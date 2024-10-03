<?php
declare(strict_types = 1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgSpriteIconProvider;
use TYPO3\CMS\Core\Information\Typo3Version;

return [
    'actions-protect-folder' => (new Typo3Version())->getMajorVersion() >= 12
        ? [
            'provider' => SvgSpriteIconProvider::class,
            'source' => 'EXT:fal_protect/Resources/Public/Icons/protect-folder-v12.svg',
            'sprite' => 'EXT:fal_protect/Resources/Public/Icons/sprites.svg#protect-folder'
        ]
        : [
            'provider' => SvgIconProvider::class,
            'source' => 'EXT:fal_protect/Resources/Public/Icons/protect-folder.svg'
        ],
];
