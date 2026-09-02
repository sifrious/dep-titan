<?php

declare(strict_types=1);

namespace Sifrious\Titan;

enum PlannedTaskReadiness: string
{
    case Ready = 'ready';
    case Blocked = 'blocked';
    case NotReady = 'not_ready';
    case Completed = 'completed';
}
