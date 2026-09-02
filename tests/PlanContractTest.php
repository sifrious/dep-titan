<?php
declare(strict_types=1);
namespace Sifrious\Titan\Tests;
use PHPUnit\Framework\TestCase;
use Sifrious\Titan\Plan;
use Sifrious\Titan\PlanMaterialization;
use Sifrious\Titan\PlanStep;
use Sifrious\Titan\PlanStepDisposition;
use Sifrious\ReferenceContract\CrossPackageReference;
final class PlanContractTest extends TestCase
{
    public function test_steps_explicitly_materialize_to_zero_one_or_many_requests(): void
    {
        $discussion = new PlanStep('step:1', 'Resolve scope.', PlanStepDisposition::Deliberation);
        $implementation = new PlanStep('step:2', 'Update two repositories.', PlanStepDisposition::Execution, ['Tests pass']);
        $plan = new Plan('plan:1', new CrossPackageReference('sifrious/elwin', 'conversation', 'conversation:1', '2'), [$discussion, $implementation]);
        self::assertSame([], (new PlanMaterialization($plan->id, $discussion->id, []))->executionRequestReferences);
        self::assertCount(2, (new PlanMaterialization($plan->id, $implementation->id, [
            new CrossPackageReference('sifrious/logres', 'execution-request', 'request:1'),
            new CrossPackageReference('sifrious/logres', 'execution-request', 'request:2'),
        ]))->executionRequestReferences);
    }
}
