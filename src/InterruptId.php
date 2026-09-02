<?php

declare(strict_types=1);

namespace Sifrious\Titan;

use InvalidArgumentException;

final readonly class InterruptId
{
    public function __construct(public string $value)
    {
        if (preg_match('/^interrupt:[a-zA-Z0-9._:-]+$/', $value) !== 1) {
            throw new InvalidArgumentException('An interrupt identity must use the interrupt namespace.');
        }
    }
}
