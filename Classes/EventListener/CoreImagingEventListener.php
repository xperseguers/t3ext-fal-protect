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

namespace Causal\FalProtect\EventListener;

use Causal\FalProtect\Domain\Repository\FolderRepository;
use TYPO3\CMS\Core\Imaging\Event\ModifyIconForResourcePropertiesEvent;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class CoreImagingEventListener
 * @package Causal\FalProtect\EventListener
 */
class CoreImagingEventListener
{

    /**
     * @param ModifyIconForResourcePropertiesEvent $event
     */
    public function postProcessIconForResource(ModifyIconForResourcePropertiesEvent $event): void
    {
        $overlayIdentifier = null;

        $resource = $event->getResource();
        if ($resource instanceof Folder) {
            $overlayIdentifier = static::getFolderOverlayIdentifier($resource);
        } elseif ($resource instanceof File) {
            $overlayIdentifier = static::getFileOverlayIdentifier($resource);
        }

        if ($overlayIdentifier !== null) {
            $event->setOverlayIdentifier($overlayIdentifier);
        }
    }

    /**
     * @param FolderInterface $folder
     * @return string|null
     * @internal
     */
    public static function getFolderOverlayIdentifier(FolderInterface $folder): ?string
    {
        // As found in EXT:core/Configuration/DefaultConfiguration.php
        $recordStatusMapping = $GLOBALS['TYPO3_CONF_VARS']['SYS']['IconFactory']['recordStatusMapping'];

        $folderRepository = GeneralUtility::makeInstance(FolderRepository::class);
        $record = $folderRepository->findOneByObject($folder, false);
        if (!empty($record['fe_groups'] ?? '')) {
            return $recordStatusMapping['fe_group'];
        }

        return null;
    }

    /**
     * @param FileInterface $file
     * @return string|null
     * @internal
     */
    public static function getFileOverlayIdentifier(FileInterface $file): ?string
    {
        $overlayIdentifier = null;

        // As found in EXT:core/Configuration/DefaultConfiguration.php
        $recordStatusMapping = $GLOBALS['TYPO3_CONF_VARS']['SYS']['IconFactory']['recordStatusMapping'];

        $isVisible = $file->hasProperty('visible') ? (bool)$file->getProperty('visible') : true;
        $accessGroups = $file->getProperty('fe_groups');

        if (!$isVisible) {
            $overlayIdentifier = $recordStatusMapping['hidden'];
        } elseif (!empty($accessGroups)) {
            $overlayIdentifier = $recordStatusMapping['fe_group'];
        } else {
            $startTime = $file->getProperty('starttime');
            $endTime = $file->getProperty('endtime');
            if ($endTime > 0 && $endTime < $GLOBALS['SIM_ACCESS_TIME']) {
                $overlayIdentifier = $recordStatusMapping['endtime'];
            } elseif ($startTime > $GLOBALS['SIM_ACCESS_TIME']) {
                $overlayIdentifier = $recordStatusMapping['starttime'];
            } elseif ($endTime > $GLOBALS['SIM_ACCESS_TIME']) {
                $overlayIdentifier = $recordStatusMapping['futureendtime'];
            }
        }

        return $overlayIdentifier;
    }

}
