<?php

declare(strict_types=1);

namespace Sifrious\Titan\Tests\Fixtures;

use Sifrious\Titan\CapabilityId;
use Sifrious\Titan\CheckinRecord;
use Sifrious\Titan\CodeActionRecord;
use Sifrious\Titan\DeclaredDependency;
use Sifrious\Titan\PlanCommitRecord;
use Sifrious\Titan\PlanningRecordKind;
use Sifrious\Titan\PlanningRecords;
use Sifrious\Titan\PlanOptionRecord;
use Sifrious\Titan\PlanPrRecord;
use Sifrious\Titan\ScopeFence;
use Sifrious\Titan\SourceProvenance;
use Sifrious\Titan\SourceRecordId;
use Sifrious\Titan\WorkKitCompilationInput;
use Sifrious\Titan\WorkKitId;

final class PlanningRecordFixtures
{
    public static function completeRecords(): PlanningRecords
    {
        return new PlanningRecords(
            codeActions: [self::codeAction()],
            planCommits: [self::planCommit()],
            planPrs: [self::planPr()],
            planOptions: [
                self::planOption('landing:plan-option-1', 'Ship the work-kit compiler.', 'Smallest coherent compile path.'),
                self::planOption('landing:plan-option-2', 'Defer compilation.', 'Wait for catalogue extraction.'),
            ],
            checkins: [self::checkin()],
        );
    }

    public static function selectedRecords(): PlanningRecords
    {
        return self::completeRecords()->selectOption(new SourceRecordId('landing:plan-option-1'));
    }

    public static function executableInput(): WorkKitCompilationInput
    {
        return self::input(self::selectedRecords(), [
            new DeclaredDependency('dep:selected-option', 'A plan option is selected.', true),
            new DeclaredDependency('dep:planning-checkpoint', 'The planning checkpoint is recorded.', true),
        ]);
    }

    public static function incompleteInput(): WorkKitCompilationInput
    {
        return self::input(self::selectedRecords(), [
            new DeclaredDependency('dep:selected-option', 'A plan option is selected.', true),
            new DeclaredDependency('dep:catalogue-cutover', 'Landing catalogue persistence has been extracted.', false),
        ]);
    }

    public static function input(PlanningRecords $records, array $dependencies): WorkKitCompilationInput
    {
        return new WorkKitCompilationInput(
            id: new WorkKitId('workkit:atlas-compiler'),
            records: $records,
            dependencies: $dependencies,
            scopeFence: new ScopeFence(
                'Compile portable work kits only.',
                ['Planning-record compilation', 'Explicit work-kit transitions'],
                ['Task graphs', 'Interrupts', 'Repository bindings', 'Landing UI', 'Logres runtime'],
            ),
            selectedCapabilities: [new CapabilityId('quain:compile-work-kit')],
            verificationSteps: ['Contract tests prove compilation, incomplete-dependency refusal, and explicit transitions.'],
            completionCriteria: ['A compiled work kit names outcome, first action, dependencies, scope, verification, and completion.'],
            failureCriteria: ['Dependency-incomplete work is presented as executable.'],
        );
    }

    public static function codeAction(): CodeActionRecord
    {
        return new CodeActionRecord(
            provenance: self::provenance(PlanningRecordKind::CodeAction, 'landing:code-action-1'),
            firstAction: 'Map Landing planning records onto an explicit work-kit contract.',
            intent: 'Prepare executable work from planning evidence.',
        );
    }

    public static function planCommit(): PlanCommitRecord
    {
        return new PlanCommitRecord(
            provenance: self::provenance(PlanningRecordKind::PlanCommit, 'landing:plan-commit-1'),
            intendedChange: 'Add the Titan work-kit compiler without observed Git history ownership.',
        );
    }

    public static function planPr(): PlanPrRecord
    {
        return new PlanPrRecord(
            provenance: self::provenance(PlanningRecordKind::PlanPr, 'landing:plan-pr-1'),
            intendedReview: 'Review the compiled work-kit contract before Logres dispatch.',
        );
    }

    public static function planOption(string $sourceId, string $outcome, string $rationale): PlanOptionRecord
    {
        return new PlanOptionRecord(
            provenance: self::provenance(PlanningRecordKind::PlanOption, $sourceId),
            outcome: $outcome,
            rationale: $rationale,
        );
    }

    public static function checkin(): CheckinRecord
    {
        return new CheckinRecord(
            provenance: self::provenance(PlanningRecordKind::Checkin, 'landing:checkin-1'),
            checkpoint: 'Planning records are assembled for compilation.',
        );
    }

    public static function provenance(PlanningRecordKind $kind, string $sourceId): SourceProvenance
    {
        return new SourceProvenance($kind, new SourceRecordId($sourceId));
    }
}
