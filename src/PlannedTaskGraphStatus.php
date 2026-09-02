<?php

declare(strict_types=1);

namespace Sifrious\Titan;

enum PlannedTaskGraphStatus: string
{
    case Planned = 'planned';
    case Dispatchable = 'dispatchable';
    case Superseded = 'superseded';
}
