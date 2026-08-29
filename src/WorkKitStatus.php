<?php

declare(strict_types=1);

namespace Sifrious\Titan;

enum WorkKitStatus: string
{
    case Assembled = 'assembled';
    case Executable = 'executable';
    case Superseded = 'superseded';
}
