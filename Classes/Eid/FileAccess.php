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

namespace Causal\FalProtect\Eid;

use Causal\FalProtect\Utility\AccessSecurity;
use TYPO3\CMS\Backend\FrontendBackendUserAuthentication;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * This is the equivalent as
 * @see \Causal\FalProtect\Middleware\FileMiddleware
 * but for use in TYPO3 v8, as an eID script since Middleware did not exist yet.
 */
class FileAccess
{
    public function process(): void
    {
        $target = urldecode((string)GeneralUtility::getIndpEnv('REQUEST_URI'));
        // Strip any query parameters
        $target = strtok($target, '?');

        $file = null;
        // Filter out what is obviously the root page or an non-authorized file name
        if ($target !== '/' && $this->isValidTarget($target)) {
            // We must initialize the TSFE in order to correctly guess the
            // sys_file_storage when fetching the FAL file object below
            $this->initializeTSFE();

            try {
                // Strip out the leading slash
                $target = ltrim($target, '/');
                $file = GeneralUtility::makeInstance(ResourceFactory::class)->getFileObjectByStorageAndIdentifier(0, $target);
            } catch (\InvalidArgumentException $e) {
                // Nothing to do
            }
        }
        if ($file !== null) {
            $frontendUser = $GLOBALS['TSFE']->fe_user;

            $maxAge = 14400;    // TODO: make this somehow configurable?
            if (!$this->isFileAccessible($file, $frontendUser, $maxAge) && !$this->isFileAccessibleBackendUser($file)) {
                $this->pageNotFoundAction();
            }

            $fileName = $file->getForLocalProcessing(false);

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

            $this->sendFile($fileName, $headers);
        }
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
            if ($file instanceof ProcessedFile) {
                return $this->isFileAccessible($file->getOriginalFile(), $user, $maxAge);
            }
            return true;
        }

        // Normally done in Middleware typo3/cms-frontend/prepare-tsfe-rendering but we want
        // to be as lightweight as possible:
        $user->fetchGroupData();

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
            /** @var Folder $fileMountObject */
            $fileMountObject = GeneralUtility::makeInstance(ResourceFactory::class)->getFolderObjectFromCombinedIdentifier($fileMount['base'] . ':' . $fileMount['path']);
            if ($fileMountObject->getStorage()->getUid() === $file->getStorage()->getUid()) {
                if ($this->isFileInFolderOrSubFolder($fileMountObject, $file)) {
                    return true;
                }
            }
        }

        return false;
    }

    protected function pageNotFoundAction(): void
    {
        header('HTTP/1.0 404 Not Found');
        die();
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

    public function sendFile(string $filename, array $headers): void
    {
        if (ob_get_level() > 0) {
            ob_end_clean();
        }

        $size = filesize($filename); // File size
        $headers['Content-Length'] = $size;

        foreach ($headers as $name => $value) {
            header($name . ': ' . $value);
        }

        // Try to reset time limit for big files
        set_time_limit(0);

        readfile($filename);
        exit();
    }

    /**
     * Creates an instance of TSFE and sets it as a global variable
     *
     * @return void
     */
    protected function initializeTSFE(): void
    {
        $bootstrap = Bootstrap::getInstance();
        $bootstrap->loadBaseTca();
        $bootstrap->initializeBackendUser(FrontendBackendUserAuthentication::class);

        /** @var TypoScriptFrontendController $tsfe */
        $tsfe = GeneralUtility::makeInstance(
            TypoScriptFrontendController::class,
            $GLOBALS['TYPO3_CONF_VARS'],
            GeneralUtility::_GP('id'),
            GeneralUtility::_GP('type'),
            GeneralUtility::_GP('no_cache'),
            GeneralUtility::_GP('cHash'),
            GeneralUtility::_GP('jumpurl'),
            GeneralUtility::_GP('MP'),
            GeneralUtility::_GP('RDCT')
        );

        $tsfe->initFEuser();
        $tsfe->determineId();

        $GLOBALS['TSFE'] = $tsfe;
    }
}

$typo3Branch = class_exists(\TYPO3\CMS\Core\Information\Typo3Version::class)
    ? (new \TYPO3\CMS\Core\Information\Typo3Version())->getBranch()
    : TYPO3_branch;
if (version_compare($typo3Branch, '9.5', '<')) {
    // Only register the eID script for TYPO3 v8
    $eID = new FileAccess();
    $eID->process();
}
