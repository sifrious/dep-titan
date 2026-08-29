<?php

declare(strict_types=1);

namespace Sifrious\Titan;

final readonly class PlannedTaskGraphCompilationInput
{
    public array $tasks;

    public function __construct(
        public PlannedTaskGraphId $id,
        public WorkKit $workKit,
        array $tasks,
        public ?PlannedTaskGraphId $supersedes = null,
    ) {
        $this->tasks = array_values($tasks);
    }
}
