<?php

declare(strict_types=1);

namespace Sifrious\Titan;

final readonly class PlannedTaskGraphReadModel
{
    public array $tasks;

    public array $interrupts;

    public function __construct(
        public string $id,
        public string $workKitId,
        array $tasks,
        array $interrupts,
        public array $topologicalBatches,
        public string $status,
        public ?string $supersedes,
    ) {
        $this->tasks = array_values($tasks);
        $this->interrupts = array_values($interrupts);
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
                    'active_interrupts' => array_map(static fn (PlanningInterrupt $interrupt): string => $interrupt->id->value, $graph->activeInterrupts($task->id)),
                    'explicitly_parallel' => $task->explicitlyParallel,
                    'completed' => $task->completed,
                    'completion_proof' => $task->completionProof === null ? null : [
                        'verification_plan_version' => $task->completionProof->verificationPlanVersion,
                        'evidence_references' => array_map(static fn (EvidenceReference $reference): array => [
                            'source' => $reference->source,
                            'reference_id' => $reference->referenceId,
                        ], $task->completionProof->evidenceReferences),
                        'completed_at' => $task->completionProof->completedAt->format(\DateTimeInterface::ATOM),
                        'verification_passed' => $task->completionProof->verificationPassed,
                        'completion_criteria_satisfied' => $task->completionProof->completionCriteriaSatisfied,
                        'failure_criteria_triggered' => $task->completionProof->failureCriteriaTriggered,
                    ],
                    'legacy_semantics' => $task->legacySemantics === null ? null : [
                        'task_id' => $task->legacySemantics->taskId,
                        'plan_step_id' => $task->legacySemantics->planStepId,
                        'project_id' => $task->legacySemantics->projectId,
                        'position' => $task->legacySemantics->position,
                        'done_at' => $task->legacySemantics->doneAt?->format(\DateTimeInterface::ATOM),
                        'discipline_task' => $task->legacySemantics->disciplineTask,
                        'note_task' => $task->legacySemantics->noteTask,
                    ],
                ],
                $graph->tasks,
            ),
            interrupts: array_map(
                static fn (PlanningInterrupt $interrupt): array => [
                    'id' => $interrupt->id->value,
                    'graph_id' => $interrupt->graphId->value,
                    'task_id' => $interrupt->taskId?->value,
                    'type' => $interrupt->type->value,
                    'reason' => $interrupt->reason,
                    'state' => $interrupt->state()->value,
                    'created_by' => $interrupt->createdBy,
                    'created_at' => $interrupt->createdAt->format(\DateTimeInterface::ATOM),
                    'evidence_references' => array_map(
                        static fn (EvidenceReference $reference): array => [
                            'source' => $reference->source,
                            'reference_id' => $reference->referenceId,
                        ],
                        $interrupt->evidenceReferences,
                    ),
                    'history' => array_map(
                        static fn (InterruptHistoryEntry $entry): array => [
                            'state' => $entry->state->value,
                            'actor' => $entry->actor,
                            'occurred_at' => $entry->occurredAt->format(\DateTimeInterface::ATOM),
                            'note' => $entry->note,
                            'decision_references' => array_map(
                                static fn (DecisionReference $reference): array => [
                                    'authority' => $reference->authority,
                                    'reference_id' => $reference->referenceId,
                                ],
                                $entry->decisionReferences,
                            ),
                        ],
                        $interrupt->history,
                    ),
                ],
                $graph->interrupts,
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
