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

namespace Causal\FalProtect\ContextMenu\ItemProviders;

use Causal\FalProtect\Service\SecurityService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FileProvider extends \TYPO3\CMS\Filelist\ContextMenu\ItemProviders\FileProvider
{
    protected function canBeEdited(): bool
    {
        return GeneralUtility::makeInstance(SecurityService::class)->canBeEdited($this->record);
    }

    protected function getAdditionalAttributes(string $itemName): array
    {
        if ($itemName === 'edit' && $this->isFolder() === true) {
            $attributes = [
                'data-callback-module' => 'TYPO3/CMS/FalProtect/ContextMenuActions'
            ];
        } else {
            $attributes = parent::getAdditionalAttributes($itemName);
        }

        return $attributes;
    }

    protected function initialize(): void
    {
        parent::initialize();

        if ($this->isFolder() === true) {
            $this->itemsConfiguration['edit']['callbackAction'] = 'editFolder';
            $this->itemsConfiguration['edit']['iconIdentifier'] = 'actions-protect-folder';
            $this->itemsConfiguration['edit']['label'] =
                'LLL:EXT:fal_protect/Resources/Private/Language/locallang_db.xlf:clickmenu.folderPermissions';
        }
    }
}
