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

namespace Causal\FalProtect\Utility;

use Causal\FalProtect\Domain\Repository\FolderRepository;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class Security
 * @package Causal\FalProtect\Utility
 */
class AccessSecurity
{

    /**
     * Returns TRUE if the folder is accessible by the Frontend user.
     *
     * @param FolderInterface $folder
     * @return bool
     */
    public static function isFolderAccessible(FolderInterface $folder): bool
    {
        $typo3Branch = class_exists(\TYPO3\CMS\Core\Information\Typo3Version::class)
            ? (new \TYPO3\CMS\Core\Information\Typo3Version())->getBranch()
            : TYPO3_branch;
        if (version_compare($typo3Branch, '9.5', '>=')) {
            $frontendUserAspect = GeneralUtility::makeInstance(Context::class)->getAspect('frontend.user');
        } else {
            $frontendUserAspect = $GLOBALS['TSFE']->fe_user;
        }
        $userGroups = $frontendUserAspect->getGroupIds();

        // Check access restrictions on folders up to the root
        // InsufficientFolderAccessPermissionsException does not seem to be throwable in that context
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

        return true;
    }

    /**
     * Returns TRUE if the file is accessible by the Frontend user.
     *
     * @param FileInterface $file
     * @param int $maxAge
     * @return bool
     */
    public static function isFileAccessible(FileInterface $file, int &$maxAge = 0): bool
    {
        if (!static::isFolderAccessible($file->getParentFolder())) {
            return false;
        }

        $frontendUserAspect = GeneralUtility::makeInstance(Context::class)->getAspect('frontend.user');
        $userGroups = $frontendUserAspect->getGroupIds();

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

}