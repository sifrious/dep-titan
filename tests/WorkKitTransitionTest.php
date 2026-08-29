<?php

declare(strict_types=1);

namespace Sifrious\Titan\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sifrious\Titan\InvalidWorkKitTransition;
use Sifrious\Titan\PlanOptionDisposition;
use Sifrious\Titan\SourceRecordId;
use Sifrious\Titan\Tests\Fixtures\PlanningRecordFixtures;
use Sifrious\Titan\WorkKitAction;
use Sifrious\Titan\WorkKitCompiler;
use Sifrious\Titan\WorkKitStatus;

final class WorkKitTransitionTest extends TestCase
{
    #[Test]
    public function option_selection_is_an_explicit_transition(): void
    {
        $records = PlanningRecordFixtures::completeRecords();

        self::assertNull($records->selectedOption());
        self::assertSame(PlanOptionDisposition::Proposed, $records->planOptions[0]->disposition);

        $selected = $records->selectOption(new SourceRecordId('landing:plan-option-1'));

        self::assertSame('landing:plan-option-1', $selected->selectedOption()?->provenance->sourceId->value);
        self::assertSame(PlanOptionDisposition::Selected, $selected->planOptions[0]->disposition);
        self::assertSame(PlanOptionDisposition::Proposed, $selected->planOptions[1]->disposition);
        self::assertSame(PlanOptionDisposition::Proposed, $records->planOptions[0]->disposition);
    }

    #[Test]
    public function a_second_option_cannot_be_selected_while_another_is_selected(): void
    {
        $this->expectException(InvalidWorkKitTransition::class);

        PlanningRecordFixtures::selectedRecords()->selectOption(new SourceRecordId('landing:plan-option-2'));
    }

    #[Test]
    public function changing_selection_requires_explicit_dismissal(): void
    {
        $changed = PlanningRecordFixtures::selectedRecords()
            ->dismissOption(new SourceRecordId('landing:plan-option-1'))
            ->selectOption(new SourceRecordId('landing:plan-option-2'));

        self::assertSame('landing:plan-option-2', $changed->selectedOption()?->provenance->sourceId->value);
        self::assertSame(PlanOptionDisposition::Dismissed, $changed->planOptions[0]->disposition);
    }

    #[Test]
    public function dismissed_or_selected_options_cannot_be_reselected_without_returning_to_proposed(): void
    {
        $this->expectException(InvalidWorkKitTransition::class);

        PlanningRecordFixtures::selectedRecords()->selectOption(new SourceRecordId('landing:plan-option-1'));
    }

    #[Test]
    public function checkpoint_recording_is_explicit_and_not_inferred(): void
    {
        $records = PlanningRecordFixtures::completeRecords();

        self::assertFalse($records->checkins[0]->recorded);

        $recorded = $records->recordCheckpoint(new SourceRecordId('landing:checkin-1'));

        self::assertTrue($recorded->checkins[0]->recorded);
        self::assertFalse($records->checkins[0]->recorded);
    }

    #[Test]
    public function a_recorded_checkpoint_cannot_be_recorded_again(): void
    {
        $this->expectException(InvalidWorkKitTransition::class);

        PlanningRecordFixtures::completeRecords()
            ->recordCheckpoint(new SourceRecordId('landing:checkin-1'))
            ->recordCheckpoint(new SourceRecordId('landing:checkin-1'));
    }

    #[Test]
    public function assembled_kits_cannot_use_present_and_supersede_is_explicit(): void
    {
        $kit = (new WorkKitCompiler)->compile(PlanningRecordFixtures::incompleteInput())->workKit;

        self::assertSame([WorkKitAction::Supersede], $kit?->availableActions());
        self::assertSame(WorkKitStatus::Superseded, $kit?->apply(WorkKitAction::Supersede)->status);
    }

    #[Test]
    public function present_is_available_only_for_executable_kits(): void
    {
        $kit = (new WorkKitCompiler)->compile(PlanningRecordFixtures::executableInput())->workKit;

        self::assertSame([WorkKitAction::Present, WorkKitAction::Supersede], $kit?->availableActions());
        self::assertSame($kit, $kit?->apply(WorkKitAction::Present));
    }

    #[Test]
    public function superseded_kits_have_no_actions(): void
    {
        $kit = (new WorkKitCompiler)->compile(PlanningRecordFixtures::executableInput())->workKit
            ?->apply(WorkKitAction::Supersede);

        self::assertSame([], $kit?->availableActions());

        $this->expectException(InvalidWorkKitTransition::class);
        $kit?->apply(WorkKitAction::Present);
    }

    #[Test]
    public function unknown_options_and_checkins_cannot_transition(): void
    {
        $records = PlanningRecordFixtures::completeRecords();

        try {
            $records->selectOption(new SourceRecordId('landing:missing-option'));
            self::fail('Missing options must not be selectable.');
        } catch (InvalidWorkKitTransition $exception) {
            self::assertStringContainsString('landing:missing-option', $exception->getMessage());
        }

        $this->expectException(InvalidWorkKitTransition::class);
        $records->recordCheckpoint(new SourceRecordId('landing:missing-checkin'));
    }
}
