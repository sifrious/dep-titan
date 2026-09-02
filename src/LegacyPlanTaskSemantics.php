<?php

declare(strict_types=1);

namespace Sifrious\Titan;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Portable meanings retained from the original Task/PlanTask record.
 * Position is presentation metadata, and project identity is never workspace identity.
 */
final readonly class LegacyPlanTaskSemantics
{
    public function __construct(
        public string $taskId,
        public string $planStepId,
        public ?string $projectId = null,
        public ?int $position = null,
        public ?DateTimeImmutable $doneAt = null,
        public bool $disciplineTask = false,
        public bool $noteTask = false,
    ) {
        if (trim($taskId) === '' || trim($planStepId) === '') {
            throw new InvalidArgumentException('Task and PlanStep identities are required.');
        }
    }

    public function withPlanningCompletion(DateTimeImmutable $doneAt): self
    {
        return new self(
            taskId: $this->taskId,
            planStepId: $this->planStepId,
            projectId: $this->projectId,
            position: $this->position,
            doneAt: $doneAt,
            disciplineTask: $this->disciplineTask,
            noteTask: $this->noteTask,
        );
    }
}
