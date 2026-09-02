<?php

declare(strict_types=1);

namespace Sifrious\Titan;

use InvalidArgumentException;

final readonly class PlannedTaskGraphId
{
    public function __construct(public string $value)
    {
        if (preg_match('/^planned-graph:[a-zA-Z0-9._:-]+$/', $value) !== 1) {
            throw new InvalidArgumentException('A planned-task graph identity must use the planned-graph namespace.');
        }
    }
}
