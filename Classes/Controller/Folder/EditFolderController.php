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

namespace Causal\FalProtect\Controller\Folder;

use Causal\FalProtect\Domain\Repository\FolderRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class EditFolderController
 * @package Causal\FalProtect\Controller\Folder
 */
class EditFolderController
{

    /**
     * The original target, but validated.
     *
     * @var string
     */
    protected $target;

    /**
     * Return URL of list module.
     *
     * @var string
     */
    protected $returnUrl;

    /**
     * The folder that is being edited on
     *
     * @var Folder
     */
    protected $folderObject;

    /**
     * @var StorageRepository
     */
    protected $storageRepository;

    /**
     * @var FolderRepository
     */
    protected $folderRepository;

    /**
     * @var UriBuilder
     */
    protected $uriBuilder;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->storageRepository = GeneralUtility::makeInstance(StorageRepository::class);
        $this->folderRepository = GeneralUtility::makeInstance(FolderRepository::class);
        $this->uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
    }

    /**
     * Processes the request, currently everything is handled and put together via "process()"
     *
     * @param ServerRequestInterface $request the current request
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->init($request);

        if ($this->folderObject === null) {
            return new RedirectResponse($this->returnUrl);
        }

        $record = $this->folderRepository->findOneByObject($this->folderObject);
        $urlParameters = [
            'edit' => [
                $this->folderRepository->tableName => [
                    $record['uid'] => 'edit'
                ]
            ],
            'returnUrl' => $this->returnUrl
        ];
        $url = (string)$this->uriBuilder->buildUriFromRoute('record_edit', $urlParameters);

        return new RedirectResponse($url);
    }

    /**
     * @param ServerRequestInterface $request
     */
    protected function init(ServerRequestInterface $request): void
    {
        $parsedBody = $request->getParsedBody();
        $queryParams = $request->getQueryParams();

        $this->target = ($combinedIdentifier = $parsedBody['target'] ?? $queryParams['target'] ?? '');
        $this->returnUrl = GeneralUtility::sanitizeLocalUrl($parsedBody['returnUrl'] ?? $queryParams['returnUrl'] ?? '');

        if (preg_match('/^(\d+):(.*)$/', $combinedIdentifier, $matches)) {
            $storage = $this->storageRepository->findByUid((int)$matches[1]);
            if ($storage !== null
                && $storage->getDriverType() === 'Local'
                && $storage->getConfiguration()['pathType'] === 'relative') {
                $this->folderObject = $storage->getFolder($matches[2]);
            }
        }
    }

}
