<?php
declare(strict_types=1);
namespace Sifrious\Titan;
use InvalidArgumentException;
use Sifrious\ReferenceContract\CrossPackageReference;
final readonly class Plan
{
    /** @param list<PlanStep> $steps */
    public function __construct(public string $id, public CrossPackageReference $conversationReference, public array $steps, public PlanStatus $status = PlanStatus::Draft, public ?string $replacementPlanId = null)
    {
        if (trim($id) === '' || $steps === []) {
            throw new InvalidArgumentException('Plan identity, conversation origin, and at least one step are required.');
        }
        if ($status === PlanStatus::Superseded && $replacementPlanId === null) {
            throw new InvalidArgumentException('A superseded plan must identify its replacement.');
        }
        $ids = array_map(static fn (PlanStep $step): string => $step->id, $steps);
        if (count($ids) !== count(array_unique($ids))) {
            throw new InvalidArgumentException('Plan step identities must be unique.');
        }
    }
}
