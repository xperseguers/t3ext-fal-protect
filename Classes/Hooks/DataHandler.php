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

namespace Causal\FalProtect\Hooks;

use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Class DataHandler
 * @package Causal\FalProtect\Hooks
 */
class DataHandler
{

    /**
     * Triggers updateFolderTree after updating permissions on a folder
     *
     * @param string $status
     * @param string $table
     * @param mixed $id
     * @param array $fieldArray
     * @param \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
     */
    public function processDatamap_afterDatabaseOperations(
        string $status,
        string $table,
        $id,
        array $fieldArray,
        \TYPO3\CMS\Core\DataHandling\DataHandler $dataHandler
    ): void
    {
        if ($table === 'tx_falprotect_folder') {
            BackendUtility::setUpdateSignal('updateFolderTree');
        }
    }

}