<?php

declare(strict_types=1);

namespace Sifrious\Titan\Tests\Fixtures;

use DateTimeImmutable;
use Sifrious\Titan\CapabilityId;
use Sifrious\Titan\DecisionReference;
use Sifrious\Titan\EvidenceReference;
use Sifrious\Titan\InterruptId;
use Sifrious\Titan\OrbisTemplateId;
use Sifrious\Titan\PlannedTask;
use Sifrious\Titan\PlannedTaskGraphCompilationInput;
use Sifrious\Titan\PlannedTaskGraphId;
use Sifrious\Titan\PlannedTaskId;
use Sifrious\Titan\PlanningInterrupt;
use Sifrious\Titan\PlanningInterruptType;
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
            interrupts: [],
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
            interrupts: [],
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
            interrupts: [],
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
            interrupts: [],
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
            interrupts: [],
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
            interrupts: [],
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
            interrupts: [],
        );
    }

    public static function scopeGateInput(): PlannedTaskGraphCompilationInput
    {
        $graphId = new PlannedTaskGraphId('planned-graph:scope-gate');

        return new PlannedTaskGraphCompilationInput(
            id: $graphId,
            workKit: self::workKit(),
            tasks: [self::task('planned-task:scope-change', [])],
            interrupts: [
                self::interrupt(
                    id: 'interrupt:scope-gate',
                    graphId: $graphId,
                    taskId: null,
                    type: PlanningInterruptType::Scope,
                    reason: 'Requested changes exceed approved scope fence.',
                    evidence: [new EvidenceReference('landing-checkin', 'checkin:scope-1')],
                ),
            ],
        );
    }

    public static function reviewGateInput(): PlannedTaskGraphCompilationInput
    {
        $graphId = new PlannedTaskGraphId('planned-graph:review-gate');

        return new PlannedTaskGraphCompilationInput(
            id: $graphId,
            workKit: self::workKit(),
            tasks: [self::task('planned-task:review-change', [])],
            interrupts: [
                self::interrupt(
                    id: 'interrupt:review-gate',
                    graphId: $graphId,
                    taskId: new PlannedTaskId('planned-task:review-change'),
                    type: PlanningInterruptType::CodeReview,
                    reason: 'Required review decision is missing.',
                    evidence: [new EvidenceReference('uqbar', 'review:missing-1')],
                ),
            ],
        );
    }

    public static function simultaneousInterruptsInput(): PlannedTaskGraphCompilationInput
    {
        $graphId = new PlannedTaskGraphId('planned-graph:simultaneous-interrupts');

        return new PlannedTaskGraphCompilationInput(
            id: $graphId,
            workKit: self::workKit(),
            tasks: [
                self::task('planned-task:targeted', []),
                self::task('planned-task:other', []),
            ],
            interrupts: [
                self::interrupt(
                    id: 'interrupt:scope-all',
                    graphId: $graphId,
                    taskId: null,
                    type: PlanningInterruptType::Scope,
                    reason: 'Scope gate applies to entire graph.',
                    evidence: [new EvidenceReference('landing-checkin', 'checkin:scope-all')],
                ),
                self::interrupt(
                    id: 'interrupt:review-targeted',
                    graphId: $graphId,
                    taskId: new PlannedTaskId('planned-task:targeted'),
                    type: PlanningInterruptType::CodeReview,
                    reason: 'Targeted review gate applies to task.',
                    evidence: [new EvidenceReference('uqbar', 'review:targeted')],
                ),
            ],
        );
    }

    public static function resolvedAndWaivedInput(): PlannedTaskGraphCompilationInput
    {
        $graphId = new PlannedTaskGraphId('planned-graph:resolved-waived');

        $resolved = self::interrupt(
            id: 'interrupt:resolved',
            graphId: $graphId,
            taskId: null,
            type: PlanningInterruptType::Scope,
            reason: 'Scope clarification required.',
            evidence: [new EvidenceReference('landing-checkin', 'checkin:scope-resolved')],
        )->resolve(
            actor: 'orual-bot',
            occurredAt: new DateTimeImmutable('2026-08-29T06:00:00+00:00'),
            note: 'Scope was approved for this revision.',
            decisionReferences: [new DecisionReference('orual', 'orual:decision-1')],
        );

        $waived = self::interrupt(
            id: 'interrupt:waived',
            graphId: $graphId,
            taskId: new PlannedTaskId('planned-task:waive-target'),
            type: PlanningInterruptType::CodeReview,
            reason: 'Review gate requested.',
            evidence: [new EvidenceReference('uqbar', 'review:waive-1')],
        )->waive(
            actor: 'uqbar-bot',
            occurredAt: new DateTimeImmutable('2026-08-29T06:01:00+00:00'),
            note: 'Gate waived by policy exception.',
            decisionReferences: [new DecisionReference('uqbar', 'uqbar:exception-1')],
        );

        return new PlannedTaskGraphCompilationInput(
            id: $graphId,
            workKit: self::workKit(),
            tasks: [self::task('planned-task:waive-target', [])],
            interrupts: [$resolved, $waived],
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

    private static function interrupt(
        string $id,
        PlannedTaskGraphId $graphId,
        ?PlannedTaskId $taskId,
        PlanningInterruptType $type,
        string $reason,
        array $evidence,
    ): PlanningInterrupt {
        return new PlanningInterrupt(
            id: new InterruptId($id),
            graphId: $graphId,
            taskId: $taskId,
            type: $type,
            reason: $reason,
            evidenceReferences: $evidence,
            createdBy: 'planner',
            createdAt: new DateTimeImmutable('2026-08-29T06:00:00+00:00'),
        );
    }
}
