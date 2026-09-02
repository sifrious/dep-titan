<?php

declare(strict_types=1);

namespace Sifrious\Titan;

use InvalidArgumentException;

final readonly class WorkKitId
{
    public function __construct(public string $value)
    {
        if (preg_match('/^workkit:[a-zA-Z0-9._:-]+$/', $value) !== 1) {
            throw new InvalidArgumentException('A work kit identity must use the workkit namespace.');
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
