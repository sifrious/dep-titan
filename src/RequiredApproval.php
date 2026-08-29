<?php

declare(strict_types=1);

namespace Sifrious\Titan;

use InvalidArgumentException;

final readonly class RequiredApproval
{
    public function __construct(
        public string $id,
        public string $description,
        public bool $granted = false,
    ) {
        if (trim($id) === '') {
            throw new InvalidArgumentException('A required approval must have an identity.');
        }

        if (trim($description) === '') {
            throw new InvalidArgumentException('A required approval must describe its decision boundary.');
        }
    }

    public function withGranted(bool $granted): self
    {
        return new self($this->id, $this->description, $granted);
    }
}
