<?php
declare(strict_types=1);
namespace Sifrious\Titan;
use InvalidArgumentException;
use Sifrious\ReferenceContract\CrossPackageReference;
/** Records an explicit zero/one/many mapping without owning execution requests. */
final readonly class PlanMaterialization
{
    /** @var list<CrossPackageReference> */
    public array $executionRequestReferences;

    /** @param list<mixed> $executionRequestReferences */
    public function __construct(public string $planId, public string $stepId, array $executionRequestReferences)
    {
        if (trim($planId) === '' || trim($stepId) === '') {
            throw new InvalidArgumentException('Materialization requires plan and step identities.');
        }
        foreach ($executionRequestReferences as $reference) {
            if (! $reference instanceof CrossPackageReference) {
                throw new InvalidArgumentException('Execution request references must use the shared cross-package contract.');
            }
        }
        $this->executionRequestReferences = $executionRequestReferences;
    }
}
