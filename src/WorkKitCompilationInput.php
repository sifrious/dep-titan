<?php

declare(strict_types=1);

namespace Sifrious\Titan;

final readonly class WorkKitCompilationInput
{
    public array $dependencies;

    public array $selectedCapabilities;

    public array $verificationSteps;

    public array $completionCriteria;

    public array $failureCriteria;

    public function __construct(
        public WorkKitId $id,
        public PlanningRecords $records,
        array $dependencies,
        public ScopeFence $scopeFence,
        array $selectedCapabilities,
        array $verificationSteps,
        array $completionCriteria,
        array $failureCriteria,
        public ?WorkKitId $supersedes = null,
    ) {
        $this->dependencies = array_values($dependencies);
        $this->selectedCapabilities = array_values($selectedCapabilities);
        $this->verificationSteps = array_values($verificationSteps);
        $this->completionCriteria = array_values($completionCriteria);
        $this->failureCriteria = array_values($failureCriteria);
    }
}
