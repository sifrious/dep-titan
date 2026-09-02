<?php

declare(strict_types=1);

namespace Sifrious\Titan;

use InvalidArgumentException;

final readonly class SourceProvenance
{
    public function __construct(
        public PlanningRecordKind $kind,
        public SourceRecordId $sourceId,
        public string $origin = 'landing',
    ) {
        if (trim($origin) === '') {
            throw new InvalidArgumentException('Source provenance must name an origin.');
        }
    }
}
