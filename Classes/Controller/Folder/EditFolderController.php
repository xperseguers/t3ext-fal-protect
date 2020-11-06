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
 * LICENSE.txt file that was distributed with TYPO3 source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace Causal\FalProtect\Controller\Folder;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Class EditFolderController
 * @package Causal\FalProtect\Controller\Folder
 */
class EditFolderController
{

    /**
     * Module content accumulated.
     *
     * @var string
     */
    protected $content;

    /**
     * Original input target
     *
     * @var string
     */
    protected $origTarget;

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
     * the file that is being edited on
     *
     * @var File
     */
    protected $fileObject;

    /**
     * ModuleTemplate object
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     * @var UriBuilder
     */
    protected $uriBuilder;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
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
        return new HtmlResponse('TODO: Implement edit folder form here ^^'/*$this->moduleTemplate->renderContent()*/);
    }

}
