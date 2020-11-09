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

namespace Causal\FalProtect\Backend\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;

/**
 * Class CrashOnNewFolderRecord
 * @package Causal\FalProtect\Backend\FormDataProvider
 */
class CrashOnNewFolderRecord implements FormDataProviderInterface
{

    /**
     * Add form data to result array
     *
     * @param array $result Initialized result array
     * @return array Result filled with more data
     */
    public function addData(array $result): array
    {
        $protectedTable = 'tx_falprotect_folder';
        if (!($result['tableName'] === $protectedTable && $result['command'] === 'new')) {
            return $result;
        }

        throw new \Exception(sprintf(
            'You are not allowed to manually create records for table "%s". ' .
            'This should not happen but is probably related to %s.',
            $protectedTable,
            'https://forge.typo3.org/issues/92788'
        ), 1604938756);
    }

}