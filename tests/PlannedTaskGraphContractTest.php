<?php

declare(strict_types=1);

namespace Sifrious\Titan\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sifrious\Titan\OrbisTemplateId;
use Sifrious\Titan\PlannedTaskGraphCompiler;
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
}
