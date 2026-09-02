<?php

declare(strict_types=1);

namespace Sifrious\Titan;

enum PlanOptionDisposition: string
{
    case Proposed = 'proposed';
    case Selected = 'selected';
    case Dismissed = 'dismissed';
}
