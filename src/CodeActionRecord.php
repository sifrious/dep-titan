<?php

declare(strict_types=1);

namespace Sifrious\Titan;

use InvalidArgumentException;

final readonly class CodeActionRecord
{
    public function __construct(
        public SourceProvenance $provenance,
        public string $firstAction,
        public string $intent,
    ) {
        if ($provenance->kind !== PlanningRecordKind::CodeAction) {
            throw new InvalidArgumentException('A code action record must carry code_action provenance.');
        }

        if (trim($firstAction) === '') {
            throw new InvalidArgumentException('A code action must declare the first action.');
        }

        if (trim($intent) === '') {
            throw new InvalidArgumentException('A code action must declare work-preparation intent.');
        }
    }
}
