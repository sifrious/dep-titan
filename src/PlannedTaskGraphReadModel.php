<?php

declare(strict_types=1);

namespace Sifrious\Titan;

final readonly class PlannedTaskGraphReadModel
{
    public array $tasks;

    public function __construct(
        public string $id,
        public string $workKitId,
        array $tasks,
        public array $topologicalBatches,
        public string $status,
        public ?string $supersedes,
    ) {
        $this->tasks = array_values($tasks);
    }

    public static function fromGraph(PlannedTaskGraph $graph): self
    {
        return new self(
            id: $graph->id->value,
            workKitId: $graph->workKitId->value,
            tasks: array_map(
                static fn (PlannedTask $task): array => [
                    'id' => $task->id->value,
                    'objective' => $task->objective,
                    'outcome' => $task->outcome,
                    'first_action' => $task->firstAction,
                    'dependencies' => array_map(static fn (PlannedTaskId $id): string => $id->value, $task->dependencies),
                    'scope_fence' => $task->scopeFence->description,
                    'required_capabilities' => array_map(static fn (CapabilityId $id): string => $id->value, $task->requiredCapabilities),
                    'required_approvals' => array_map(static fn (RequiredApproval $approval): array => [
                        'id' => $approval->id,
                        'description' => $approval->description,
                        'granted' => $approval->granted,
                    ], $task->requiredApprovals),
                    'required_inputs' => array_map(static fn (RequiredInput $input): array => [
                        'id' => $input->id,
                        'description' => $input->description,
                        'available' => $input->available,
                    ], $task->requiredInputs),
                    'verification_steps' => $task->verificationSteps,
                    'completion_criteria' => $task->completionCriteria,
                    'failure_criteria' => $task->failureCriteria,
                    'orbis_template' => $task->orbisTemplate->value,
                    'readiness' => $graph->readiness($task->id)->value,
                    'blocked_by' => array_map(static fn (PlannedTaskId $id): string => $id->value, $graph->blockedByDependencies($task->id)),
                    'missing_capabilities' => array_map(static fn (CapabilityId $id): string => $id->value, $graph->missingCapabilities($task->id)),
                    'pending_approvals' => array_map(static fn (RequiredApproval $approval): string => $approval->id, $graph->pendingApprovals($task->id)),
                    'missing_inputs' => array_map(static fn (RequiredInput $input): string => $input->id, $graph->missingInputs($task->id)),
                    'explicitly_parallel' => $task->explicitlyParallel,
                    'completed' => $task->completed,
                ],
                $graph->tasks,
            ),
            topologicalBatches: array_map(
                static fn (array $batch): array => array_map(static fn (PlannedTaskId $id): string => $id->value, $batch),
                $graph->topologicalBatches(),
            ),
            status: $graph->status->value,
            supersedes: $graph->supersedes?->value,
        );
    }
}
