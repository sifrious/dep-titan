<?php

declare(strict_types=1);

namespace Sifrious\Titan;

use InvalidArgumentException;

final readonly class ScopeFence
{
    public array $includes;

    public array $excludes;

    public function __construct(
        public string $description,
        array $includes = [],
        array $excludes = [],
    ) {
        if (trim($description) === '') {
            throw new InvalidArgumentException('A scope fence description is required.');
        }

        $this->includes = array_values($includes);
        $this->excludes = array_values($excludes);
    }
}
