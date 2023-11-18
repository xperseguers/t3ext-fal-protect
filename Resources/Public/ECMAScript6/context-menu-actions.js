/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Module: @causal/fal-protect/context-menu-actions
 * @exports @causal/fal-protect/context-menu-actions
 */
class ContextMenuActions {
    editFolder(table, uid) {
        const returnUrl = encodeURIComponent(top.list_frame.document.location.pathname + top.list_frame.document.location.search);
        top.TYPO3.Backend.ContentContainer.setUrl(
            top.TYPO3.settings.FolderEdit.moduleUrl + '&target=' + encodeURIComponent(uid) + '&returnUrl=' + returnUrl
        );
    }
}

export default new ContextMenuActions();
