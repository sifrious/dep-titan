<?php

declare(strict_types=1);

namespace Sifrious\Titan\Tests;

use PHPUnit\Framework\TestCase;
use Sifrious\HarnessContractFixtures\Fixture;
use Sifrious\Titan\Plan;
use Sifrious\Titan\PlanMaterialization;
use Sifrious\Titan\PlanStep;
use Sifrious\Titan\PlanStepDisposition;

final class SharedRequestLifecycleFixtureTest extends TestCase
{
    public function test_titan_boundary_uses_the_shared_request_lifecycle_fixture(): void
    {
        $fixture = Fixture::load('request-lifecycle-v1');
        $expected = $fixture['plan'];
        $steps = array_map(
            static fn (array $step): PlanStep => new PlanStep(
                $step['id'],
                $step['id'],
                PlanStepDisposition::from($step['disposition']),
            ),
            $expected['steps'],
        );
        $plan = new Plan($expected['id'], $expected['conversation_id'], $steps);
        $discussion = new PlanMaterialization(
            $plan->id,
            $plan->steps[0]->id,
            $expected['steps'][0]['materialized_execution_request_ids'],
        );
        $execution = new PlanMaterialization(
            $plan->id,
            $plan->steps[1]->id,
            $expected['steps'][1]['materialized_execution_request_ids'],
        );

        self::assertSame([], $discussion->executionRequestReferences);
        self::assertSame($expected['steps'][1]['materialized_execution_request_ids'], $execution->executionRequestReferences);

        $multi = $fixture['multi_work_kit_variant'];
        $multiMaterialization = new PlanMaterialization($multi['plan_id'], 'multi-work-kit', $multi['execution_request_ids']);
        self::assertSame($multi['execution_request_ids'], $multiMaterialization->executionRequestReferences);
        self::assertCount($multi['distinct_origin_relation_count'], $multiMaterialization->executionRequestReferences);
    }
}
