<?php

declare(strict_types=1);

namespace Sifrious\Titan;

use InvalidArgumentException;

final readonly class WorkKit
{
    public array $dependencies;

    public array $selectedCapabilities;

    public array $verificationSteps;

    public array $completionCriteria;

    public array $failureCriteria;

    public array $sourceRecords;

    public function __construct(
        public WorkKitId $id,
        public string $outcome,
        public string $firstAction,
        array $dependencies,
        public ScopeFence $scopeFence,
        array $selectedCapabilities,
        array $verificationSteps,
        array $completionCriteria,
        array $failureCriteria,
        array $sourceRecords,
        public WorkKitStatus $status,
        public ?WorkKitId $supersedes = null,
    ) {
        foreach ([['outcome', $outcome], ['first_action', $firstAction]] as [$field, $value]) {
            if (trim($value) === '') {
                throw new InvalidArgumentException(str_replace('_', ' ', ucfirst($field)).' is required.');
            }
        }

        $this->dependencies = array_values($dependencies);
        $this->selectedCapabilities = array_values($selectedCapabilities);
        $this->verificationSteps = array_values($verificationSteps);
        $this->completionCriteria = array_values($completionCriteria);
        $this->failureCriteria = array_values($failureCriteria);
        $this->sourceRecords = array_values($sourceRecords);

        if ($this->verificationSteps === []) {
            throw new InvalidArgumentException('At least one verification step is required.');
        }

        if ($this->completionCriteria === []) {
            throw new InvalidArgumentException('At least one completion criterion is required.');
        }

        if ($this->failureCriteria === []) {
            throw new InvalidArgumentException('At least one failure criterion is required.');
        }

        if ($status === WorkKitStatus::Executable && ! $this->dependenciesComplete()) {
            throw new InvalidArgumentException('A work kit cannot be executable while dependencies are incomplete.');
        }
    }

    public function dependenciesComplete(): bool
    {
        foreach ($this->dependencies as $dependency) {
            if (! $dependency->satisfied) {
                return false;
            }
        }

        return true;
    }

    public function isExecutable(): bool
    {
        return $this->status === WorkKitStatus::Executable && $this->dependenciesComplete();
    }

    public function present(): WorkKitReadModel
    {
        if (! $this->isExecutable()) {
            throw new InvalidWorkKitTransition("Work kit {$this->id->value} cannot be presented as executable while dependencies are incomplete or the kit is not executable.");
        }

        return WorkKitReadModel::fromWorkKit($this);
    }

    public function apply(WorkKitAction $action): self
    {
        if (! in_array($action, $this->availableActions(), true)) {
            throw new InvalidWorkKitTransition("Action {$action->value} is not available for work kit {$this->id->value}.");
        }

        return match ($action) {
            WorkKitAction::Present => $this,
            WorkKitAction::Supersede => $this->withStatus(WorkKitStatus::Superseded),
        };
    }

    public function availableActions(): array
    {
        return match ($this->status) {
            WorkKitStatus::Assembled => [WorkKitAction::Supersede],
            WorkKitStatus::Executable => [WorkKitAction::Present, WorkKitAction::Supersede],
            WorkKitStatus::Superseded => [],
        };
    }

    public function withStatus(WorkKitStatus $status): self
    {
        return new self(
            id: $this->id,
            outcome: $this->outcome,
            firstAction: $this->firstAction,
            dependencies: $this->dependencies,
            scopeFence: $this->scopeFence,
            selectedCapabilities: $this->selectedCapabilities,
            verificationSteps: $this->verificationSteps,
            completionCriteria: $this->completionCriteria,
            failureCriteria: $this->failureCriteria,
            sourceRecords: $this->sourceRecords,
            status: $status,
            supersedes: $this->supersedes,
        );
    }
}
