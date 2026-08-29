<?php

declare(strict_types=1);

namespace Sifrious\Titan;

final readonly class PlannedTaskGraphCompiler
{
    public function compile(PlannedTaskGraphCompilationInput $input): PlannedTaskGraphCompilationResult
    {
        $failures = $this->validate($input);

        if ($failures !== []) {
            return new PlannedTaskGraphCompilationResult(PlannedTaskGraphCompilationStatus::Rejected, null, $failures);
        }

        $graph = new PlannedTaskGraph(
            id: $input->id,
            workKitId: $input->workKit->id,
            tasks: $input->tasks,
            selectedCapabilities: $input->workKit->selectedCapabilities,
            status: PlannedTaskGraphStatus::Planned,
            supersedes: $input->supersedes,
        );

        if ($this->isDispatchable($graph)) {
            $graph = $graph->withStatus(PlannedTaskGraphStatus::Dispatchable);
        }

        return new PlannedTaskGraphCompilationResult(PlannedTaskGraphCompilationStatus::Accepted, $graph);
    }

    private function validate(PlannedTaskGraphCompilationInput $input): array
    {
        $failures = [];
        $byId = [];

        if (! $input->workKit->isExecutable()) {
            $failures[] = new PlannedTaskGraphFailure(
                'work_kit_not_executable',
                'work_kit.status',
                'Only executable work kits can compile planned-task graphs.',
            );
        }

        if ($input->tasks === []) {
            $failures[] = new PlannedTaskGraphFailure(
                'tasks_required',
                'tasks',
                'A planned-task graph requires at least one planned task.',
            );
        }

        foreach ($input->tasks as $index => $task) {
            if (! $task instanceof PlannedTask) {
                $failures[] = new PlannedTaskGraphFailure(
                    'task_invalid',
                    "tasks.{$index}",
                    'Every graph entry must be a planned task.',
                );
                continue;
            }

            if (isset($byId[$task->id->value])) {
                $failures[] = new PlannedTaskGraphFailure(
                    'task_identity_duplicate',
                    "tasks.{$index}.id",
                    'Planned task identities must be unique within a graph.',
                );
            }

            $byId[$task->id->value] = $task;

            foreach ($task->dependencies as $dependencyIndex => $dependency) {
                if (! $dependency instanceof PlannedTaskId) {
                    $failures[] = new PlannedTaskGraphFailure(
                        'dependency_invalid',
                        "tasks.{$index}.dependencies.{$dependencyIndex}",
                        'Dependencies must reference planned task identities.',
                    );
                }
            }

            foreach ($task->requiredCapabilities as $capabilityIndex => $capability) {
                if (! $capability instanceof CapabilityId) {
                    $failures[] = new PlannedTaskGraphFailure(
                        'capability_identity_required',
                        "tasks.{$index}.required_capabilities.{$capabilityIndex}",
                        'Required capabilities must be Quain identities.',
                    );
                }
            }

            foreach ($task->requiredApprovals as $approvalIndex => $approval) {
                if (! $approval instanceof RequiredApproval) {
                    $failures[] = new PlannedTaskGraphFailure(
                        'approval_invalid',
                        "tasks.{$index}.required_approvals.{$approvalIndex}",
                        'Required approvals must use portable approval contracts.',
                    );
                }
            }

            foreach ($task->requiredInputs as $inputIndex => $requiredInput) {
                if (! $requiredInput instanceof RequiredInput) {
                    $failures[] = new PlannedTaskGraphFailure(
                        'input_invalid',
                        "tasks.{$index}.required_inputs.{$inputIndex}",
                        'Required inputs must use portable input contracts.',
                    );
                }
            }
        }

        foreach ($input->tasks as $index => $task) {
            if (! $task instanceof PlannedTask) {
                continue;
            }

            foreach ($task->dependencies as $dependency) {
                if (! isset($byId[$dependency->value])) {
                    $failures[] = new PlannedTaskGraphFailure(
                        'dependency_unknown',
                        "tasks.{$index}.dependencies",
                        'Dependencies must reference tasks in the same graph.',
                    );
                }
            }
        }

        if ($this->containsCycle($byId)) {
            $failures[] = new PlannedTaskGraphFailure(
                'dependency_cycle',
                'tasks',
                'Planned task dependencies must form an acyclic graph.',
            );
        }

        return $failures;
    }

    private function isDispatchable(PlannedTaskGraph $graph): bool
    {
        foreach ($graph->tasks as $task) {
            if ($graph->readiness($task->id) === PlannedTaskReadiness::Ready) {
                return true;
            }
        }

        return false;
    }

    private function containsCycle(array $tasks): bool
    {
        $visiting = [];
        $visited = [];

        $visit = function (string $id) use (&$visit, &$visiting, &$visited, $tasks): bool {
            if (isset($visiting[$id])) {
                return true;
            }

            if (isset($visited[$id]) || ! isset($tasks[$id])) {
                return false;
            }

            $visiting[$id] = true;

            foreach ($tasks[$id]->dependencies as $dependency) {
                if ($visit($dependency->value)) {
                    return true;
                }
            }

            unset($visiting[$id]);
            $visited[$id] = true;

            return false;
        };

        foreach (array_keys($tasks) as $id) {
            if ($visit($id)) {
                return true;
            }
        }

        return false;
    }
}
