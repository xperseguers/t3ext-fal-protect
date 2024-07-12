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

use Causal\FalProtect\Event\SecurityCheckEvent;
use Causal\FalProtect\Stream\FileStream;
use Causal\FalProtect\Utility\AccessSecurity;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\FrontendBackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Http\ImmediateResponseException;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ProcessedFile;
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
        // Respect encoded file names like "sonderzeichenäöü.png" with configurations like [SYS][systemLocale] = "de_DE.UTF8" && [SYS][UTF8filesystem] = "true"
        $target = urldecode($request->getUri()->getPath());

        $file = null;
        // Filter out what is obviously the root page or an non-authorized file name
        if ($target !== '/' && $this->isValidTarget($target)) {
            try {
                $file = GeneralUtility::makeInstance(ResourceFactory::class)->getFileObjectByStorageAndIdentifier(0, $target);
            } catch (\InvalidArgumentException $e) {
                // Nothing to do
            }
        }
        if ($file !== null) {
            $frontendUser = $request->getAttribute('frontend.user');

            $maxAge = 14400;    // TODO: make this somehow configurable?
            $isAccessible = $this->isFileAccessible($request, $file, $frontendUser, $maxAge)
                || $this->isFileAccessibleBackendUser($file);

            $eventDispatcher = GeneralUtility::makeInstance(EventDispatcherInterface::class);
            $securityCheckEvent = GeneralUtility::makeInstance(SecurityCheckEvent::class, $file, $isAccessible);
            $eventDispatcher->dispatch($securityCheckEvent);
            $isAccessible = $securityCheckEvent->isAccessible();

            if (!$isAccessible) {
                $settings = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('fal_protect') ?? [];
                if ((bool)($settings['return_403'] ?? false)) {
                    $this->accessDeniedAction($request);
                }
                $this->pageNotFoundAction($request);
            }

            $fileName = $file->getForLocalProcessing(false);
            if (!is_readable($fileName)) {
                // This may happen, e.g., if the FAL database is out-of-sync with the file system
                $this->pageNotFoundAction($request);
            }

            $headers = [];
            $headers['Content-Type'] = $file->getMimeType();
            if ($maxAge > 0) {
                $headers['Cache-Control'] = 'public, max-age=' . $maxAge;
            } else {
                $headers['Cache-Control'] = implode(', ', [
                    'no-store',
                    'no-cache',
                    'must-revalidate',
                    'max-age=0',
                    'post-check=0',
                    'pre-check=0',
                ]);
                $headers['Pragma'] = 'no-cache';
            }

            $stream = new FileStream($fileName);
            return new Response($stream, 200, $headers);
        }

        return $handler->handle($request);
    }

    /**
     * @param string $target
     * @return bool
     * @see \TYPO3\CMS\Core\Resource\Security\FileNameValidator::isValid()
     */
    protected function isValidTarget(string $target): bool
    {
        if (isset($GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern'])) {
            $fileDenyPattern = $GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern'];
        } else {
            /** Borrowed from @see \TYPO3\CMS\Core\Resource\Security\FileNameValidator::DEFAULT_FILE_DENY_PATTERN */
            $fileDenyPattern = '\\.(php[3-8]?|phpsh|phtml|pht|phar|shtml|cgi)(\\..*)?$|\\.pl$|^\\.htaccess$';
        }

        $pattern = '/[[:cntrl:]]/';
        if ($target !== '' && $fileDenyPattern !== '') {
            $pattern = '/(?:[[:cntrl:]]|' . $fileDenyPattern . ')/iu';
        }
        return preg_match($pattern, $target) === 0;
    }

    /**
     * Checks whether a given file is accessible by current authenticated user.
     *
     * @param ServerRequestInterface $request
     * @param FileInterface $file
     * @param FrontendUserAuthentication $user
     * @param int &$maxAge
     * @return bool
     */
    protected function isFileAccessible(
        ServerRequestInterface $request,
        FileInterface $file,
        FrontendUserAuthentication $user,
        int &$maxAge
    ): bool
    {
        // This check is supposed to never succeed if the processed folder is properly
        // checked at the Web Server level to allow direct access
        if ($file->getStorage()->isWithinProcessingFolder($file->getIdentifier())) {
            if ($file instanceof ProcessedFile) {
                return $this->isFileAccessible($request, $file->getOriginalFile(), $user, $maxAge);
            }
            return true;
        }

        // Normally done in Middleware typo3/cms-frontend/prepare-tsfe-rendering but we want
        // to be as lightweight as possible:
        $user->fetchGroupData($request);

        return AccessSecurity::isFileAccessible($file, $maxAge);
    }

    /**
     * Checks whether a given file is accessible by current authenticated backend user.
     *
     * @param FileInterface $file
     * @return bool
     */
    protected function isFileAccessibleBackendUser(FileInterface $file): bool
    {
        // No BE user auth
        if (!($GLOBALS['BE_USER'] instanceof FrontendBackendUserAuthentication)) {
            return false;
        }

        // Not logged in
        if (!is_array($GLOBALS['BE_USER']->user)) {
            return false;
        }

        if ($GLOBALS['BE_USER']->isAdmin()) {
            return true;
        }

        // Handle processed files with original folder access
        if ($file instanceof ProcessedFile) {
            $file = $file->getOriginalFile();
        }

        foreach ($GLOBALS['BE_USER']->getFileMountRecords() as $fileMount) {
            if ((new Typo3Version())->getMajorVersion() >= 12) {
                $identifier = $fileMount['identifier'];
            } else {
                $identifier = $fileMount['base'] . ':' . $fileMount['path'];
            }
            /** @var Folder $fileMountObject */
            $fileMountObject = GeneralUtility::makeInstance(ResourceFactory::class)->getFolderObjectFromCombinedIdentifier($identifier);
            if ($fileMountObject->getStorage()->getUid() === $file->getStorage()->getUid()) {
                if ($this->isFileInFolderOrSubFolder($fileMountObject, $file)) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function pageNotFoundAction(ServerRequestInterface $request, string $message = 'Not Found'): void
    {
        $response = GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction($request, $message);

        throw new ImmediateResponseException($response, 1604918043);
    }

    protected function accessDeniedAction(ServerRequestInterface $request, string $message = 'Forbidden'): void
    {
        $response = GeneralUtility::makeInstance(ErrorController::class)->accessDeniedAction($request, $message);

        throw new ImmediateResponseException($response, 1604918043);
    }

    protected function isFileInFolderOrSubFolder(Folder $folder, FileInterface $file)
    {
        foreach ($folder->getFiles() as $folderFiles) {
            if ($folderFiles->getUid() === $file->getUid()) {
                return true;
            }
        }
        foreach ($folder->getSubfolders() as $subfolder) {
            if ($this->isFileInFolderOrSubFolder($subfolder, $file)) {
                return true;
            }
        }
        return false;
    }
}
