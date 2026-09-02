<?php

declare(strict_types=1);

namespace Sifrious\Titan;

final readonly class PlannedTaskGraphCompilationResult
{
    public array $failures;

    public function __construct(
        public PlannedTaskGraphCompilationStatus $status,
        public ?PlannedTaskGraph $graph,
        array $failures = [],
    ) {
        $this->failures = array_values($failures);
    }

    public function acceptedSuccessfully(): bool
    {
        return $this->status === PlannedTaskGraphCompilationStatus::Accepted && $this->graph !== null;
    }
}
