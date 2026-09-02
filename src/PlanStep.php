<?php
declare(strict_types=1);
namespace Sifrious\Titan;
use InvalidArgumentException;
final readonly class PlanStep
{
    /** @param list<string> $acceptanceCriteria */
    public function __construct(public string $id, public string $outcome, public PlanStepDisposition $disposition, public array $acceptanceCriteria = [])
    {
        if (trim($id) === '' || trim($outcome) === '') {
            throw new InvalidArgumentException('Plan step identity and outcome are required.');
        }
    }
}
