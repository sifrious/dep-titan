<?php

declare(strict_types=1);

namespace Sifrious\Titan;

use InvalidArgumentException;

final readonly class PlannedTaskGraph
{
    public array $tasks;

    public array $selectedCapabilities;

    public function __construct(
        public PlannedTaskGraphId $id,
        public WorkKitId $workKitId,
        array $tasks,
        array $selectedCapabilities,
        public PlannedTaskGraphStatus $status,
        public ?PlannedTaskGraphId $supersedes = null,
    ) {
        $this->tasks = array_values($tasks);
        $this->selectedCapabilities = array_values($selectedCapabilities);

        if ($this->tasks === []) {
            throw new InvalidArgumentException('A planned-task graph requires at least one task.');
        }
    }

    public function task(PlannedTaskId $id): PlannedTask
    {
        foreach ($this->tasks as $task) {
            if ($task->id->value === $id->value) {
                return $task;
            }
        }

        throw new InvalidWorkKitTransition("Planned task {$id->value} is not in graph {$this->id->value}.");
    }

    public function readiness(PlannedTaskId $id): PlannedTaskReadiness
    {
        $task = $this->task($id);

        if ($task->completed) {
            return PlannedTaskReadiness::Completed;
        }

        if ($this->blockedByDependencies($id) !== [] || $this->pendingApprovals($id) !== []) {
            return PlannedTaskReadiness::Blocked;
        }

        if ($this->missingCapabilities($id) !== [] || $this->missingInputs($id) !== []) {
            return PlannedTaskReadiness::NotReady;
        }

        return PlannedTaskReadiness::Ready;
    }

    public function blockedByDependencies(PlannedTaskId $id): array
    {
        $task = $this->task($id);
        $blockedBy = [];

        foreach ($task->dependencies as $dependencyId) {
            $dependency = $this->task($dependencyId);

            if (! $dependency->completed) {
                $blockedBy[] = $dependencyId;
            }
        }

        return $blockedBy;
    }

    public function pendingApprovals(PlannedTaskId $id): array
    {
        $task = $this->task($id);
        $pending = [];

        foreach ($task->requiredApprovals as $approval) {
            if (! $approval->granted) {
                $pending[] = $approval;
            }
        }

        return $pending;
    }

    public function missingCapabilities(PlannedTaskId $id): array
    {
        $task = $this->task($id);
        $selectedById = [];

        foreach ($this->selectedCapabilities as $capability) {
            $selectedById[$capability->value] = true;
        }

        $missing = [];
        foreach ($task->requiredCapabilities as $capability) {
            if (! isset($selectedById[$capability->value])) {
                $missing[] = $capability;
            }
        }

        return $missing;
    }

    public function missingInputs(PlannedTaskId $id): array
    {
        $task = $this->task($id);
        $missing = [];

        foreach ($task->requiredInputs as $input) {
            if (! $input->available) {
                $missing[] = $input;
            }
        }

        return $missing;
    }

    public function topologicalBatches(): array
    {
        $graph = [];
        $inDegree = [];

        foreach ($this->tasks as $task) {
            $taskId = $task->id->value;
            $graph[$taskId] = [];
            $inDegree[$taskId] = 0;
        }

        foreach ($this->tasks as $task) {
            foreach ($task->dependencies as $dependency) {
                $graph[$dependency->value][] = $task->id->value;
                $inDegree[$task->id->value]++;
            }
        }

        $queue = [];
        foreach ($inDegree as $taskId => $count) {
            if ($count === 0) {
                $queue[] = $taskId;
            }
        }

        sort($queue);
        $batches = [];

        while ($queue !== []) {
            $batch = $queue;
            $queue = [];
            sort($batch);
            $batches[] = array_map(static fn (string $value): PlannedTaskId => new PlannedTaskId($value), $batch);

            foreach ($batch as $taskId) {
                foreach ($graph[$taskId] as $dependentId) {
                    $inDegree[$dependentId]--;
                    if ($inDegree[$dependentId] === 0) {
                        $queue[] = $dependentId;
                    }
                }
            }

            $queue = array_values(array_unique($queue));
            sort($queue);
        }

        return $batches;
    }

    public function dispatchableTasks(): array
    {
        return array_values(array_filter(
            $this->tasks,
            fn (PlannedTask $task): bool => $this->readiness($task->id) === PlannedTaskReadiness::Ready,
        ));
    }

    public function isDispatchable(): bool
    {
        return $this->status === PlannedTaskGraphStatus::Dispatchable;
    }

    public function handoff(): PlannedTaskHandoff
    {
        if (! $this->isDispatchable()) {
            throw new InvalidWorkKitTransition("Planned graph {$this->id->value} is not dispatchable.");
        }

        return PlannedTaskHandoff::fromGraph($this);
    }

    public function complete(PlannedTaskId $id): self
    {
        if ($this->readiness($id) !== PlannedTaskReadiness::Ready) {
            throw new InvalidWorkKitTransition("Planned task {$id->value} is not ready to complete.");
        }

        $replacement = $this->task($id)->withCompleted(true);

        return $this->replaceTask($replacement);
    }

    public function grantApproval(PlannedTaskId $id, string $approvalId): self
    {
        $task = $this->task($id);
        $approvals = [];
        $matched = false;

        foreach ($task->requiredApprovals as $approval) {
            if ($approval->id === $approvalId) {
                $approvals[] = $approval->withGranted(true);
                $matched = true;
                continue;
            }

            $approvals[] = $approval;
        }

        if (! $matched) {
            throw new InvalidWorkKitTransition("Approval {$approvalId} is not required for planned task {$id->value}.");
        }

        return $this->replaceTask($task->withApprovals($approvals));
    }

    public function provideInput(PlannedTaskId $id, string $inputId): self
    {
        $task = $this->task($id);
        $inputs = [];
        $matched = false;

        foreach ($task->requiredInputs as $input) {
            if ($input->id === $inputId) {
                $inputs[] = $input->withAvailable(true);
                $matched = true;
                continue;
            }

            $inputs[] = $input;
        }

        if (! $matched) {
            throw new InvalidWorkKitTransition("Input {$inputId} is not required for planned task {$id->value}.");
        }

        return $this->replaceTask($task->withInputs($inputs));
    }

    public function supersede(PlannedTaskGraphId $newId, array $tasks): self
    {
        return new self(
            id: $newId,
            workKitId: $this->workKitId,
            tasks: $tasks,
            selectedCapabilities: $this->selectedCapabilities,
            status: PlannedTaskGraphStatus::Planned,
            supersedes: $this->id,
        );
    }

    public function withStatus(PlannedTaskGraphStatus $status): self
    {
        return new self(
            id: $this->id,
            workKitId: $this->workKitId,
            tasks: $this->tasks,
            selectedCapabilities: $this->selectedCapabilities,
            status: $status,
            supersedes: $this->supersedes,
        );
    }

    private function replaceTask(PlannedTask $replacement): self
    {
        $tasks = array_map(
            static fn (PlannedTask $task): PlannedTask => $task->id->value === $replacement->id->value ? $replacement : $task,
            $this->tasks,
        );

        return new self(
            id: $this->id,
            workKitId: $this->workKitId,
            tasks: $tasks,
            selectedCapabilities: $this->selectedCapabilities,
            status: $this->status === PlannedTaskGraphStatus::Superseded
                ? PlannedTaskGraphStatus::Superseded
                : $this->statusForTasks($tasks),
            supersedes: $this->supersedes,
        );
    }

    private function statusForTasks(array $tasks): PlannedTaskGraphStatus
    {
        $candidate = new self(
            id: $this->id,
            workKitId: $this->workKitId,
            tasks: $tasks,
            selectedCapabilities: $this->selectedCapabilities,
            status: PlannedTaskGraphStatus::Planned,
            supersedes: $this->supersedes,
        );

        foreach ($candidate->tasks as $task) {
            if ($candidate->readiness($task->id) === PlannedTaskReadiness::Ready) {
                return PlannedTaskGraphStatus::Dispatchable;
            }
        }

        return PlannedTaskGraphStatus::Planned;
    }
}
