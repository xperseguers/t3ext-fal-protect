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
 * LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace Causal\FalProtect\Middleware;

use Causal\FalProtect\Domain\Repository\FolderRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\ImmediateResponseException;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\ErrorController;

/**
 * Class FileMiddleware
 * @package Causal\FalProtect\Middleware
 */
class FileMiddleware implements MiddlewareInterface, LoggerAwareInterface
{

    use LoggerAwareTrait;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $target = $request->getRequestTarget();
        $fileadminDir = $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'];

        if (substr($target, 0, strlen($fileadminDir) + 1) === '/' . $fileadminDir) {
            /** @var Response $response */
            $response = GeneralUtility::makeInstance(Response::class);

            $defaultStorage = GeneralUtility::makeInstance(ResourceFactory::class)->getDefaultStorage();
            if ($defaultStorage === null) {
                $this->logger->error('Default storage cannot be determined, please check the configuration of your File Storage record at root.');
                // It is better to block everything than possibly let an administrator think
                // everything is correctly configured
                return $response->withStatus(503, 'Service Unavailable');
            }
            $fileIdentifier = substr($target, strlen($fileadminDir));

            if (!$defaultStorage->hasFile($fileIdentifier)) {
                $this->pageNotFoundAction($request);
            }

            $frontendUser = version_compare((new \TYPO3\CMS\Core\Information\Typo3Version())->getBranch(), '10.4', '<')
                ? $GLOBALS['TSFE']->fe_user
                : $request->getAttribute('frontend.user');

            $file = $defaultStorage->getFile($fileIdentifier);
            $maxAge = 14400;    // TODO: make this somehow configurable?
            if (!$this->isFileAccessible($file, $frontendUser, $maxAge)) {
                $this->pageNotFoundAction($request);
            }

            $fileName = $file->getForLocalProcessing(false);

            /**
             * Note: we cannot return a standard PSR response as
             * @see \TYPO3\CMS\Core\Http\AbstractApplication::sendResponse()
             * will load all the content into memory using $body->__toString()
             */
            header('Content-Type: ' . $file->getMimeType());
            header('Content-Length: ' . (string)$file->getSize());
            if ($maxAge > 0) {
                header('Cache-Control: public, max-age=' . $maxAge);
            } else {
                header('Cache-Control: ' . implode(', ', [
                    'no-store',
                    'no-cache',
                    'must-revalidate',
                    'max-age=0',
                    'post-check=0',
                    'pre-check=0',
                ]));
                header('Pragma: no-cache');
            }

            if (ob_get_level()) {
                ob_end_clean();
            }
            flush();

            readfile($fileName);
            exit;
        }

        return $handler->handle($request);
    }

    /**
     * Checks whether a given file is accessible by current authenticated user.
     *
     * @param FileInterface $file
     * @param FrontendUserAuthentication $user
     * @param int &$maxAge
     * @return bool
     */
    protected function isFileAccessible(FileInterface $file, FrontendUserAuthentication $user, int &$maxAge): bool
    {
        // This check is supposed to never succeed if the processed folder is properly
        // checked at the Web Server level to allow direct access
        if ($file->getStorage()->isWithinProcessingFolder($file->getIdentifier())) {
            return true;
        }

        // Normally done in Middleware typo3/cms-frontend/prepare-tsfe-rendering but we want
        // to be as lightweight as possible:
        $user->fetchGroupData();

        $frontendUserAspect = GeneralUtility::makeInstance(Context::class)->getAspect('frontend.user');
        $userGroups = $frontendUserAspect->getGroupIds();

        // Check access restrictions on folders up to the root
        // InsufficientFolderAccessPermissionsException does not seem to be throwable in that context
        $folder = $file->getParentFolder();
        $folderRepository = GeneralUtility::makeInstance(FolderRepository::class);
        while ($folder->getIdentifier() !== '/') {
            $record = $folderRepository->findOneByObject($folder, false);
            $accessGroups = GeneralUtility::intExplode(',', $record['fe_groups'] ?? '', true);
            if (!empty($accessGroups) && empty(array_intersect($accessGroups, $userGroups))) {
                // Access denied
                return false;
            }
            $folder = $folder->getParentFolder();
        }

        $isVisible = $file->hasProperty('visible') ? (bool)$file->getProperty('visible') : true;
        if ($isVisible) {
            $startTime = $file->getProperty('starttime');
            $endTime = $file->getProperty('endtime');
            if (($startTime > 0 && $startTime > $GLOBALS['SIM_ACCESS_TIME'])
                || ($endTime > 0 && $endTime < $GLOBALS['SIM_ACCESS_TIME'])) {
                return false;
            }

            $accessGroups = $file->getProperty('fe_groups');
            if (empty($accessGroups)) {
                return true;
            }

            $accessGroups = GeneralUtility::intExplode(',', $accessGroups, true);
            if (!empty(array_intersect($accessGroups, $userGroups))) {
                // Prevent caching by a CDN or another kind of proxy
                $maxAge = 0;
                return true;
            }
        }

        return false;
    }

    protected function pageNotFoundAction(ServerRequestInterface $request, string $message = 'Not Found'): void
    {
        $response = GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction($request, $message);

        throw new ImmediateResponseException($response, 1604918043);
    }

}
