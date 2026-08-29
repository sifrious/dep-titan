<?php

declare(strict_types=1);

namespace Sifrious\Titan;

use InvalidArgumentException;

final readonly class EvidenceReference
{
    public function __construct(
        public string $source,
        public string $referenceId,
    ) {
        if (trim($source) === '') {
            throw new InvalidArgumentException('Evidence source is required.');
        }

        if (trim($referenceId) === '') {
            throw new InvalidArgumentException('Evidence reference identity is required.');
        }
    }
}
