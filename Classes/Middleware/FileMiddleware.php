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

namespace Causal\FalProtect\Middleware;

use Causal\FalProtect\Domain\Repository\FolderRepository;
use Causal\FalProtect\Utility\AccessSecurity;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\ImmediateResponseException;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Http\Stream;
use TYPO3\CMS\Core\Resource\FileInterface;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\ErrorController;

/**
 * Class FileMiddleware
 * @package Causal\FalProtect\Middleware
 */
class FileMiddleware implements MiddlewareInterface, LoggerAwareInterface
{

    use LoggerAwareTrait;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $target = $request->getRequestTarget();
        $fileadminDir = $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'];

        if (substr($target, 0, strlen($fileadminDir) + 1) === '/' . $fileadminDir) {
            /** @var Response $response */
            $response = GeneralUtility::makeInstance(Response::class);

            $defaultStorage = GeneralUtility::makeInstance(ResourceFactory::class)->getDefaultStorage();
            if ($defaultStorage === null) {
                $this->logger->error('Default storage cannot be determined, please check the configuration of your File Storage record at root.');
                // It is better to block everything than possibly let an administrator think
                // everything is correctly configured
                return $response->withStatus(503, 'Service Unavailable');
            }
            $fileIdentifier = substr($target, strlen($fileadminDir));

            // Respect encoded file names like "sonderzeichenäöü.png" with configurations like [SYS][systemLocale] = "de_DE.UTF8" && [SYS][UTF8filesystem] = "true"
            $fileIdentifier = urldecode($fileIdentifier);

            if (!$defaultStorage->hasFile($fileIdentifier)) {
                $this->pageNotFoundAction($request);
            }

            $frontendUser = version_compare((new \TYPO3\CMS\Core\Information\Typo3Version())->getBranch(), '10.4', '<')
                ? $GLOBALS['TSFE']->fe_user
                : $request->getAttribute('frontend.user');

            $file = $defaultStorage->getFile($fileIdentifier);
            $maxAge = 14400;    // TODO: make this somehow configurable?
            if (!$this->isFileAccessible($file, $frontendUser, $maxAge)) {
                $this->pageNotFoundAction($request);
            }

            $fileName = $file->getForLocalProcessing(false);

            /**
             * Note: we cannot return a standard PSR response as
             * @see \TYPO3\CMS\Core\Http\AbstractApplication::sendResponse()
             * will load all the content into memory using $body->__toString()
             */
            header('Content-Type: ' . $file->getMimeType());
            if ($maxAge > 0) {
                header('Cache-Control: public, max-age=' . $maxAge);
            } else {
                header('Cache-Control: ' . implode(', ', [
                    'no-store',
                    'no-cache',
                    'must-revalidate',
                    'max-age=0',
                    'post-check=0',
                    'pre-check=0',
                ]));
                header('Pragma: no-cache');
            }

            $this->handlePartialDownload($fileName);
        }

        return $handler->handle($request);
    }

    /**
     * Checks whether a given file is accessible by current authenticated user.
     *
     * @param FileInterface $file
     * @param FrontendUserAuthentication $user
     * @param int &$maxAge
     * @return bool
     */
    protected function isFileAccessible(FileInterface $file, FrontendUserAuthentication $user, int &$maxAge): bool
    {
        // This check is supposed to never succeed if the processed folder is properly
        // checked at the Web Server level to allow direct access
        if ($file->getStorage()->isWithinProcessingFolder($file->getIdentifier())) {
            if ($file instanceof \TYPO3\CMS\Core\Resource\ProcessedFile) {
                return $this->isFileAccessible($file->getOriginalFile(), $user, $maxAge);
            }
            return true;
        }

        // Normally done in Middleware typo3/cms-frontend/prepare-tsfe-rendering but we want
        // to be as lightweight as possible:
        $user->fetchGroupData();

        return AccessSecurity::isFileAccessible($file, $maxAge);
    }

    /**
     * @param string $fileName
     * @see https://www.techstruggles.com/mp3-streaming-for-apple-iphone-with-php-readfile-file_get_contents-fail/
     */
    protected function handlePartialDownload(string $fileName): void
    {
        $fp = fopen($fileName, 'rb');

        $size   = filesize($fileName); // File size
        $length = $size;               // Content length
        $start  = 0;                   // Start byte
        $end    = $size - 1;           // End byte

        if (isset($_SERVER['HTTP_RANGE'])) {
            // Extract the range string
            list(, $range) = explode('=', $_SERVER['HTTP_RANGE'], 2);
            // Make sure the client has not sent us a multibyte range
            if (strpos($range, ',') !== false) {
                // TODO: Should this be issued here, or should the first
                // range be used? Or should the header be ignored and
                // we output the whole content?
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header('Content-Range: bytes ' . sprintf('%s-%s/%s', $start, $end, $size));
                // TODO: Echo some info to the client?
                exit;
            }

            // If the range starts with an '-' we start from the beginning
            // If not, we forward the file pointer
            // and make sure to get the end byte if specified
            if (substr($range, 0, 1) === '-') {
                // The n-number of the last bytes is requested
                $c_start = $size - (int)substr($range, 1);
                $c_end   = $end;
            } else {
                $range   = explode('-', $range);
                $c_start = (int)$range[0];
                $c_end   = !empty($range[1] ?? '') ? (int)$range[1] : $size;
            }

            /* Check the range and make sure it is treated according to the specs.
             * https://www.w3.org/Protocols/rfc2616/rfc2616-sec14.html
             */
            // End bytes can not be larger than $end.
            $c_end = ($c_end > $end) ? $end : $c_end;
            // Validate the requested range and return an error if it is incorrect
            if ($c_start > $c_end || $c_start > $size - 1 || $c_end >= $size) {
                header('HTTP/1.1 416 Requested Range Not Satisfiable');
                header('Content-Range: bytes ' . sprintf('%s-%s/%s', $start, $end, $size));
                // TODO: Echo some info to the client?
                exit;
            }

            $start  = $c_start;
            $end    = $c_end;
            $length = $end - $start + 1; // Calculate new content length
            fseek($fp, $start);
            header('HTTP/1.1 206 Partial Content');
            // Notify the client the byte range we'll be outputting
            header('Content-Range: bytes ' . sprintf('%s-%s/%s', $start, $end, $size));
        }

        header('Content-Length: ' . $length);
        header('Accept-Ranges: bytes');

        // Try to reset time limit for big files
        set_time_limit(0);

        // Start buffered download (chunks of 8K)
        $buffer = 1024 * 8;
        while (!feof($fp) && ($p = ftell($fp)) <= $end) {
            if ($p + $buffer > $end) {
                // In case we are only outputting a chunk, make sure we do not
                // read past the length
                $buffer = $end - $p + 1;
            }
            echo fread($fp, $buffer);

            // Free up memory, otherwise large files will trigger PHP's memory limit
            flush();
        }

        fclose($fp);
        exit;
    }

    protected function pageNotFoundAction(ServerRequestInterface $request, string $message = 'Not Found'): void
    {
        $response = GeneralUtility::makeInstance(ErrorController::class)->pageNotFoundAction($request, $message);

        throw new ImmediateResponseException($response, 1604918043);
    }

}
