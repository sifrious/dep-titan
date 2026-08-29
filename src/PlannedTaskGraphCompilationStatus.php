<?php

declare(strict_types=1);

namespace Sifrious\Titan;

enum PlannedTaskGraphCompilationStatus: string
{
    case Accepted = 'accepted';
    case Rejected = 'rejected';
}
