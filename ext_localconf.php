<?php
defined('TYPO3') || die();

(static function (string $_EXTKEY) {
    // Hook into the FileOrFolderLinkBuilder to prevent linking to folders
    // and files that are protected and thus not accessible
    $GLOBALS['TYPO3_CONF_VARS']['SYS']['linkHandler']['file'] =
        \Causal\FalProtect\LinkHandling\ProtectedFileLinkHandler::class;

    // Refresh the file tree after updating permissions on a folder
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] =
        \Causal\FalProtect\Hooks\DataHandler::class;

    $dataProviders =& $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'];
    $dataProviders[\Causal\FalProtect\Backend\FormDataProvider\CrashOnNewFolderRecord::class] = [
        'before' => [
            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEditRow::class,
        ],
    ];

    // Override the context menu as defined in EXT:filelist
    $GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1486418731] = \Causal\FalProtect\ContextMenu\ItemProviders\FileProvider::class;
})('fal_protect');
