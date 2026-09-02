<?php

declare(strict_types=1);

namespace Sifrious\Titan\Tests;

use PHPUnit\Framework\TestCase;
use Sifrious\HarnessContractFixtures\Fixture;

final class SharedRequestLifecycleFixtureTest extends TestCase
{
    public function test_titan_boundary_uses_the_shared_request_lifecycle_fixture(): void
    {
        $fixture = Fixture::load('request-lifecycle-v1');
        $steps = $fixture['plan']['steps'];

        self::assertSame([], $steps[0]['materialized_execution_request_ids']);
        self::assertNotEmpty($steps[1]['materialized_execution_request_ids']);
        self::assertCount(
            $fixture['multi_work_kit_variant']['distinct_origin_relation_count'],
            $fixture['multi_work_kit_variant']['execution_request_ids'],
        );
    }
}
