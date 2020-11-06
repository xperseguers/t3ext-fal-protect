<?php
defined('TYPO3_MODE') || die();

$tempColumns = [
    'starttime' => [
        'exclude' => true,
        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.starttime',
        'config' => [
            'type' => 'input',
            'renderType' => 'inputDateTime',
            'eval' => 'datetime,int',
            'default' => 0,
            'behaviour' => [
                'allowLanguageSynchronization' => true
            ]
        ]
    ],
    'endtime' => [
        'exclude' => true,
        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.endtime',
        'config' => [
            'type' => 'input',
            'renderType' => 'inputDateTime',
            'eval' => 'datetime,int',
            'default' => 0,
            'range' => [
                'upper' => mktime(0, 0, 0, 1, 1, 2038)
            ],
            'behaviour' => [
                'allowLanguageSynchronization' => true
            ]
        ]
    ],
];

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('sys_file_metadata', $tempColumns);

$GLOBALS['TCA']['sys_file_metadata']['palettes']['access'] = [
    'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.access',
    'showitem' => 'starttime, endtime, --linebreak--, fe_groups'
];
foreach ($GLOBALS['TCA']['sys_file_metadata']['types'] as &$configuration) {
    $configuration['showitem'] = str_replace('fe_groups,', '--palette--;;access,', $configuration['showitem']);
}
