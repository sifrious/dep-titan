<?php
declare(strict_types=1);
namespace Sifrious\Titan\Promotion;

enum WorkForm: string
{
    case ExplorationPlan = 'exploration-plan';
    case RepositoryPlan = 'repository-plan';
    case WorkKit = 'work-kit';

    public function requiresRepository(): bool
    {
        return $this === self::RepositoryPlan;
    }
}
