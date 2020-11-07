<?php
defined('TYPO3_MODE') || die();

call_user_func(function(string $_EXTKEY) {

    $typo3Branch = class_exists(\TYPO3\CMS\Core\Information\Typo3Version::class)
        ? (new \TYPO3\CMS\Core\Information\Typo3Version())->getBranch()
        : TYPO3_branch;
    if (version_compare($typo3Branch, '10.0', '<')) {
        /** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
        $signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
        $signalSlotDispatcher->connect(
            'TYPO3\\CMS\\Core\\Imaging\\IconFactory',
            'buildIconForResourceSignal',
            \Causal\FalProtect\Slots\IconFactory::class,
            'postProcessIconForResource'
        );
    }

    // Override the context menu as defined in EXT:filelist
    $GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1486418731] = \Causal\FalProtect\ContextMenu\ItemProviders\FileProvider::class;
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/backend.php']['constructPostProcess'][] = \Causal\FalProtect\Hooks\BackendControllerHook::class . '->addJavaScript';

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('
        options.saveDocNew.tx_falprotect_folder = 0
        options.disableDelete.tx_falprotect_folder = 1
    ');

}, 'fal_protect');
