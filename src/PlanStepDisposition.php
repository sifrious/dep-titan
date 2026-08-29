<?php
declare(strict_types=1);
namespace Sifrious\Titan;
enum PlanStepDisposition: string
{
    case Deliberation = 'deliberation';
    case Research = 'research';
    case Decision = 'decision';
    case Ticket = 'ticket';
    case Execution = 'execution';
    case NoAction = 'no_action';
}
