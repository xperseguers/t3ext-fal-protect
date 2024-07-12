<?php
defined('TYPO3') || die();

(static function (string $_EXTKEY) {
    if ((new \TYPO3\CMS\Core\Information\Typo3Version())->getMajorVersion() < 11) {
        $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Imaging\IconRegistry::class);
        $iconRegistry->registerIcon(
            'actions-protect-folder',
            \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
            [
                'source' => 'EXT:' . $_EXTKEY . '/Resources/Public/Icons/protect-folder.svg',
            ]
        );
    }
})('fal_protect');
