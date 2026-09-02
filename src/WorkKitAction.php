<?php

declare(strict_types=1);

namespace Sifrious\Titan;

enum WorkKitAction: string
{
    case Present = 'present';
    case Supersede = 'supersede';
}
