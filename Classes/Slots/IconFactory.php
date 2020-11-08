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

namespace Causal\FalProtect\Slots;

use Causal\FalProtect\EventListener\CoreImagingEventListener;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceInterface;

/**
 * This slot is used when running TYPO3 v9.
 *
 * Class IconFactory
 * @package Causal\FalProtect\Slots
 */
class IconFactory
{

    /**
     * @param ResourceInterface $resource
     * @param string $size
     * @param array $options
     * @param string $iconIdentifier
     * @param string|null $overlayIdentifier
     * @return array
     */
    public function postProcessIconForResource(
        ResourceInterface $resource,
        string $size,
        array $options,
        string $iconIdentifier,
        ?string $overlayIdentifier
    ): array
    {
        $newOverlayIdentifier = null;

        if ($resource instanceof Folder) {
            $newOverlayIdentifier = CoreImagingEventListener::getFolderOverlayIdentifier($resource);
        } elseif ($resource instanceof File) {
            $newOverlayIdentifier = CoreImagingEventListener::getFileOverlayIdentifier($resource);
        }

        $result = [
            $resource,
            $size,
            $options,
            $iconIdentifier,
            $newOverlayIdentifier ?? $overlayIdentifier
        ];

        return $result;
    }

}
