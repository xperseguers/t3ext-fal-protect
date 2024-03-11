<?php
defined('TYPO3_MODE') || die();

(static function (string $_EXTKEY) {
    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
    $iconRegistry->registerIcon(
        'actions-protect-folder',
        \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        [
            'source' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/protect-folder.svg',
        ]
    );

    if (TYPO3_MODE === 'BE') {
        // Override the context menu as defined in EXT:filelist
        $GLOBALS['TYPO3_CONF_VARS']['BE']['ContextMenu']['ItemProviders'][1486418731] = \Causal\FalProtect\ContextMenu\ItemProviders\FileProvider::class;
    }
})('fal_protect');
