<?php
defined('TYPO3_MODE') || defined('TYPO3') || die();

(static function (string $_EXTKEY) {
    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
    $typo3Version = (new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion();
    $iconRegistry->registerIcon(
        'actions-protect-folder',
        \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
        [
            'source' => $typo3Version >= 12
                ? 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/protect-folder-v12.svg'
                : 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/protect-folder.svg',
        ]
    );
})('fal_protect');
