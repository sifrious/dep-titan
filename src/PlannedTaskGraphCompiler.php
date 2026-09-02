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
            interrupts: $input->interrupts,
            versionAuthority: new PlannedTaskGraphVersionAuthority($input->id),
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

            if ($task->legacySemantics === null) {
                $failures[] = new PlannedTaskGraphFailure(
                    'plan_task_mapping_required',
                    "tasks.{$index}.legacy_semantics",
                    'Every planned task must preserve its Task, PlanStep, and PlanTask meanings.',
                );
            }

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

        foreach ($input->interrupts as $index => $interrupt) {
            if (! $interrupt instanceof PlanningInterrupt) {
                $failures[] = new PlannedTaskGraphFailure(
                    'interrupt_invalid',
                    "interrupts.{$index}",
                    'Every graph interrupt must use the portable interrupt contract.',
                );
                continue;
            }

            if ($interrupt->graphId->value !== $input->id->value) {
                $failures[] = new PlannedTaskGraphFailure(
                    'interrupt_graph_mismatch',
                    "interrupts.{$index}.graph_id",
                    'Every interrupt must target the graph being compiled.',
                );
            }

            if ($interrupt->taskId !== null && ! isset($byId[$interrupt->taskId->value])) {
                $failures[] = new PlannedTaskGraphFailure(
                    'interrupt_task_unknown',
                    "interrupts.{$index}.task_id",
                    'Interrupt task references must exist in the graph.',
                );
            }

            foreach ($interrupt->evidenceReferences as $evidenceIndex => $reference) {
                if (! $reference instanceof EvidenceReference) {
                    $failures[] = new PlannedTaskGraphFailure(
                        'interrupt_evidence_invalid',
                        "interrupts.{$index}.evidence_references.{$evidenceIndex}",
                        'Interrupt evidence references must be stable source references.',
                    );
                }
            }

            foreach ($interrupt->history as $historyIndex => $entry) {
                if (! $entry instanceof InterruptHistoryEntry) {
                    $failures[] = new PlannedTaskGraphFailure(
                        'interrupt_history_invalid',
                        "interrupts.{$index}.history.{$historyIndex}",
                        'Interrupt history must be append-only state entries.',
                    );
                    continue;
                }

                foreach ($entry->decisionReferences as $decisionIndex => $decisionReference) {
                    if (! $decisionReference instanceof DecisionReference) {
                        $failures[] = new PlannedTaskGraphFailure(
                            'interrupt_decision_reference_invalid',
                            "interrupts.{$index}.history.{$historyIndex}.decision_references.{$decisionIndex}",
                            'Gate decisions must reference stable Orual/Uqbar identifiers, not copied records.',
                        );
                    }
                }
            }

            if (! $this->hasValidInterruptHistory($interrupt)) {
                $failures[] = new PlannedTaskGraphFailure(
                    'interrupt_history_transition_invalid',
                    "interrupts.{$index}.history",
                    'Interrupt history must start open and only append acknowledged/resolved/waived transitions.',
                );
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

    private function hasValidInterruptHistory(PlanningInterrupt $interrupt): bool
    {
        $history = $interrupt->history;

        if ($history === []) {
            return false;
        }

        if (! $history[0] instanceof InterruptHistoryEntry || $history[0]->state !== PlanningInterruptState::Open) {
            return false;
        }

        for ($index = 1; $index < count($history); $index++) {
            if (! $history[$index - 1] instanceof InterruptHistoryEntry || ! $history[$index] instanceof InterruptHistoryEntry) {
                return false;
            }

            $previous = $history[$index - 1]->state;
            $current = $history[$index]->state;
            $allowed = match ($previous) {
                PlanningInterruptState::Open => [PlanningInterruptState::Acknowledged, PlanningInterruptState::Resolved, PlanningInterruptState::Waived],
                PlanningInterruptState::Acknowledged => [PlanningInterruptState::Resolved, PlanningInterruptState::Waived],
                PlanningInterruptState::Resolved, PlanningInterruptState::Waived => [],
            };

            if (! in_array($current, $allowed, true)) {
                return false;
            }
        }

        return true;
    }
}
