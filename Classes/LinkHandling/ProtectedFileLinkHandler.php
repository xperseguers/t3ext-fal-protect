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

namespace Causal\FalProtect\LinkHandling;

use Causal\FalProtect\Utility\AccessSecurity;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\LinkHandling\FileLinkHandler;
use TYPO3\CMS\Core\Resource\Exception\FileDoesNotExistException;
use TYPO3\CMS\Core\Resource\FileInterface;

class ProtectedFileLinkHandler extends FileLinkHandler
{
    /**
     * @param array $data
     * @return FileInterface|null
     * @throws FileDoesNotExistException
     */
    protected function resolveFile(array $data): ?FileInterface
    {
        $file = parent::resolveFile($data);

        // Link to file even if access is missing?
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        if ($request !== null) {
            if (ApplicationType::fromRequest($request)->isBackend()) {
                // No check needed in Backend
                return $file;
            }
            // Possibly deprecated since TYPO3 v12, see
            // https://docs.typo3.org/c/typo3/cms-core/main/en-us/Changelog/12.0/Breaking-97816-NewTypoScriptParserInFrontend.html
            $frontendTypoScriptConfigArray = $request->getAttribute('frontend.controller')->tmpl->setup['config.'] ?? [];
            if ((bool)($frontendTypoScriptConfigArray['typolinkLinkAccessRestrictedPages'] ?? false)) {
                return $file;
            }
        } else {
            $tsfe = $GLOBALS['TSFE'] ?? null;
            if ($tsfe && (bool)$tsfe->config['config']['typolinkLinkAccessRestrictedPages']) {
                return $file;
            }
        }

        if ($file !== null && !AccessSecurity::isFileAccessible($file)) {
            // No access to that file
            $file = null;
        }

        return $file;
    }
}
