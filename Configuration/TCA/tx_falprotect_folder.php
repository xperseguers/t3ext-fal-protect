<?php
defined('TYPO3_MODE') || die();

$typo3Version = class_exists(\TYPO3\CMS\Core\Information\Typo3Version::class)
    ? (new \TYPO3\CMS\Core\Information\Typo3Version())->getVersion()
    : TYPO3_version;

$coreLabels = version_compare($typo3Version, '9.5', '>=')
    ? 'LLL:EXT:core/Resources/Private/Language/'
    : 'LLL:EXT:lang/Resources/Private/Language/';

return [
    'ctrl' => [
        'title' => $coreLabels . 'locallang_tca.xlf:sys_file_collection.folder',
        'label' => 'identifier',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'hideTable' => true,
        'rootLevel' => 1,
        'iconfile' => version_compare($typo3Version, '10.4.10', '<')
            ? 'EXT:core/Resources/Public/Icons/T3Icons/apps/apps-filetree-folder-default.svg'
            : 'EXT:core/Resources/Public/Icons/T3Icons/svgs/apps/apps-filetree-folder-default.svg',
        'default_sortby' => 'crdate DESC',
        'security' => [
            'ignoreWebMountRestriction' => true,
            'ignoreRootLevelRestriction' => true,
        ],
        'searchFields' => ''
    ],
    'interface' => [],
    'types' => [
        1 => [
            'showitem' => 'storage, fe_groups'
        ],
    ],
    'palettes' => [],
    'columns' => [
        'crdate' => [
            'config' => [
                'type' => 'passthrough',
            ],
        ],
        'storage' => [
            'label' => $coreLabels . 'locallang_tca.xlf:sys_file.storage',
            'config' => [
                'readOnly' => true,
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    ['', 0]
                ],
                'foreign_table' => 'sys_file_storage',
                'foreign_table_where' => 'ORDER BY sys_file_storage.name',
                'size' => 1,
                'minitems' => 0,
                'maxitems' => 1
            ]
        ],
        'identifier' => [
            'label' => $coreLabels . 'locallang_tca.xlf:sys_file.identifier',
            'config' => [
                'readOnly' => true,
                'type' => 'input',
                'size' => 30
            ]
        ],
        'fe_groups' => [
            'exclude' => true,
            'label' => $coreLabels . 'locallang_general.xlf:LGL.fe_group',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'size' => 5,
                'maxitems' => 20,
                'items' => [
                    [
                        $coreLabels . 'locallang_general.xlf:LGL.hide_at_login',
                        -1
                    ],
                    [
                        $coreLabels . 'locallang_general.xlf:LGL.any_login',
                        -2
                    ],
                    [
                        $coreLabels . 'locallang_general.xlf:LGL.usergroups',
                        '--div--'
                    ]
                ],
                'exclusiveKeys' => '-1,-2',
                'foreign_table' => 'fe_groups',
                'foreign_table_where' => 'ORDER BY fe_groups.title'
            ]
        ],
    ],
];
