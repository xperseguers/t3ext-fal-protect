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
use Causal\FalProtect\Traits\UpdateSubfoldersTrait;
use TYPO3\CMS\Core\Resource\Event\AfterFolderCopiedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFolderDeletedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFolderMovedEvent;
use TYPO3\CMS\Core\Resource\Event\AfterFolderRenamedEvent;
use TYPO3\CMS\Core\Resource\Event\BeforeFolderCopiedEvent;
use TYPO3\CMS\Core\Resource\Event\BeforeFolderDeletedEvent;
use TYPO3\CMS\Core\Resource\Event\BeforeFolderMovedEvent;
use TYPO3\CMS\Core\Resource\Event\BeforeFolderRenamedEvent;
use TYPO3\CMS\Core\Resource\Folder;

/**
 * Class CoreResourceStorageEventListener
 * @package Causal\FalProtect\EventListener
 */
class CoreResourceStorageEventListener
{

    use UpdateSubfoldersTrait;

    /**
     * @var Folder
     */
    protected $previousFolder;

    /**
     * CoreResourceStorageEventListener constructor.
     *
     * @param FolderRepository $folderRepository
     */
    public function __construct(FolderRepository $folderRepository)
    {
        $this->folderRepository = $folderRepository;
    }

    /**
     * A folder is getting copied.
     *
     * @param BeforeFolderCopiedEvent $event
     */
    public function beforeFolderCopied(BeforeFolderCopiedEvent $event): void
    {
        $this->populatePreviousFolderMapping($event->getFolder());
    }

    /**
     * A folder has been copied.
     *
     * @param AfterFolderCopiedEvent $event
     */
    public function afterFolderCopied(AfterFolderCopiedEvent $event): void
    {
        $this->folderRepository->copyRestrictions($event->getFolder(), $event->getTargetFolder());
        $this->copyRestrictionsFromSubfolders($event->getFolder(), $event->getTargetFolder());
    }

    /**
     * A folder is getting moved.
     *
     * @param BeforeFolderMovedEvent $event
     */
    public function beforeFolderMoved(BeforeFolderMovedEvent $event): void
    {
        $this->populatePreviousFolderMapping($event->getFolder());
    }

    /**
     * A folder has been moved.
     *
     * @param AfterFolderMovedEvent $event
     */
    public function afterFolderMoved(AfterFolderMovedEvent $event): void
    {
        $this->folderRepository->moveRestrictions($event->getFolder(), $event->getTargetFolder());
        $this->moveRestrictionsFromSubfolders($event->getFolder(), $event->getTargetFolder());
    }

    /**
     * A folder is getting renamed.
     *
     * @param BeforeFolderRenamedEvent $event
     */
    public function beforeFolderRenamed(BeforeFolderRenamedEvent $event): void
    {
        $this->previousFolder = $event->getFolder();
        $this->populatePreviousFolderMapping($event->getFolder());
    }

    /**
     * A folder has been renamed.
     *
     * @param AfterFolderRenamedEvent $event
     */
    public function afterFolderRenamed(AfterFolderRenamedEvent $event): void
    {
        $this->folderRepository->moveRestrictions($this->previousFolder, $event->getFolder());
        $this->moveRestrictionsFromSubfolders($this->previousFolder, $event->getFolder());
    }

    /**
     * A folder is getting deleted.
     *
     * @param BeforeFolderDeletedEvent $event
     */
    public function beforeFolderDeleted(BeforeFolderDeletedEvent $event): void
    {
        $this->populatePreviousFolderMapping($event->getFolder());
    }

    /**
     * A folder has been deleted.
     *
     * @param AfterFolderDeletedEvent $event
     */
    public function afterFolderDeleted(AfterFolderDeletedEvent $event): void
    {
        $this->folderRepository->deleteRestrictions($event->getFolder());
        $this->deleteRestrictionsFromSubfolders($event->getFolder());
    }

}
