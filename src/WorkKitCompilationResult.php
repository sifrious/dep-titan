<?php

declare(strict_types=1);

namespace Sifrious\Titan;

final readonly class WorkKitCompilationResult
{
    public array $failures;

    public function __construct(
        public WorkKitCompilationStatus $status,
        public ?WorkKit $workKit,
        array $failures = [],
    ) {
        $this->failures = array_values($failures);
    }

    public function acceptedSuccessfully(): bool
    {
        return $this->status === WorkKitCompilationStatus::Accepted && $this->workKit !== null;
    }

    public function dispatchable(): bool
    {
        return $this->acceptedSuccessfully() && $this->workKit->isExecutable();
    }
}
