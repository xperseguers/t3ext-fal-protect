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
     * @param Folder $folder
     * @param bool $createIfNotExisting
     * @return array|null
     */
    public function findOneByObject(Folder $folder, bool $createIfNotExisting = true): ?array
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($this->tableName);
        $record = $connection
            ->select(
                ['*'],
                $this->tableName,
                [
                    'storage' => $folder->getStorage()->getUid(),
                    'identifier_hash' => $folder->getHashedIdentifier(),
                ]
            )
            ->fetch();

        if (empty($record)) {
            $record = null;
            if ($createIfNotExisting) {
                $record = $this->createFolderRecord($folder);
            }
        }

        return $record;
    }

    /**
     * @param Folder $source
     * @param Folder $target
     */
    public function moveRestrictions(Folder $source, Folder $target): void
    {
        $record = $this->findOneByObject($source, false);
        if ($record !== null) {
            $record['identifier'] = $target->getIdentifier();
            $record['identifier_hash'] = $target->getHashedIdentifier();
            $record['tstamp'] = $GLOBALS['EXEC_TIME'];

            GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable($this->tableName)
                ->update(
                    $this->tableName,
                    $record,
                    [
                        'uid' => $record['uid'],
                    ]
                );
        }
    }

    /**
     * @param Folder $folder
     */
    public function deleteRestrictions(Folder $folder): void
    {
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($this->tableName)
            ->delete(
                $this->tableName,
                [
                    'storage' => $folder->getStorage()->getUid(),
                    'identifier_hash' => $folder->getHashedIdentifier(),
                ]
            );
    }

    /**
     * Creates an empty folder record.
     *
     * @param Folder $folder
     * @return array
     */
    protected function createFolderRecord(Folder $folder): array
    {
        $emptyRecord = [
            'pid' => 0,
            'crdate' => $GLOBALS['EXEC_TIME'],
            'tstamp' => $GLOBALS['EXEC_TIME'],
            'cruser_id' => isset($GLOBALS['BE_USER']->user['uid']) ? (int)$GLOBALS['BE_USER']->user['uid'] : 0,
            'storage' => $folder->getStorage()->getUid(),
            'identifier' => $folder->getIdentifier(),
            'identifier_hash' => $folder->getHashedIdentifier(),
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
