<?php
declare(strict_types=1);
namespace Sifrious\Titan\Promotion;
use Closure;
use Sifrious\ReferenceContract\CrossPackageReference;

final class TwinklePromoter
{
    /** @var array<string, PromotionResult> */
    private array $results = [];

    /** @param Closure(PromotionRequest): (CrossPackageReference|list<CrossPackageReference>) $createWork */
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

        $created = $createWork($request);
        $workReferences = $created instanceof CrossPackageReference ? [$created] : $created;

        return $this->results[$request->idempotencyKey] = new PromotionResult(
            $workReferences,
            $request->twinkle,
            $request->twinkleVersion,
            $fingerprint,
        );
    }

    public function recordUnmaterialized(PromotionRequest $request, PromotionStatus $status, string $reason): PromotionResult
    {
        if ($status === PromotionStatus::Succeeded || trim($reason) === '') {
            throw new \InvalidArgumentException('An unmaterialized promotion requires failed/abandoned status and a reason.');
        }

        $fingerprint = $request->fingerprint();
        $existing = $this->results[$request->idempotencyKey] ?? null;
        if ($existing !== null) {
            if ($existing->requestFingerprint !== $fingerprint) {
                throw new ConflictingPromotionReplay('The promotion key was already used for a different request.');
            }

            return $existing;
        }

        return $this->results[$request->idempotencyKey] = new PromotionResult([], $request->twinkle, $request->twinkleVersion, $fingerprint, $status, $reason);
    }
}
