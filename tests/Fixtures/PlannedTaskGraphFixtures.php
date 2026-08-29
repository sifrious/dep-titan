<?php

declare(strict_types=1);

namespace Sifrious\Titan\Tests\Fixtures;

use Sifrious\Titan\CapabilityId;
use Sifrious\Titan\OrbisTemplateId;
use Sifrious\Titan\PlannedTask;
use Sifrious\Titan\PlannedTaskGraphCompilationInput;
use Sifrious\Titan\PlannedTaskGraphId;
use Sifrious\Titan\PlannedTaskId;
use Sifrious\Titan\RequiredApproval;
use Sifrious\Titan\RequiredInput;
use Sifrious\Titan\ScopeFence;
use Sifrious\Titan\WorkKit;
use Sifrious\Titan\WorkKitCompiler;

final class PlannedTaskGraphFixtures
{
    public static function chainInput(): PlannedTaskGraphCompilationInput
    {
        return new PlannedTaskGraphCompilationInput(
            id: new PlannedTaskGraphId('planned-graph:chain'),
            workKit: self::workKit(),
            tasks: [
                self::task('planned-task:discover', [], true),
                self::task('planned-task:implement', ['planned-task:discover']),
                self::task('planned-task:verify', ['planned-task:implement']),
            ],
        );
    }

    public static function parallelInput(): PlannedTaskGraphCompilationInput
    {
        return new PlannedTaskGraphCompilationInput(
            id: new PlannedTaskGraphId('planned-graph:parallel'),
            workKit: self::workKit(),
            tasks: [
                self::task('planned-task:inspect-a', [], true),
                self::task('planned-task:inspect-b', [], true),
                self::task('planned-task:integrate', ['planned-task:inspect-a', 'planned-task:inspect-b']),
            ],
        );
    }

    public static function blockedApprovalInput(): PlannedTaskGraphCompilationInput
    {
        return new PlannedTaskGraphCompilationInput(
            id: new PlannedTaskGraphId('planned-graph:approval'),
            workKit: self::workKit(),
            tasks: [
                self::task(
                    id: 'planned-task:approval-gate',
                    dependencies: [],
                    explicitlyParallel: false,
                    approvals: [new RequiredApproval('approval:architect', 'Architect approval is required.', false)],
                ),
            ],
        );
    }

    public static function missingCapabilityInput(): PlannedTaskGraphCompilationInput
    {
        return new PlannedTaskGraphCompilationInput(
            id: new PlannedTaskGraphId('planned-graph:capability'),
            workKit: self::workKit(),
            tasks: [
                self::task(
                    id: 'planned-task:needs-extra-capability',
                    dependencies: [],
                    requiredCapabilities: [new CapabilityId('quain:review-proposed-change')],
                ),
            ],
        );
    }

    public static function missingInputArtifactInput(): PlannedTaskGraphCompilationInput
    {
        return new PlannedTaskGraphCompilationInput(
            id: new PlannedTaskGraphId('planned-graph:missing-input'),
            workKit: self::workKit(),
            tasks: [
                self::task(
                    id: 'planned-task:missing-artifact',
                    dependencies: [],
                    inputs: [new RequiredInput('artifact:test-report', 'Test report artifact is required.', false)],
                ),
            ],
        );
    }

    public static function cyclicInput(): PlannedTaskGraphCompilationInput
    {
        return new PlannedTaskGraphCompilationInput(
            id: new PlannedTaskGraphId('planned-graph:cycle'),
            workKit: self::workKit(),
            tasks: [
                self::task('planned-task:first', ['planned-task:second']),
                self::task('planned-task:second', ['planned-task:first']),
            ],
        );
    }

    public static function unknownDependencyInput(): PlannedTaskGraphCompilationInput
    {
        return new PlannedTaskGraphCompilationInput(
            id: new PlannedTaskGraphId('planned-graph:unknown-dependency'),
            workKit: self::workKit(),
            tasks: [
                self::task('planned-task:depends-on-missing', ['planned-task:absent']),
            ],
        );
    }

    private static function task(
        string $id,
        array $dependencies,
        bool $explicitlyParallel = false,
        ?array $requiredCapabilities = null,
        array $approvals = [],
        array $inputs = [],
    ): PlannedTask {
        return new PlannedTask(
            id: new PlannedTaskId($id),
            objective: "Objective for {$id}.",
            outcome: "Outcome for {$id}.",
            firstAction: "First action for {$id}.",
            dependencies: array_map(static fn (string $dependency): PlannedTaskId => new PlannedTaskId($dependency), $dependencies),
            scopeFence: new ScopeFence('Planned-task execution scope is explicit.'),
            requiredCapabilities: $requiredCapabilities ?? [new CapabilityId('quain:compile-work-kit')],
            requiredApprovals: $approvals,
            requiredInputs: $inputs,
            orbisTemplate: new OrbisTemplateId('orbis:agent-template'),
            verificationSteps: ["Verify {$id} output."],
            completionCriteria: ["Complete {$id} contract."],
            failureCriteria: ["Fail {$id} on unmet contract."],
            explicitlyParallel: $explicitlyParallel,
        );
    }

    private static function workKit(): WorkKit
    {
        $kit = (new WorkKitCompiler)->compile(PlanningRecordFixtures::executableInput())->workKit;

        if (! $kit instanceof WorkKit) {
            throw new \RuntimeException('Fixture work kit compilation failed.');
        }

        return $kit;
    }
}
