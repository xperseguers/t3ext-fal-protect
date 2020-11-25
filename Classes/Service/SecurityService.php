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

namespace Causal\FalProtect\Service;

use function array_flip;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class SecurityService
{
    public function canBeEdited($record): bool
    {
        $canBeEdited = false;

        if (($record instanceof Folder) === true) {
            $role = $record->getRole();

            $canBeEdited = $record->getStorage()->isDefault() === true
                && $record->getIdentifier() !== '/'
                && $record->checkActionPermission('write') === true
                && $role !== FolderInterface::ROLE_RECYCLER
                && $role !== FolderInterface::ROLE_TEMPORARY
                && $this->canEditFolder() === true;
        } elseif (($record instanceof File) === true) {
            $canBeEdited = $record->checkActionPermission('write') === true && $record->isTextFile() === true;
        }

        return $canBeEdited;
    }

    protected function canEditFolder(): bool
    {
        $backendUser = $this->getBackendUserAuthentication();

        if (
            !($GLOBALS['TCA']['tx_falprotect_folder']['columns']['fe_groups']['exclude'] ?? false)
            || $backendUser->isAdmin() === true
        ) {
            return true;
        }

        if (isset($backendUser->groupData['non_exclude_fields']) === true) {
            $nonExcludeFieldsArray = array_flip(
                GeneralUtility::trimExplode(',', $backendUser->groupData['non_exclude_fields'])
            );

            if (isset($nonExcludeFieldsArray['tx_falprotect_folder:fe_groups']) === true) {
                return true;
            }
        }

        return false;
    }

    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
