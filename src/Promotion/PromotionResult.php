<?php
declare(strict_types=1);
namespace Sifrious\Titan\Promotion;
use Sifrious\Elwin\Reference;

final readonly class PromotionResult
{
    public function __construct(
        public Reference $work,
        public Reference $originatingTwinkle,
        public int $originatingTwinkleVersion,
        public string $requestFingerprint,
    ) {}
}
