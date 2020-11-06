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

}, 'fal_protect');
