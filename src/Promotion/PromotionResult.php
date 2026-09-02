<?php
declare(strict_types=1);
namespace Sifrious\Titan\Promotion;
use Sifrious\ReferenceContract\CrossPackageReference;

final readonly class PromotionResult
{
    /** @var list<CrossPackageReference> */
    public array $workReferences;

    /** @param list<CrossPackageReference> $workReferences */
    public function __construct(
        array $workReferences,
        public CrossPackageReference $originatingTwinkle,
        public int $originatingTwinkleVersion,
        public string $requestFingerprint,
        public PromotionStatus $status = PromotionStatus::Succeeded,
        public ?string $failureReason = null,
    ) {
        $this->workReferences = $workReferences;
    }
}
