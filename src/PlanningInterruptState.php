<?php

declare(strict_types=1);

namespace Sifrious\Titan;

enum PlanningInterruptState: string
{
    case Open = 'open';
    case Acknowledged = 'acknowledged';
    case Resolved = 'resolved';
    case Waived = 'waived';
}
