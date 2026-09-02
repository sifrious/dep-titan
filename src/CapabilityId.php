<?php

declare(strict_types=1);

namespace Sifrious\Titan;

use InvalidArgumentException;

final readonly class CapabilityId
{
    public function __construct(public string $value)
    {
        if (preg_match('/^quain:[a-zA-Z0-9._:-]+$/', $value) !== 1) {
            throw new InvalidArgumentException('A selected capability must be referenced by a Quain identity.');
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
