<?php

declare(strict_types=1);

namespace Sifrious\Titan;

final readonly class PlannedTaskHandoff
{
    public array $tasks;

    public function __construct(
        public PlannedTaskGraphId $graphId,
        public WorkKitId $workKitId,
        array $tasks,
    ) {
        $this->tasks = array_values($tasks);
    }

    public static function fromGraph(PlannedTaskGraph $graph): self
    {
        return new self(
            graphId: $graph->id,
            workKitId: $graph->workKitId,
            tasks: array_map(
                static fn (PlannedTask $task): array => [
                    'id' => $task->id->value,
                    'objective' => $task->objective,
                    'outcome' => $task->outcome,
                    'first_action' => $task->firstAction,
                    'dependencies' => array_map(static fn (PlannedTaskId $id): string => $id->value, $task->dependencies),
                    'orbis_template' => $task->orbisTemplate->value,
                    'required_capabilities' => array_map(static fn (CapabilityId $id): string => $id->value, $task->requiredCapabilities),
                    'verification_steps' => $task->verificationSteps,
                    'completion_criteria' => $task->completionCriteria,
                    'failure_criteria' => $task->failureCriteria,
                ],
                $graph->dispatchableTasks(),
            ),
        );
    }
}
