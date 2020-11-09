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

namespace Causal\FalProtect\Traits;

use Causal\FalProtect\Domain\Repository\FolderRepository;
use TYPO3\CMS\Core\Resource\Folder;

/**
 * Class UpdateSubfoldersTrait
 * @package Causal\FalProtect\Traits
 */
trait UpdateSubfoldersTrait
{

    /**
     * @var array
     */
    protected $previousFolderMapping;

    /**
     * @var FolderRepository
     */
    protected $folderRepository;

    /**
     * @param Folder $folder
     */
    protected function populatePreviousFolderMapping(Folder $folder): void
    {
        $this->previousFolderMapping[$folder->getCombinedIdentifier()] = $this->getSubfolderIdentifiers($folder);
    }

    /**
     * @param Folder $folder
     * @return array
     */
    protected function getSubfolderIdentifiers(Folder $folder): array
    {
        $folderIdentifiers = [];

        foreach ($folder->getSubfolders() as $subFolder) {
            $folderIdentifiers[] = [$subFolder->getHashedIdentifier(), $subFolder->getIdentifier()];
            $folderIdentifiers = array_merge($folderIdentifiers, $this->getSubFolderIdentifiers($subFolder));
        }

        return $folderIdentifiers;
    }

    /**
     * @param Folder $source
     * @param Folder $target
     */
    protected function moveRestrictionsFromSubfolders(Folder $source, Folder $target): void
    {
        if (empty($this->previousFolderMapping[$source->getCombinedIdentifier()])) {
            return;
        }

        $newFolderMapping = $this->getSubfolderIdentifiers($target);
        foreach ($this->previousFolderMapping[$source->getCombinedIdentifier()] as $key => $folderInfo) {
            $record = $this->folderRepository->findOneByStorageAndHashedIdentifier(
                $source->getStorage()->getUid(),
                $folderInfo[0]
            );
            if ($record !== null) {
                $this->folderRepository->updateRestrictionsRecord(
                    $record['uid'],
                    [
                        'storage' => $target->getStorage()->getUid(),
                        'identifier' => $newFolderMapping[$key][1],
                        'identifier_hash' => $newFolderMapping[$key][0],
                    ]
                );
            }
        }
    }

    /**
     * @param Folder $source
     * @param Folder $target
     */
    protected function copyRestrictionsFromSubfolders(Folder $source, Folder $target): void
    {
        if (empty($this->previousFolderMapping[$source->getCombinedIdentifier()])) {
            return;
        }

        $newFolderMapping = $this->getSubfolderIdentifiers($target);
        foreach ($this->previousFolderMapping[$source->getCombinedIdentifier()] as $key => $folderInfo) {
            $record = $this->folderRepository->findOneByStorageAndHashedIdentifier(
                $source->getStorage()->getUid(),
                $folderInfo[0]
            );
            if ($record !== null && !empty($record['fe_groups'])) {
                $this->folderRepository->createFolderRecord(
                    $target->getStorage()->getUid(),
                    $newFolderMapping[$key][1],
                    $newFolderMapping[$key][0],
                    $record['fe_groups']
                );
            }
        }
    }

    /**
     * @param Folder $folder
     */
    protected function deleteRestrictionsFromSubfolders(Folder $folder): void
    {
        $storage = $folder->getStorage()->getUid();
        foreach ($this->previousFolderMapping[$folder->getCombinedIdentifier()] as $folderInfo) {
            $this->folderRepository->deleteRestrictionsRecord(
                $storage,
                $folderInfo[0]
            );
        }
    }

}
