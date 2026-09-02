<?php

declare(strict_types=1);

namespace Sifrious\Titan;

use InvalidArgumentException;

final readonly class RequiredInput
{
    public function __construct(
        public string $id,
        public string $description,
        public bool $available = false,
    ) {
        if (trim($id) === '') {
            throw new InvalidArgumentException('A required input must have an identity.');
        }

        if (trim($description) === '') {
            throw new InvalidArgumentException('A required input must describe expected evidence or artifact.');
        }
    }

    public function withAvailable(bool $available): self
    {
        return new self($this->id, $this->description, $available);
    }
}
