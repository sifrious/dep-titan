<?php

declare(strict_types=1);

namespace Sifrious\Titan;

enum PlanningInterruptType: string
{
    case Scope = 'scope';
    case CodeReview = 'code_review';
    case Audit = 'audit';
    case Ship = 'ship';
    case Avoidance = 'avoidance';
}
