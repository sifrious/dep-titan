<?php

declare(strict_types=1);

namespace Sifrious\Titan;

use DateTimeImmutable;
use InvalidArgumentException;

final readonly class PlannedTaskCompletionProof
{
    public array $evidenceReferences;

    public function __construct(
        public string $verificationPlanVersion,
        array $evidenceReferences,
        public bool $verificationPassed,
        public bool $completionCriteriaSatisfied,
        public bool $failureCriteriaTriggered,
        public DateTimeImmutable $completedAt,
    ) {
        if (trim($verificationPlanVersion) === '') {
            throw new InvalidArgumentException('Completion proof requires a verification-plan version.');
        }

        $this->evidenceReferences = array_values($evidenceReferences);

        if ($this->evidenceReferences === []) {
            throw new InvalidArgumentException('Completion proof requires immutable evidence references.');
        }

        foreach ($this->evidenceReferences as $reference) {
            if (! $reference instanceof EvidenceReference) {
                throw new InvalidArgumentException('Completion proof evidence must use portable evidence references.');
            }
        }
    }

    public function permitsCompletion(): bool
    {
        return $this->verificationPassed
            && $this->completionCriteriaSatisfied
            && ! $this->failureCriteriaTriggered;
    }
}
