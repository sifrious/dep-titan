<?php

declare(strict_types=1);

namespace Sifrious\Titan;

enum PlanningRecordKind: string
{
    case CodeAction = 'code_action';
    case PlanCommit = 'plan_commit';
    case PlanPr = 'plan_pr';
    case PlanOption = 'plan_option';
    case Checkin = 'checkin';
}
