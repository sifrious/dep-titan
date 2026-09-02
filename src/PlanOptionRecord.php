<?php

declare(strict_types=1);

namespace Sifrious\Titan;

use InvalidArgumentException;

final readonly class PlanOptionRecord
{
    public function __construct(
        public SourceProvenance $provenance,
        public string $outcome,
        public string $rationale,
        public PlanOptionDisposition $disposition = PlanOptionDisposition::Proposed,
    ) {
        if ($provenance->kind !== PlanningRecordKind::PlanOption) {
            throw new InvalidArgumentException('A plan option record must carry plan_option provenance.');
        }

        if (trim($outcome) === '') {
            throw new InvalidArgumentException('A plan option must declare an outcome.');
        }

        if (trim($rationale) === '') {
            throw new InvalidArgumentException('A plan option must declare rationale.');
        }
    }

    public function withDisposition(PlanOptionDisposition $disposition): self
    {
        return new self($this->provenance, $this->outcome, $this->rationale, $disposition);
    }
}
