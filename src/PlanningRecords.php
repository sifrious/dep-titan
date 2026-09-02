<?php

declare(strict_types=1);

namespace Sifrious\Titan;

final readonly class PlanningRecords
{
    public array $codeActions;

    public array $planCommits;

    public array $planPrs;

    public array $planOptions;

    public array $checkins;

    public function __construct(
        array $codeActions,
        array $planCommits = [],
        array $planPrs = [],
        array $planOptions = [],
        array $checkins = [],
    ) {
        $this->codeActions = array_values($codeActions);
        $this->planCommits = array_values($planCommits);
        $this->planPrs = array_values($planPrs);
        $this->planOptions = array_values($planOptions);
        $this->checkins = array_values($checkins);
    }

    public function selectedOption(): ?PlanOptionRecord
    {
        foreach ($this->planOptions as $option) {
            if ($option->disposition === PlanOptionDisposition::Selected) {
                return $option;
            }
        }

        return null;
    }

    public function option(SourceRecordId $sourceId): PlanOptionRecord
    {
        foreach ($this->planOptions as $option) {
            if ($option->provenance->sourceId->value === $sourceId->value) {
                return $option;
            }
        }

        throw new InvalidWorkKitTransition("Plan option {$sourceId->value} is not in the planning records.");
    }

    public function checkin(SourceRecordId $sourceId): CheckinRecord
    {
        foreach ($this->checkins as $checkin) {
            if ($checkin->provenance->sourceId->value === $sourceId->value) {
                return $checkin;
            }
        }

        throw new InvalidWorkKitTransition("Checkin {$sourceId->value} is not in the planning records.");
    }

    public function selectOption(SourceRecordId $sourceId): self
    {
        $option = $this->option($sourceId);

        if ($option->disposition !== PlanOptionDisposition::Proposed) {
            throw new InvalidWorkKitTransition("Plan option {$sourceId->value} cannot be selected from {$option->disposition->value}.");
        }

        if ($this->selectedOption() !== null) {
            throw new InvalidWorkKitTransition('A selected plan option must be dismissed before another option is selected.');
        }

        return $this->replaceOption($option->withDisposition(PlanOptionDisposition::Selected));
    }

    public function dismissOption(SourceRecordId $sourceId): self
    {
        $option = $this->option($sourceId);

        if ($option->disposition === PlanOptionDisposition::Dismissed) {
            throw new InvalidWorkKitTransition("Plan option {$sourceId->value} is already dismissed.");
        }

        return $this->replaceOption($option->withDisposition(PlanOptionDisposition::Dismissed));
    }

    public function recordCheckpoint(SourceRecordId $sourceId): self
    {
        $checkin = $this->checkin($sourceId);

        if ($checkin->recorded) {
            throw new InvalidWorkKitTransition("Checkin {$sourceId->value} is already recorded.");
        }

        return $this->replaceCheckin($checkin->withRecorded(true));
    }

    public function provenance(): array
    {
        $records = [
            ...$this->codeActions,
            ...$this->planCommits,
            ...$this->planPrs,
            ...$this->planOptions,
            ...$this->checkins,
        ];

        return array_map(
            static fn (CodeActionRecord|PlanCommitRecord|PlanPrRecord|PlanOptionRecord|CheckinRecord $record): SourceProvenance => $record->provenance,
            $records,
        );
    }

    private function replaceOption(PlanOptionRecord $replacement): self
    {
        return new self(
            codeActions: $this->codeActions,
            planCommits: $this->planCommits,
            planPrs: $this->planPrs,
            planOptions: array_map(
                static fn (PlanOptionRecord $option): PlanOptionRecord => $option->provenance->sourceId->value === $replacement->provenance->sourceId->value
                    ? $replacement
                    : $option,
                $this->planOptions,
            ),
            checkins: $this->checkins,
        );
    }

    private function replaceCheckin(CheckinRecord $replacement): self
    {
        return new self(
            codeActions: $this->codeActions,
            planCommits: $this->planCommits,
            planPrs: $this->planPrs,
            planOptions: $this->planOptions,
            checkins: array_map(
                static fn (CheckinRecord $checkin): CheckinRecord => $checkin->provenance->sourceId->value === $replacement->provenance->sourceId->value
                    ? $replacement
                    : $checkin,
                $this->checkins,
            ),
        );
    }
}
