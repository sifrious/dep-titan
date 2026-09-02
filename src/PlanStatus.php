<?php
declare(strict_types=1);
namespace Sifrious\Titan;
enum PlanStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Completed = 'completed';
    case Abandoned = 'abandoned';
    case Superseded = 'superseded';
}
