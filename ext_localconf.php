<?php
defined('TYPO3_MODE') || defined('TYPO3') || die();

(static function (string $_EXTKEY) {
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
    $typo3Version = (new \TYPO3\CMS\Core\Information\Typo3Version())->getBranch();
    if (version_compare($typo3Version, '12.4', '<')) {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/backend.php']['constructPostProcess'][] = \Causal\FalProtect\Hooks\BackendControllerHook::class . '->addJavaScript';
    }

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('
        options.saveDocNew.tx_falprotect_folder = 0
        options.disableDelete.tx_falprotect_folder = 1
    ');
})('fal_protect');
