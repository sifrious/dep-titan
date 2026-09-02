<?php
declare(strict_types=1);
namespace Sifrious\Titan\Promotion;
use Closure;
use Sifrious\Elwin\Reference;

final class TwinklePromoter
{
    /** @var array<string, PromotionResult> */
    private array $results = [];

    /** @param Closure(PromotionRequest): Reference $createWork */
    public function promote(PromotionRequest $request, Closure $createWork): PromotionResult
    {
        $fingerprint = $request->fingerprint();
        $existing = $this->results[$request->idempotencyKey] ?? null;
        if ($existing !== null) {
            if ($existing->requestFingerprint !== $fingerprint) {
                throw new ConflictingPromotionReplay('The promotion key was already used for a different request.');
            }
            return $existing;
        }

        return $this->results[$request->idempotencyKey] = new PromotionResult(
            $createWork($request),
            $request->twinkle,
            $request->twinkleVersion,
            $fingerprint,
        );
    }
}
