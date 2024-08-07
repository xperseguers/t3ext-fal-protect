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

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class FileProvider
 * @package Causal\FalProtect\ContextMenu\ItemProviders
 */
class FileProvider extends \TYPO3\CMS\Filelist\ContextMenu\ItemProviders\FileProvider
{

    /**
     * Initialize file object
     */
    protected function initialize()
    {
        parent::initialize();
        if ($this->record instanceof Folder) {
            $this->itemsConfiguration['edit']['label'] = 'LLL:EXT:fal_protect/Resources/Private/Language/locallang_db.xlf:clickmenu.folderPermissions';
            $this->itemsConfiguration['edit']['iconIdentifier'] = 'actions-protect-folder';
            $this->itemsConfiguration['edit']['callbackAction'] = 'editFolder';
        }
    }

    /**
     * @return bool
     */
    protected function canBeEdited(): bool
    {
        if ($this->isFolder()) {
            $storage = $this->record->getStorage();
            return $storage->getDriverType() === 'Local'
                && $storage->getConfiguration()['pathType'] === 'relative'
                && $this->record->checkActionPermission('write')
                && $this->record->getRole() !== FolderInterface::ROLE_TEMPORARY
                && $this->record->getRole() !== FolderInterface::ROLE_RECYCLER
                && $this->canEditFolder();
        }

        return parent::canBeEdited();
    }

    /**
     * @param string $itemName
     * @return array
     */
    protected function getAdditionalAttributes(string $itemName): array
    {
        $callbackModule = (new Typo3Version())->getMajorVersion() >= 12
            ? '@causal/fal-protect/context-menu-actions'
            : 'TYPO3/CMS/FalProtect/ContextMenuActions';

        if ($itemName === 'edit' && $this->isFolder()) {
            $attributes = [
                'data-callback-module' => $callbackModule
            ];
        } else {
            $attributes = parent::getAdditionalAttributes($itemName);
        }

        return $attributes;
    }

    /**
     * @return bool
     */
    protected function canEditFolder(): bool
    {
        /** @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication $backendUser */
        $backendUser = $GLOBALS['BE_USER'];

        if ($backendUser->isAdmin()
            || !($GLOBALS['TCA']['tx_falprotect_folder']['columns']['fe_groups']['exclude'] ?? false)) {
            return true;
        }

        if (isset($backendUser->groupData['non_exclude_fields'])) {
            $nonExcludeFieldsArray = array_flip(GeneralUtility::trimExplode(',', $backendUser->groupData['non_exclude_fields']));
            if (isset($nonExcludeFieldsArray['tx_falprotect_folder:fe_groups'])) {
                return true;
            }
        }

        return false;
    }

}
