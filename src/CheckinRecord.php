<?php

declare(strict_types=1);

namespace Sifrious\Titan;

use InvalidArgumentException;

final readonly class CheckinRecord
{
    public function __construct(
        public SourceProvenance $provenance,
        public string $checkpoint,
        public bool $recorded = false,
    ) {
        if ($provenance->kind !== PlanningRecordKind::Checkin) {
            throw new InvalidArgumentException('A checkin record must carry checkin provenance.');
        }

        if (trim($checkpoint) === '') {
            throw new InvalidArgumentException('A checkin must declare a planning checkpoint.');
        }
    }

    public function withRecorded(bool $recorded): self
    {
        return new self($this->provenance, $this->checkpoint, $recorded);
    }
}
