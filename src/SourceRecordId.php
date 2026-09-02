<?php

declare(strict_types=1);

namespace Sifrious\Titan;

use InvalidArgumentException;

final readonly class SourceRecordId
{
    public function __construct(public string $value)
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException('A source record identity is required to preserve Landing provenance.');
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
