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

namespace Causal\FalProtect\Event;

use TYPO3\CMS\Core\Resource\FileInterface;

class SecurityCheckEvent
{
    /**
     * @var FileInterface
     */
    private $file;

    /**
     * @var bool
     */
    private $isAccessible;

    public function __construct(FileInterface $file, bool $isAccessible)
    {
        $this->file = $file;
        $this->isAccessible = $isAccessible;
    }

    public function getFile(): FileInterface
    {
        return $this->file;
    }

    public function isAccessible(): bool
    {
        return $this->isAccessible;
    }

    public function setAccessible(bool $isAccessible): self
    {
        $this->isAccessible = $isAccessible;
        return $this;
    }
}
