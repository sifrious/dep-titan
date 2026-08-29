<?php

declare(strict_types=1);

namespace Sifrious\Titan;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class InterruptHistoryEntry
{
    public array $decisionReferences;

    public function __construct(
        public PlanningInterruptState $state,
        public string $actor,
        public DateTimeImmutable $occurredAt,
        public string $note,
        array $decisionReferences = [],
    ) {
        if (trim($actor) === '') {
            throw new InvalidArgumentException('Interrupt history actor is required.');
        }

        if (trim($note) === '') {
            throw new InvalidArgumentException('Interrupt history note is required.');
        }

        $this->decisionReferences = array_values($decisionReferences);
    }
}
