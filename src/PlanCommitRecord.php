<?php

declare(strict_types=1);

namespace Sifrious\Titan;

use InvalidArgumentException;

final readonly class PlanCommitRecord
{
    public function __construct(
        public SourceProvenance $provenance,
        public string $intendedChange,
    ) {
        if ($provenance->kind !== PlanningRecordKind::PlanCommit) {
            throw new InvalidArgumentException('A plan commit record must carry plan_commit provenance.');
        }

        if (trim($intendedChange) === '') {
            throw new InvalidArgumentException('A plan commit must declare intended change, not an observed Git commit.');
        }
    }
}
