<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace Causal\FalProtect\Hooks;

use Causal\FalProtect\Domain\Repository\FolderRepository;
use Causal\FalProtect\Service\SecurityService;
use function count;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ButtonBarHook
{
    /**
     * @var Folder
     */
    protected $folder;

    public function __construct()
    {
        try {
            $this->folder = GeneralUtility::makeInstance(ResourceFactory::class)
                ->retrieveFileOrFolderObject((string)GeneralUtility::_GP('id'));
        } catch (Exception $exception) {
        }
    }

    public function getButtons(array $parameters, ButtonBar $buttonBar): array
    {
        if (GeneralUtility::makeInstance(SecurityService::class)->canBeEdited($this->folder) === true) {
            $button = $this->getButton();
            $index = count($parameters['buttons'][ButtonBar::BUTTON_POSITION_LEFT]);
            $parameters['buttons'][ButtonBar::BUTTON_POSITION_LEFT][$index + 1][] = $buttonBar
                ->makeLinkButton()
                ->setHref($button['url'])
                ->setIcon($button['icon'])
                ->setTitle($button['title']);
        }

        return $parameters['buttons'];
    }

    protected function buildAddUrl(): string
    {
        return $this->buildUrl([
            'defVals' => [
                'tx_falprotect_folder' => [
                    'identifier' => $this->folder->getIdentifier(),
                    'identifier_hash' => $this->folder->getHashedIdentifier(),
                    'storage' => $this->folder->getStorage()->getUid()
                ]
            ],
            'edit' => [
                'tx_falprotect_folder' => [
                    0 => 'new'
                ]
            ]
        ]);
    }

    protected function buildEditUrl(int $uid): string
    {
        return $this->buildUrl([
            'edit' => [
                'tx_falprotect_folder' => [
                    $uid => 'edit'
                ]
            ]
        ]);
    }

    protected function buildUrl(array $parameters): string
    {
        $parameters['returnUrl'] = GeneralUtility::getIndpEnv('REQUEST_URI');

        return (string)GeneralUtility::makeInstance(UriBuilder::class)->buildUriFromRoute('record_edit', $parameters);
    }

    protected function getButton(): array
    {
        $folderUid = $this->getFolderUid();

        return [
            'icon' => $this->getIcon(),
            'title' => $this->translate('clickmenu.folderPermissions'),
            'url' => $folderUid > 0 ? $this->buildEditUrl($folderUid) : $this->buildAddUrl()
        ];
    }

    protected function getFolderUid(): int
    {
        $folder = GeneralUtility::makeInstance(FolderRepository::class)->findOneByObject($this->folder);

        return $folder['uid'];
    }

    protected function getIcon(): Icon
    {
        return GeneralUtility::makeInstance(IconFactory::class)->getIcon('actions-protect-folder', Icon::SIZE_SMALL);
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function translate(string $input): string
    {
        return (string)$this->getLanguageService()->sl(
            'LLL:EXT:fal_protect/Resources/Private/Language/locallang_db.xlf:' . $input
        );
    }
}
