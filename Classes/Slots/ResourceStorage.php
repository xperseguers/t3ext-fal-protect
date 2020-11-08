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

use Causal\FalProtect\Domain\Repository\FolderRepository;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This slot is used when running TYPO3 v9.
 *
 * Class ResourceStorage
 * @package Causal\FalProtect\Slots
 */
class ResourceStorage
{

    /**
     * @var Folder
     */
    protected static $previousFolder;

    /**
     * @var FolderRepository
     */
    protected $folderRepository;

    /**
     * ResourceStorage constructor.
     */
    public function __construct()
    {
        $this->folderRepository = GeneralUtility::makeInstance(FolderRepository::class);
    }

    /**
     * @param Folder $folder
     * @param Folder $targetFolder
     * @param string $newName
     * @param Folder $originalFolder
     */
    public function postFolderMove(Folder $folder, Folder $targetFolder, string $newName, Folder $originalFolder): void
    {
        $newIdentifier = $targetFolder->getIdentifier() . $newName . '/';
        $newFolder = $targetFolder->getStorage()->getFolder($newIdentifier);
        $this->folderRepository->moveRestrictions($folder, $newFolder);
    }

    /**
     * @param Folder $folder
     * @param Folder $targetFolder
     * @param $newName
     */
    public function postFolderCopy(Folder $folder, Folder $targetFolder, string $newName): void {
        $newIdentifier = $targetFolder->getIdentifier() . $newName . '/';
        $newFolder = $targetFolder->getStorage()->getFolder($newIdentifier);
        $this->folderRepository->copyRestrictions($folder, $newFolder);
    }

    /**
     * @param Folder $folder
     */
    public function postFolderDelete(Folder $folder): void
    {
        $this->folderRepository->deleteRestrictions($folder);
    }

    /**
     * @param Folder $folder
     * @param string $newName
     */
    public function preFolderRename(Folder $folder, string $newName): void
    {
        static::$previousFolder = $folder;
    }

    /**
     * @param Folder $folder
     * @param string $newName
     */
    public function postFolderRename(Folder $folder, string $newName): void
    {
        if ($folder->getIdentifier() === static::$previousFolder->getIdentifier()) {
            // This is a known bug: https://forge.typo3.org/issues/92790
            $newIdentifier = dirname($folder->getIdentifier()) . '/' . $newName . '/';
            $folder = $folder->getStorage()->getFolder($newIdentifier);
        }
        $this->folderRepository->moveRestrictions(static::$previousFolder, $folder);
    }

}
