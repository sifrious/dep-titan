<?php

declare(strict_types=1);

namespace Sifrious\Titan;

use InvalidArgumentException;

final readonly class OrbisTemplateId
{
    public function __construct(public string $value)
    {
        if (preg_match('/^orbis:[a-zA-Z0-9._:-]+$/', $value) !== 1) {
            throw new InvalidArgumentException('An Orbis template reference must use the orbis namespace.');
        }
    }
}
