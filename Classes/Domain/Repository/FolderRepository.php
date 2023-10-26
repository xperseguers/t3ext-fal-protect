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

namespace Causal\FalProtect\Domain\Repository;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\FolderInterface;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class FolderRepository
 * @package Causal\FalProtect\Domain\Repository
 */
class FolderRepository implements SingletonInterface
{

    /**
     * @var string
     */
    public $tableName = 'tx_falprotect_folder';

    /**
     * @param FolderInterface $folder
     * @param bool $createIfNotExisting
     * @return array|null
     */
    public function findOneByObject(FolderInterface $folder, bool $createIfNotExisting = true): ?array
    {
        $record = $this->findOneByStorageAndHashedIdentifier(
            $folder->getStorage()->getUid(),
            $folder->getHashedIdentifier()
        );

        if ($record === null && $createIfNotExisting) {
            $record = $this->createFolder($folder);
        }

        return $record;
    }

    /**
     * @param int $storage
     * @param string $hashedIdentifier
     * @return array|null
     */
    public function findOneByStorageAndHashedIdentifier(int $storage, string $hashedIdentifier): ?array
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->tableName);
        $record = $connection
            ->select(
                ['*'],
                $this->tableName,
                [
                    'storage' => $storage,
                    'identifier_hash' => $hashedIdentifier,
                ]
            )
            ->fetch();

        return !empty($record) ? $record : null;
    }

    /**
     * @param Folder $source
     * @param Folder $target
     */
    public function moveRestrictions(Folder $source, Folder $target): void
    {
        $record = $this->findOneByObject($source, false);
        if ($record !== null) {
            $newData = [
                'identifier' => $target->getIdentifier(),
                'identifier_hash' => $target->getHashedIdentifier(),
            ];
            $this->updateRestrictionsRecord($record['uid'], $newData);
        }
    }

    /**
     * @param int $uid
     * @param array $newData
     */
    public function updateRestrictionsRecord(int $uid, array $newData): void
    {
        $newData['tstamp'] = $GLOBALS['EXEC_TIME'];
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($this->tableName)
            ->update(
                $this->tableName,
                $newData,
                [
                    'uid' => $uid,
                ]
            );
    }

    /**
     * @param Folder $source
     * @param Folder $target
     */
    public function copyRestrictions(Folder $source, Folder $target): void
    {
        $record = $this->findOneByObject($source, false);
        if ($record !== null && !empty($record['fe_groups'])) {
            $this->createFolder($target, $record['fe_groups']);
        }
    }

    /**
     * @param Folder $folder
     */
    public function deleteRestrictions(Folder $folder): void
    {
        $this->deleteRestrictionsRecord($folder->getStorage()->getUid(), $folder->getHashedIdentifier());
    }

    /**
     * @param int $storage
     * @param string $hashedIdentifier
     */
    public function deleteRestrictionsRecord(int $storage, string $hashedIdentifier): void
    {
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($this->tableName)
            ->delete(
                $this->tableName,
                [
                    'storage' => $storage,
                    'identifier_hash' => $hashedIdentifier,
                ]
            );
    }

    /**
     * Creates a folder record.
     *
     * @param FolderInterface $folder
     * @param string|null $feGroups
     * @return array
     */
    protected function createFolder(FolderInterface $folder, ?string $feGroups = null): array
    {
        return $this->createFolderRecord(
            $folder->getStorage()->getUid(),
            $folder->getIdentifier(),
            $folder->getHashedIdentifier(),
            $feGroups
        );
    }

    /**
     * Creates a folder record.
     *
     * @param int $storage
     * @param string $identifier
     * @param string $hashedIdentifier
     * @param string|null $feGroups
     * @return array
     */
    public function createFolderRecord(
        int $storage,
        string $identifier,
        string $hashedIdentifier,
        ?string $feGroups = null
    ): array
    {
        $emptyRecord = [
            'pid' => 0,
            'crdate' => $GLOBALS['EXEC_TIME'],
            'tstamp' => $GLOBALS['EXEC_TIME'],
            //'cruser_id' => isset($GLOBALS['BE_USER']->user['uid']) ? (int)$GLOBALS['BE_USER']->user['uid'] : 0,
            'storage' => $storage,
            'identifier' => $identifier,
            'identifier_hash' => $hashedIdentifier,
            'fe_groups' => $feGroups,
        ];

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->tableName);
        $connection->insert(
            $this->tableName,
            $emptyRecord
        );

        $record = $emptyRecord;
        $record['uid'] = (int)$connection->lastInsertId($this->tableName);

        return $record;
    }

}
