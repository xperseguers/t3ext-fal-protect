<?php

declare(strict_types=1);

namespace Causal\FalProtect\Stream;

use TYPO3\CMS\Core\Http\SelfEmittableLazyOpenStream;

class FileStream extends SelfEmittableLazyOpenStream
{
    /**
     * @return void
     * @see https://www.techstruggles.com/mp3-streaming-for-apple-iphone-with-php-readfile-file_get_contents-fail/
     */
    public function emit()
    {
        $fp = fopen($this->filename, 'rb');

        $size = filesize($this->filename); // File size
        $length = $size;               // Content length
        $start = 0;                   // Start byte
        $end = $size - 1;           // End byte

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
                return;
            }

            // If the range starts with an '-' we start from the beginning
            // If not, we forward the file pointer
            // and make sure to get the end byte if specified
            if (substr($range, 0, 1) === '-') {
                // The n-number of the last bytes is requested
                $c_start = $size - (int) substr($range, 1);
                $c_end = $end;
            } else {
                $range = explode('-', $range);
                $c_start = (int) $range[0];
                $c_end = !empty($range[1] ?? '') ? (int) $range[1] : $size;
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
                return;
            }

            $start = $c_start;
            $end = $c_end;
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
    }

    public function getFilename(): string
    {
        return $this->filename;
    }
}
