<?php

declare(strict_types=1);

namespace Sifrious\Titan\Tests;

use DateTimeImmutable;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sifrious\Titan\DecisionReference;
use Sifrious\Titan\EvidenceReference;
use Sifrious\Titan\InterruptId;
use Sifrious\Titan\InvalidWorkKitTransition;
use Sifrious\Titan\PlannedTaskGraphId;
use Sifrious\Titan\PlannedTaskId;
use Sifrious\Titan\PlanningInterrupt;
use Sifrious\Titan\PlanningInterruptState;
use Sifrious\Titan\PlanningInterruptType;

final class PlanningInterruptContractTest extends TestCase
{
    #[Test]
    public function interrupt_contract_names_identity_target_type_reason_evidence_and_resolution_state(): void
    {
        $interrupt = $this->scopeInterrupt();

        self::assertSame('interrupt:scope-1', $interrupt->id->value);
        self::assertSame('planned-graph:interrupt-contract', $interrupt->graphId->value);
        self::assertSame('planned-task:target', $interrupt->taskId?->value);
        self::assertSame(PlanningInterruptType::Scope, $interrupt->type);
        self::assertSame('Scope exceeded approved boundary.', $interrupt->reason);
        self::assertSame('planner', $interrupt->createdBy);
        self::assertSame(PlanningInterruptState::Open, $interrupt->state());
        self::assertSame('uqbar', $interrupt->evidenceReferences[0]->source);
    }

    #[Test]
    public function acknowledge_resolve_and_waive_are_append_only_history_entries(): void
    {
        $interrupt = $this->scopeInterrupt();
        $acknowledged = $interrupt->acknowledge('operator', new DateTimeImmutable('2026-08-29T06:20:00+00:00'), 'Acknowledged for review.');
        $resolved = $acknowledged->resolve(
            actor: 'orual-bot',
            occurredAt: new DateTimeImmutable('2026-08-29T06:21:00+00:00'),
            note: 'Scope confirmed.',
            decisionReferences: [new DecisionReference('orual', 'orual:scope-1')],
        );

        self::assertCount(1, $interrupt->history);
        self::assertCount(2, $acknowledged->history);
        self::assertCount(3, $resolved->history);
        self::assertSame(PlanningInterruptState::Open, $interrupt->state());
        self::assertSame(PlanningInterruptState::Acknowledged, $acknowledged->state());
        self::assertSame(PlanningInterruptState::Resolved, $resolved->state());
    }

    #[Test]
    public function resolve_and_waive_require_stable_decision_references(): void
    {
        $interrupt = $this->scopeInterrupt();

        $this->expectException(InvalidArgumentException::class);
        $interrupt->resolve(
            actor: 'orual-bot',
            occurredAt: new DateTimeImmutable('2026-08-29T06:21:00+00:00'),
            note: 'Scope confirmed.',
            decisionReferences: [],
        );
    }

    #[Test]
    public function terminal_interrupts_cannot_transition_again(): void
    {
        $resolved = $this->scopeInterrupt()->resolve(
            actor: 'orual-bot',
            occurredAt: new DateTimeImmutable('2026-08-29T06:21:00+00:00'),
            note: 'Scope confirmed.',
            decisionReferences: [new DecisionReference('orual', 'orual:scope-1')],
        );

        $this->expectException(InvalidWorkKitTransition::class);
        $resolved->waive(
            actor: 'uqbar-bot',
            occurredAt: new DateTimeImmutable('2026-08-29T06:22:00+00:00'),
            note: 'Attempted waiver after resolve.',
            decisionReferences: [new DecisionReference('uqbar', 'uqbar:scope-2')],
        );
    }

    private function scopeInterrupt(): PlanningInterrupt
    {
        return new PlanningInterrupt(
            id: new InterruptId('interrupt:scope-1'),
            graphId: new PlannedTaskGraphId('planned-graph:interrupt-contract'),
            taskId: new PlannedTaskId('planned-task:target'),
            type: PlanningInterruptType::Scope,
            reason: 'Scope exceeded approved boundary.',
            evidenceReferences: [new EvidenceReference('uqbar', 'uqbar:finding-1')],
            createdBy: 'planner',
            createdAt: new DateTimeImmutable('2026-08-29T06:19:00+00:00'),
        );
    }
}
