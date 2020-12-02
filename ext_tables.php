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
})('fal_protect');
