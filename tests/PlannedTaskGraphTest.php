<?php

declare(strict_types=1);

namespace Sifrious\Titan\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sifrious\Titan\CapabilityId;
use Sifrious\Titan\InvalidWorkKitTransition;
use Sifrious\Titan\OrbisTemplateId;
use Sifrious\Titan\PlannedTask;
use Sifrious\Titan\PlannedTaskGraphCompilationInput;
use Sifrious\Titan\PlannedTaskGraphCompilationStatus;
use Sifrious\Titan\PlannedTaskGraphCompiler;
use Sifrious\Titan\PlannedTaskGraphId;
use Sifrious\Titan\PlannedTaskGraphReadModel;
use Sifrious\Titan\PlannedTaskGraphStatus;
use Sifrious\Titan\PlannedTaskId;
use Sifrious\Titan\PlannedTaskReadiness;
use Sifrious\Titan\RequiredApproval;
use Sifrious\Titan\RequiredInput;
use Sifrious\Titan\ScopeFence;
use Sifrious\Titan\Tests\Fixtures\PlannedTaskGraphFixtures;
use Sifrious\Titan\Tests\Fixtures\PlanningRecordFixtures;
use Sifrious\Titan\WorkKitCompiler;

final class PlannedTaskGraphTest extends TestCase
{
    #[Test]
    public function linear_chain_graph_is_topologically_ordered_and_dispatchable(): void
    {
        $result = (new PlannedTaskGraphCompiler)->compile(PlannedTaskGraphFixtures::chainInput());
        $graph = $result->graph;
        self::assertNotNull($graph);

        self::assertSame(PlannedTaskGraphCompilationStatus::Accepted, $result->status);
        self::assertTrue($result->acceptedSuccessfully());
        self::assertSame(PlannedTaskGraphStatus::Dispatchable, $graph->status);
        self::assertSame(PlannedTaskReadiness::Ready, $graph->readiness(new PlannedTaskId('planned-task:discover')));
        self::assertSame(PlannedTaskReadiness::Blocked, $graph->readiness(new PlannedTaskId('planned-task:implement')));
        self::assertSame(
            [['planned-task:discover'], ['planned-task:implement'], ['planned-task:verify']],
            array_map(
                static fn (array $batch): array => array_map(static fn (PlannedTaskId $id): string => $id->value, $batch),
                $graph->topologicalBatches(),
            ),
        );
    }

    #[Test]
    public function explicit_parallel_branches_are_preserved_in_the_same_batch(): void
    {
        $graph = (new PlannedTaskGraphCompiler)->compile(PlannedTaskGraphFixtures::parallelInput())->graph;
        self::assertNotNull($graph);

        $model = PlannedTaskGraphReadModel::fromGraph($graph);

        self::assertSame(
            [['planned-task:inspect-a', 'planned-task:inspect-b'], ['planned-task:integrate']],
            $model->topologicalBatches,
        );
        self::assertTrue($model->tasks[0]['explicitly_parallel']);
        self::assertTrue($model->tasks[1]['explicitly_parallel']);
    }

    #[Test]
    public function blocked_approval_keeps_task_blocked_until_granted(): void
    {
        $graph = (new PlannedTaskGraphCompiler)->compile(PlannedTaskGraphFixtures::blockedApprovalInput())->graph;
        self::assertNotNull($graph);

        self::assertSame(PlannedTaskReadiness::Blocked, $graph->readiness(new PlannedTaskId('planned-task:approval-gate')));
        self::assertSame([], $graph->dispatchableTasks());

        $granted = $graph->grantApproval(new PlannedTaskId('planned-task:approval-gate'), 'approval:architect');
        self::assertSame(PlannedTaskReadiness::Ready, $granted->readiness(new PlannedTaskId('planned-task:approval-gate')));
    }

    #[Test]
    public function missing_capability_keeps_task_not_ready(): void
    {
        $graph = (new PlannedTaskGraphCompiler)->compile(PlannedTaskGraphFixtures::missingCapabilityInput())->graph;
        self::assertNotNull($graph);

        self::assertSame(PlannedTaskReadiness::NotReady, $graph->readiness(new PlannedTaskId('planned-task:needs-extra-capability')));
        self::assertSame(['quain:review-proposed-change'], array_map(
            static fn (CapabilityId $id): string => $id->value,
            $graph->missingCapabilities(new PlannedTaskId('planned-task:needs-extra-capability')),
        ));
    }

    #[Test]
    public function missing_input_keeps_task_not_ready_until_artifact_is_available(): void
    {
        $graph = (new PlannedTaskGraphCompiler)->compile(PlannedTaskGraphFixtures::missingInputArtifactInput())->graph;
        self::assertNotNull($graph);

        self::assertSame(PlannedTaskReadiness::NotReady, $graph->readiness(new PlannedTaskId('planned-task:missing-artifact')));
        self::assertSame(['artifact:test-report'], array_map(
            static fn (RequiredInput $input): string => $input->id,
            $graph->missingInputs(new PlannedTaskId('planned-task:missing-artifact')),
        ));

        $provided = $graph->provideInput(new PlannedTaskId('planned-task:missing-artifact'), 'artifact:test-report');
        self::assertSame(PlannedTaskReadiness::Ready, $provided->readiness(new PlannedTaskId('planned-task:missing-artifact')));
    }

    #[Test]
    public function cycle_and_missing_dependencies_are_rejected_before_dispatchability(): void
    {
        $cycleResult = (new PlannedTaskGraphCompiler)->compile(PlannedTaskGraphFixtures::cyclicInput());
        $missingResult = (new PlannedTaskGraphCompiler)->compile(PlannedTaskGraphFixtures::unknownDependencyInput());

        self::assertSame(PlannedTaskGraphCompilationStatus::Rejected, $cycleResult->status);
        self::assertSame(['dependency_cycle'], array_map(static fn ($failure): string => $failure->code, $cycleResult->failures));
        self::assertFalse($cycleResult->acceptedSuccessfully());
        self::assertNull($cycleResult->graph);

        self::assertSame(PlannedTaskGraphCompilationStatus::Rejected, $missingResult->status);
        self::assertSame(['dependency_unknown'], array_map(static fn ($failure): string => $failure->code, $missingResult->failures));
        self::assertFalse($missingResult->acceptedSuccessfully());
        self::assertNull($missingResult->graph);
    }

    #[Test]
    public function completed_dependencies_transition_downstream_tasks_to_ready(): void
    {
        $graph = (new PlannedTaskGraphCompiler)->compile(PlannedTaskGraphFixtures::chainInput())->graph;
        self::assertNotNull($graph);

        $advanced = $graph
            ->complete(new PlannedTaskId('planned-task:discover'))
            ->complete(new PlannedTaskId('planned-task:implement'));

        self::assertSame(PlannedTaskReadiness::Completed, $advanced->readiness(new PlannedTaskId('planned-task:discover')));
        self::assertSame(PlannedTaskReadiness::Completed, $advanced->readiness(new PlannedTaskId('planned-task:implement')));
        self::assertSame(PlannedTaskReadiness::Ready, $advanced->readiness(new PlannedTaskId('planned-task:verify')));
    }

    #[Test]
    public function non_ready_tasks_cannot_be_completed(): void
    {
        $this->expectException(InvalidWorkKitTransition::class);

        $graph = (new PlannedTaskGraphCompiler)->compile(PlannedTaskGraphFixtures::chainInput())->graph;
        $graph?->complete(new PlannedTaskId('planned-task:implement'));
    }

    #[Test]
    public function graph_handoff_contains_only_dependency_and_contract_ready_tasks(): void
    {
        $graph = (new PlannedTaskGraphCompiler)->compile(PlannedTaskGraphFixtures::chainInput())->graph;
        self::assertNotNull($graph);

        $handoff = $graph->handoff();

        self::assertSame('planned-graph:chain', $handoff->graphId->value);
        self::assertCount(1, $handoff->tasks);
        self::assertSame('planned-task:discover', $handoff->tasks[0]['id']);
        self::assertSame('orbis:agent-template', $handoff->tasks[0]['orbis_template']);
    }

    #[Test]
    public function graph_revisions_supersede_prior_versions_without_rewriting_history(): void
    {
        $first = (new PlannedTaskGraphCompiler)->compile(PlannedTaskGraphFixtures::chainInput())->graph;
        self::assertNotNull($first);

        $second = $first->supersede(
            new PlannedTaskGraphId('planned-graph:chain:v2'),
            [...$first->tasks],
        );

        self::assertSame('planned-graph:chain', $second->supersedes?->value);
        self::assertSame('planned-graph:chain', $first->id->value);
        self::assertNull($first->supersedes);
    }

    #[Test]
    public function planning_readiness_states_do_not_expose_logres_runtime_labels(): void
    {
        $states = array_map(static fn (PlannedTaskReadiness $state): string => $state->value, PlannedTaskReadiness::cases());

        self::assertSame(['ready', 'blocked', 'not_ready', 'completed'], $states);
        self::assertNotContains('leased', $states);
        self::assertNotContains('running', $states);
        self::assertNotContains('retrying', $states);
        self::assertNotContains('canceling', $states);
    }

    #[Test]
    public function compiling_from_non_executable_work_kit_is_rejected(): void
    {
        $incompleteKit = (new WorkKitCompiler)->compile(PlanningRecordFixtures::incompleteInput())->workKit;
        self::assertNotNull($incompleteKit);

        $result = (new PlannedTaskGraphCompiler)->compile(
            new PlannedTaskGraphCompilationInput(
                id: new PlannedTaskGraphId('planned-graph:incomplete-work-kit'),
                workKit: $incompleteKit,
                tasks: [
                    new PlannedTask(
                        id: new PlannedTaskId('planned-task:single'),
                        objective: 'Objective.',
                        outcome: 'Outcome.',
                        firstAction: 'First action.',
                        dependencies: [],
                        scopeFence: new ScopeFence('Explicit scope.'),
                        requiredCapabilities: [new CapabilityId('quain:compile-work-kit')],
                        requiredApprovals: [new RequiredApproval('approval:none', 'No approval required.', true)],
                        requiredInputs: [new RequiredInput('artifact:none', 'No input required.', true)],
                        orbisTemplate: new OrbisTemplateId('orbis:agent-template'),
                        verificationSteps: ['Verify outcome.'],
                        completionCriteria: ['Complete objective.'],
                        failureCriteria: ['Fail if objective is unmet.'],
                    ),
                ],
            ),
        );

        self::assertSame(PlannedTaskGraphCompilationStatus::Rejected, $result->status);
        self::assertSame(['work_kit_not_executable'], array_map(static fn ($failure): string => $failure->code, $result->failures));
    }
}
