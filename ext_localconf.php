<?php
defined('TYPO3_MODE') || defined('TYPO3') || die();

(static function (string $_EXTKEY) {
    $typo3Version = (new \TYPO3\CMS\Core\Information\Typo3Version())->getBranch();
    if (version_compare($typo3Version, '10.2', '<')) {
        /** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
        $signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Extbase\SignalSlot\Dispatcher::class);
        $signalSlotDispatcher->connect(
            'TYPO3\\CMS\\Core\\Imaging\\IconFactory',
            'buildIconForResourceSignal',
            \Causal\FalProtect\Slots\IconFactory::class,
            'postProcessIconForResource'
        );

        $listenSignals = [
            \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFolderCopy,
            \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFolderCopy,
            \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFolderMove,
            \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFolderMove,
            \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFolderRename,
            \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFolderRename,
            \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PreFolderDelete,
            \TYPO3\CMS\Core\Resource\ResourceStorageInterface::SIGNAL_PostFolderDelete,
        ];
        foreach ($listenSignals as $signal) {
            $signalSlotDispatcher->connect(
                'TYPO3\\CMS\\Core\\Resource\\ResourceStorage',
                $signal,
                \Causal\FalProtect\Slots\ResourceStorage::class,
                $signal
            );
        }
    }

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
    if (version_compare($typo3Version, '12.4', '<')) {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/backend.php']['constructPostProcess'][] = \Causal\FalProtect\Hooks\BackendControllerHook::class . '->addJavaScript';
    }

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addUserTSConfig('
        options.saveDocNew.tx_falprotect_folder = 0
        options.disableDelete.tx_falprotect_folder = 1
    ');
})('fal_protect');
