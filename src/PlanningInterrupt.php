<?php

declare(strict_types=1);

namespace Sifrious\Titan;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class PlanningInterrupt
{
    public array $evidenceReferences;

    public array $history;

    public function __construct(
        public InterruptId $id,
        public PlannedTaskGraphId $graphId,
        public ?PlannedTaskId $taskId,
        public PlanningInterruptType $type,
        public string $reason,
        array $evidenceReferences,
        public string $createdBy,
        public DateTimeImmutable $createdAt,
        array $history = [],
    ) {
        if (trim($reason) === '') {
            throw new InvalidArgumentException('Interrupt reason is required.');
        }

        if (trim($createdBy) === '') {
            throw new InvalidArgumentException('Interrupt creator is required.');
        }

        $this->evidenceReferences = array_values($evidenceReferences);
        $normalizedHistory = array_values($history);

        if ($normalizedHistory === []) {
            $normalizedHistory = [
                new InterruptHistoryEntry(
                    state: PlanningInterruptState::Open,
                    actor: $createdBy,
                    occurredAt: $createdAt,
                    note: $reason,
                ),
            ];
        }

        $this->history = $normalizedHistory;
    }

    public function state(): PlanningInterruptState
    {
        return $this->history[array_key_last($this->history)]->state;
    }

    public function isBlocking(): bool
    {
        return in_array($this->state(), [PlanningInterruptState::Open, PlanningInterruptState::Acknowledged], true);
    }

    public function affects(PlannedTaskId $taskId): bool
    {
        if (! $this->isBlocking()) {
            return false;
        }

        if ($this->taskId === null) {
            return true;
        }

        return $this->taskId->value === $taskId->value;
    }

    public function acknowledge(string $actor, DateTimeImmutable $occurredAt, string $note): self
    {
        if ($this->state() !== PlanningInterruptState::Open) {
            throw new InvalidWorkKitTransition("Interrupt {$this->id->value} cannot be acknowledged from {$this->state()->value}.");
        }

        return $this->append(
            new InterruptHistoryEntry(
                state: PlanningInterruptState::Acknowledged,
                actor: $actor,
                occurredAt: $occurredAt,
                note: $note,
            ),
        );
    }

    public function resolve(string $actor, DateTimeImmutable $occurredAt, string $note, array $decisionReferences): self
    {
        if (in_array($this->state(), [PlanningInterruptState::Resolved, PlanningInterruptState::Waived], true)) {
            throw new InvalidWorkKitTransition("Interrupt {$this->id->value} is already terminal.");
        }

        if ($decisionReferences === []) {
            throw new InvalidArgumentException('Resolving an interrupt requires at least one stable decision reference.');
        }

        return $this->append(
            new InterruptHistoryEntry(
                state: PlanningInterruptState::Resolved,
                actor: $actor,
                occurredAt: $occurredAt,
                note: $note,
                decisionReferences: $decisionReferences,
            ),
        );
    }

    public function waive(string $actor, DateTimeImmutable $occurredAt, string $note, array $decisionReferences): self
    {
        if (in_array($this->state(), [PlanningInterruptState::Resolved, PlanningInterruptState::Waived], true)) {
            throw new InvalidWorkKitTransition("Interrupt {$this->id->value} is already terminal.");
        }

        if ($decisionReferences === []) {
            throw new InvalidArgumentException('Waiving an interrupt requires at least one stable decision reference.');
        }

        return $this->append(
            new InterruptHistoryEntry(
                state: PlanningInterruptState::Waived,
                actor: $actor,
                occurredAt: $occurredAt,
                note: $note,
                decisionReferences: $decisionReferences,
            ),
        );
    }

    private function append(InterruptHistoryEntry $entry): self
    {
        return new self(
            id: $this->id,
            graphId: $this->graphId,
            taskId: $this->taskId,
            type: $this->type,
            reason: $this->reason,
            evidenceReferences: $this->evidenceReferences,
            createdBy: $this->createdBy,
            createdAt: $this->createdAt,
            history: [...$this->history, $entry],
        );
    }
}
