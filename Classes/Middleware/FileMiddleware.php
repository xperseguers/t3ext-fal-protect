<?php
declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with TYPO3 source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace Causal\FalProtect\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FileMiddleware implements MiddlewareInterface
{

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $target = $request->getRequestTarget();
        $fileadminDir = $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'];

        if (substr($target, 0, strlen($fileadminDir) + 1) === '/' . $fileadminDir) {
            /** @var Response $response */
            $response = GeneralUtility::makeInstance(Response::class);

            $defaultStorage = ResourceFactory::getInstance()->getDefaultStorage();
            $fileIdentifier = substr($target, strlen($fileadminDir));

            if (!$defaultStorage->hasFile($fileIdentifier)) {
                return $response->withStatus(403, 'Forbidden');
            }

            $file = $defaultStorage->getFile($fileIdentifier);
            if (!$this->isFileAccessible($file)) {
                return $response->withStatus(403, 'Forbidden');
            }

            $fileName = $file->getForLocalProcessing(false);

            return $response
                ->withHeader('Content-Type', $file->getMimeType())
                ->withHeader('Content-Length', (string)$file->getSize())
                ->withBody(new Stream($fileName));
        }

        return $handler->handle($request);
    }

    /**
     * Checks whether a given file is accessible by current authenticated user.
     *
     * @param FileInterface $file
     * @return bool
     */
    protected function isFileAccessible(FileInterface $file): bool
    {
        // This check is supposed to never succeeds if the processed folder is properly
        // checked at the Web Server level to allow direct access
        if ($file->getStorage()->isWithinProcessingFolder($file->getIdentifier())) {
            return true;
        }

        $isVisible = $file->hasProperty('visible') ? (bool)$file->getProperty('visible') : true;
        if ($isVisible) {
            $accessGroups = $file->getProperty('fe_groups');
            if (empty($accessGroups)) {
                return true;
            }

            $accessGroups = GeneralUtility::intExplode(',', $accessGroups, true);

            $frontendUserAspect = GeneralUtility::makeInstance(Context::class)->getAspect('frontend.user');

            // Normally done in Middleware typo3/cms-frontend/prepare-tsfe-rendering but we want
            // to be as lightweight as possible:
            if (version_compare((new \TYPO3\CMS\Core\Information\Typo3Version())->getBranch(), '10.4', '<')) {
                $GLOBALS['TSFE']->fe_user->fetchGroupData();
            } else {
                $refObject = new \ReflectionObject($frontendUserAspect);
                $userProperty = $refObject->getProperty('user');
                $userProperty->setAccessible(true);
                $user = $userProperty->getValue($frontendUserAspect);
                $user->fetchGroupData();
            }

            $userGroups = $frontendUserAspect->getGroupIds();

            if (!empty(array_intersect($accessGroups, $userGroups))) {
                return true;
            }
        }

        return false;
    }

}