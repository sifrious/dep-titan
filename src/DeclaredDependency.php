<?php

declare(strict_types=1);

namespace Sifrious\Titan;

use InvalidArgumentException;

final readonly class DeclaredDependency
{
    public function __construct(
        public string $id,
        public string $description,
        public bool $satisfied,
    ) {
        if (trim($id) === '') {
            throw new InvalidArgumentException('A declared dependency requires an identity.');
        }

        if (trim($description) === '') {
            throw new InvalidArgumentException('A declared dependency requires a description.');
        }
    }

    public function withSatisfied(bool $satisfied): self
    {
        return new self($this->id, $this->description, $satisfied);
    }
}
