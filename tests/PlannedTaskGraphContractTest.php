<?php

declare(strict_types=1);

namespace Sifrious\Titan\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sifrious\Titan\DecisionReference;
use Sifrious\Titan\OrbisTemplateId;
use Sifrious\Titan\PlannedTaskGraphCompiler;
use Sifrious\Titan\PlannedTaskGraphReadModel;
use Sifrious\Titan\PlannedTaskReadiness;
use Sifrious\Titan\Tests\Fixtures\PlannedTaskGraphFixtures;

final class PlannedTaskGraphContractTest extends TestCase
{
    #[Test]
    public function planned_tasks_reference_orbis_by_identity_not_embedded_definition(): void
    {
        $graph = (new PlannedTaskGraphCompiler)->compile(PlannedTaskGraphFixtures::chainInput())->graph;
        self::assertNotNull($graph);

        self::assertInstanceOf(OrbisTemplateId::class, $graph->tasks[0]->orbisTemplate);
        self::assertSame('orbis:agent-template', $graph->tasks[0]->orbisTemplate->value);
        self::assertFalse(property_exists($graph->tasks[0], 'adapterConfig'));
        self::assertFalse(property_exists($graph->tasks[0], 'providerToken'));
    }

    #[Test]
    public function planning_readiness_depends_on_contract_inputs_not_runtime_labels(): void
    {
        $graph = (new PlannedTaskGraphCompiler)->compile(PlannedTaskGraphFixtures::blockedApprovalInput())->graph;
        self::assertNotNull($graph);

        self::assertSame(PlannedTaskReadiness::Blocked, $graph->readiness($graph->tasks[0]->id));
        self::assertFalse(property_exists($graph->tasks[0], 'runtimeStatus'));
        self::assertFalse(property_exists($graph->tasks[0], 'leaseId'));
    }

    #[Test]
    public function gate_resolution_references_external_decisions_by_identity_only(): void
    {
        $graph = (new PlannedTaskGraphCompiler)->compile(PlannedTaskGraphFixtures::scopeGateInput())->graph;
        self::assertNotNull($graph);

        $resolved = $graph->resolveInterrupt(
            $graph->interrupts[0]->id,
            'orual-bot',
            new \DateTimeImmutable('2026-08-29T06:11:00+00:00'),
            'Resolved by policy decision.',
            [new DecisionReference('orual', 'orual:decision-4')],
        );

        $decision = $resolved->interrupts[0]->history[1]->decisionReferences[0];
        self::assertSame('orual', $decision->authority);
        self::assertSame('orual:decision-4', $decision->referenceId);
        self::assertFalse(property_exists($decision, 'payload'));
        self::assertFalse(property_exists($decision, 'copiedRecord'));
    }

    #[Test]
    public function legacy_task_plan_step_and_plan_task_meanings_survive_without_driving_graph_order(): void
    {
        $graph = (new PlannedTaskGraphCompiler)->compile(PlannedTaskGraphFixtures::parallelInput())->graph;
        self::assertNotNull($graph);

        $model = PlannedTaskGraphReadModel::fromGraph($graph);
        $legacy = $model->tasks[0]['legacy_semantics'];

        self::assertSame('task:inspect-a', $legacy['task_id']);
        self::assertSame('plan-step:implementation', $legacy['plan_step_id']);
        self::assertSame('project:planning-record', $legacy['project_id']);
        self::assertSame(10, $legacy['position']);
        self::assertNull($legacy['done_at']);
        self::assertTrue($legacy['discipline_task']);
        self::assertFalse($legacy['note_task']);
        self::assertSame(
            [['planned-task:inspect-a', 'planned-task:inspect-b'], ['planned-task:integrate']],
            $model->topologicalBatches,
        );
    }
}
