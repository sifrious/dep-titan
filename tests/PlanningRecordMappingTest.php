<?php

declare(strict_types=1);

namespace Sifrious\Titan\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sifrious\Titan\CheckinRecord;
use Sifrious\Titan\CodeActionRecord;
use Sifrious\Titan\PlanCommitRecord;
use Sifrious\Titan\PlanningRecordKind;
use Sifrious\Titan\PlanOptionRecord;
use Sifrious\Titan\PlanPrRecord;
use Sifrious\Titan\Tests\Fixtures\PlanningRecordFixtures;
use Sifrious\Titan\WorkKitCompiler;

final class PlanningRecordMappingTest extends TestCase
{
    #[Test]
    public function each_landing_planning_record_maps_to_an_explicit_titan_concept(): void
    {
        $kit = (new WorkKitCompiler)->compile(PlanningRecordFixtures::executableInput())->workKit;
        $kinds = array_map(static fn ($provenance): string => $provenance->kind->value, $kit?->sourceRecords ?? []);

        self::assertContains(PlanningRecordKind::CodeAction->value, $kinds);
        self::assertContains(PlanningRecordKind::PlanCommit->value, $kinds);
        self::assertContains(PlanningRecordKind::PlanPr->value, $kinds);
        self::assertContains(PlanningRecordKind::PlanOption->value, $kinds);
        self::assertContains(PlanningRecordKind::Checkin->value, $kinds);
        self::assertSame(
            PlanningRecordFixtures::codeAction()->firstAction,
            $kit?->firstAction,
        );
        self::assertSame(
            PlanningRecordFixtures::selectedRecords()->selectedOption()?->outcome,
            $kit?->outcome,
        );
    }

    #[Test]
    public function code_action_is_work_preparation_not_runtime_or_ui(): void
    {
        $record = PlanningRecordFixtures::codeAction();

        self::assertInstanceOf(CodeActionRecord::class, $record);
        self::assertSame(PlanningRecordKind::CodeAction, $record->provenance->kind);
        self::assertSame('landing:code-action-1', $record->provenance->sourceId->value);
        self::assertFalse(property_exists($record, 'status'));
        self::assertFalse(property_exists($record, 'providerRunId'));
    }

    #[Test]
    public function plan_commit_and_plan_pr_are_intended_work_not_observed_history(): void
    {
        $commit = PlanningRecordFixtures::planCommit();
        $pr = PlanningRecordFixtures::planPr();

        self::assertInstanceOf(PlanCommitRecord::class, $commit);
        self::assertInstanceOf(PlanPrRecord::class, $pr);
        self::assertFalse(property_exists($commit, 'sha'));
        self::assertFalse(property_exists($commit, 'observedCommit'));
        self::assertFalse(property_exists($pr, 'providerPrNumber'));
        self::assertFalse(property_exists($pr, 'observedPullRequest'));
        self::assertNotSame('', $commit->intendedChange);
        self::assertNotSame('', $pr->intendedReview);
    }

    #[Test]
    public function plan_option_remains_an_alternative_until_selected(): void
    {
        $option = PlanningRecordFixtures::planOption(
            'landing:plan-option-1',
            'Ship the work-kit compiler.',
            'Smallest coherent compile path.',
        );

        self::assertInstanceOf(PlanOptionRecord::class, $option);
        self::assertSame('proposed', $option->disposition->value);
        self::assertNotSame('executable', $option->disposition->value);
    }

    #[Test]
    public function checkin_is_a_planning_checkpoint_not_logres_telemetry(): void
    {
        $checkin = PlanningRecordFixtures::checkin();

        self::assertInstanceOf(CheckinRecord::class, $checkin);
        self::assertFalse($checkin->recorded);
        self::assertFalse(property_exists($checkin, 'runtimeStatus'));
        self::assertFalse(property_exists($checkin, 'leasedAt'));
        self::assertFalse(property_exists($checkin, 'providerEvent'));
    }

    #[Test]
    public function landing_adapter_residue_is_documented_outside_titan_source(): void
    {
        $mapping = file_get_contents(dirname(__DIR__).'/docs/landing-adapters.md');

        self::assertIsString($mapping);
        self::assertStringContainsString('CodeAction', $mapping);
        self::assertStringContainsString('PlanCommit', $mapping);
        self::assertStringContainsString('PlanPr', $mapping);
        self::assertStringContainsString('PlanOption', $mapping);
        self::assertStringContainsString('Checkin', $mapping);
        self::assertStringContainsString('Landing adapter', $mapping);
        self::assertStringContainsString('Eloquent', $mapping);
        self::assertStringContainsString('MME-873', $mapping);
    }
}
