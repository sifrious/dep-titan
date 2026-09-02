<?php

declare(strict_types=1);

namespace Sifrious\Titan;

use InvalidArgumentException;

final readonly class PlanPrRecord
{
    public function __construct(
        public SourceProvenance $provenance,
        public string $intendedReview,
    ) {
        if ($provenance->kind !== PlanningRecordKind::PlanPr) {
            throw new InvalidArgumentException('A plan PR record must carry plan_pr provenance.');
        }

        if (trim($intendedReview) === '') {
            throw new InvalidArgumentException('A plan PR must declare intended review work, not an observed provider PR.');
        }
    }
}
