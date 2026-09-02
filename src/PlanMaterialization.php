<?php
declare(strict_types=1);
namespace Sifrious\Titan;
use InvalidArgumentException;
/** Records an explicit zero/one/many mapping without owning execution requests. */
final readonly class PlanMaterialization
{
    /** @param list<string> $executionRequestReferences */
    public function __construct(public string $planId, public string $stepId, public array $executionRequestReferences)
    {
        if (trim($planId) === '' || trim($stepId) === '') {
            throw new InvalidArgumentException('Materialization requires plan and step identities.');
        }
        foreach ($executionRequestReferences as $reference) {
            if (! is_string($reference) || trim($reference) === '') {
                throw new InvalidArgumentException('Execution request references must be non-empty.');
            }
        }
    }
}
