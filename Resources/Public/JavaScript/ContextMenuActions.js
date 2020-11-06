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
 * Module: TYPO3/CMS/FalProtect/ContextMenuActions
 *
 * JavaScript to handle fal_protect actions from context menu
 * @exports TYPO3/CMS/FalProtect/ContextMenuActions
 */
define([], function () {
    'use strict';

    var ContextMenuActions = {
        getReturnUrl: function () {
            return encodeURIComponent(top.list_frame.document.location.pathname + top.list_frame.document.location.search);
        },

        editFolder: function (table, uid) {
            top.TYPO3.Backend.ContentContainer.setUrl(
                top.TYPO3.settings.FolderEdit.moduleUrl + '&target=' + encodeURIComponent(uid) + '&returnUrl=' + ContextMenuActions.getReturnUrl(),
            );
        }
    }

    return ContextMenuActions;
});