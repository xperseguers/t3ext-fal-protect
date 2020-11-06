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

namespace Causal\FalProtect\EventListener;

use TYPO3\CMS\Core\Imaging\Event\ModifyIconForResourcePropertiesEvent;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\FileInterface;

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
        if ($resource instanceof File) {
            $overlayIdentifier = static::getOverlayIdentifier($resource);
        }

        if ($overlayIdentifier !== null) {
            $event->setOverlayIdentifier($overlayIdentifier);
        }
    }

    /**
     * @param FileInterface $file
     * @return string|null
     * @internal
     */
    public static function getOverlayIdentifier(FileInterface $file): ?string
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
