<?php

declare(strict_types=1);

namespace Sifrious\Titan;

final readonly class PlannedTaskGraphFailure
{
    public function __construct(
        public string $code,
        public string $field,
        public string $message,
    ) {}
}
