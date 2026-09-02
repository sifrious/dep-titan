<?php

declare(strict_types=1);

namespace Sifrious\Titan;

final readonly class PlannedTaskGraphSupersession
{
    public function __construct(
        public PlannedTaskGraph $retired,
        public PlannedTaskGraph $successor,
    ) {}
}
