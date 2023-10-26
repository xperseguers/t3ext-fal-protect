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

namespace Causal\FalProtect\EventListener;

use TYPO3\CMS\Backend\Controller\Event\AfterBackendPageRenderEvent;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class BackendControllerEventListener
{
    private PageRenderer $pageRenderer;
    private UriBuilder $uriBuilder;

    public function __construct(PageRenderer $pageRenderer, UriBuilder $uriBuilder)
    {
        $this->pageRenderer = $pageRenderer;
        $this->uriBuilder = $uriBuilder;
    }

    public function afterBackendPageRender(AfterBackendPageRenderEvent $event): void
    {
        $this->pageRenderer->addInlineSetting('FolderEdit', 'moduleUrl', (string)$this->uriBuilder->buildUriFromRoute('folder_edit'));
    }
}
