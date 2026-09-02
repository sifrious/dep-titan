<?php

declare(strict_types=1);

namespace Sifrious\Titan;

final readonly class WorkKitReadModel
{
    public array $dependencies;

    public array $selectedCapabilities;

    public array $verificationSteps;

    public array $completionCriteria;

    public array $failureCriteria;

    public array $sourceRecords;

    public array $actions;

    public function __construct(
        public string $id,
        public string $outcome,
        public string $firstAction,
        array $dependencies,
        public string $scopeFence,
        array $selectedCapabilities,
        array $verificationSteps,
        array $completionCriteria,
        array $failureCriteria,
        array $sourceRecords,
        public string $status,
        public bool $executable,
        array $actions,
        public ?string $supersedes,
    ) {
        $this->dependencies = array_values($dependencies);
        $this->selectedCapabilities = array_values($selectedCapabilities);
        $this->verificationSteps = array_values($verificationSteps);
        $this->completionCriteria = array_values($completionCriteria);
        $this->failureCriteria = array_values($failureCriteria);
        $this->sourceRecords = array_values($sourceRecords);
        $this->actions = array_values($actions);
    }

    public static function fromWorkKit(WorkKit $kit): self
    {
        return new self(
            id: $kit->id->value,
            outcome: $kit->outcome,
            firstAction: $kit->firstAction,
            dependencies: array_map(
                static fn (DeclaredDependency $dependency): array => [
                    'id' => $dependency->id,
                    'description' => $dependency->description,
                    'satisfied' => $dependency->satisfied,
                ],
                $kit->dependencies,
            ),
            scopeFence: $kit->scopeFence->description,
            selectedCapabilities: array_map(
                static fn (CapabilityId $capability): string => $capability->value,
                $kit->selectedCapabilities,
            ),
            verificationSteps: $kit->verificationSteps,
            completionCriteria: $kit->completionCriteria,
            failureCriteria: $kit->failureCriteria,
            sourceRecords: array_map(
                static fn (SourceProvenance $provenance): array => [
                    'kind' => $provenance->kind->value,
                    'source_id' => $provenance->sourceId->value,
                    'origin' => $provenance->origin,
                ],
                $kit->sourceRecords,
            ),
            status: $kit->status->value,
            executable: $kit->isExecutable(),
            actions: array_map(
                static fn (WorkKitAction $action): string => $action->value,
                $kit->availableActions(),
            ),
            supersedes: $kit->supersedes?->value,
        );
    }
}
