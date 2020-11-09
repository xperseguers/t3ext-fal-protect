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

if (!\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('filemetadata')) {
    $tempColumns['visible'] = [
        'exclude' => true,
        'label' => 'LLL:EXT:fal_protect/Resources/Private/Language/locallang_db.xlf:sys_file_metadata.visible',
        'config' => [
            'type' => 'check',
            'default' => '1'
        ],
    ];
    $tempColumns['fe_groups'] = [
        'exclude' => true,
        'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.fe_group',
        'config' => [
            'type' => 'select',
            'renderType' => 'selectMultipleSideBySide',
            'size' => 5,
            'maxitems' => 20,
            'items' => [
                [
                    'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hide_at_login',
                    -1
                ],
                [
                    'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.any_login',
                    -2
                ],
                [
                    'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.usergroups',
                    '--div--'
                ]
            ],
            'exclusiveKeys' => '-1,-2',
            'foreign_table' => 'fe_groups',
            'foreign_table_where' => 'ORDER BY fe_groups.title'
        ]
    ];

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addToAllTCAtypes(
        'sys_file_metadata',
        '
            --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
            visible,
            fe_groups
        ',
        '',
        'after:alternative'
    );
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTCAcolumns('sys_file_metadata', $tempColumns);

$GLOBALS['TCA']['sys_file_metadata']['palettes']['access'] = [
    'label' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_tca.xlf:pages.palettes.access',
    'showitem' => 'starttime, endtime, --linebreak--, fe_groups'
];
foreach ($GLOBALS['TCA']['sys_file_metadata']['types'] as &$configuration) {
    $configuration['showitem'] = str_replace('fe_groups,', '--palette--;;access,', $configuration['showitem']);
}
