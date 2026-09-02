<?php

declare(strict_types=1);

namespace Sifrious\Titan;

final class PlannedTaskGraphVersionAuthority
{
    private string $currentGraphId;

    public function __construct(PlannedTaskGraphId $currentGraphId)
    {
        $this->currentGraphId = $currentGraphId->value;
    }

    public function activate(PlannedTaskGraphId $graphId): void
    {
        $this->currentGraphId = $graphId->value;
    }

    public function isCurrent(PlannedTaskGraphId $graphId): bool
    {
        return $this->currentGraphId === $graphId->value;
    }
}
