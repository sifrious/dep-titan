<?php

declare(strict_types=1);

namespace Sifrious\Titan;

use InvalidArgumentException;

final readonly class DecisionReference
{
    public function __construct(
        public string $authority,
        public string $referenceId,
    ) {
        if (trim($authority) === '') {
            throw new InvalidArgumentException('Decision authority is required.');
        }

        if (trim($referenceId) === '') {
            throw new InvalidArgumentException('Decision reference identity is required.');
        }
    }
}
