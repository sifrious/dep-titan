<?php
declare(strict_types=1);
namespace Sifrious\Titan\Tests;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Quain\Core\Concept\ConceptReference;
use Quain\Core\Concept\VocabularySchemeReference;
use Sifrious\ReferenceContract\CrossPackageReference;
use Sifrious\Titan\Promotion\ConflictingPromotionReplay;
use Sifrious\Titan\Promotion\PromotionRequest;
use Sifrious\Titan\Promotion\TwinklePromoter;
use Sifrious\Titan\Promotion\WorkForm;
use Sifrious\Titan\Promotion\PromotionStatus;

final class TwinklePromotionContractTest extends TestCase
{
    public function test_domain_neutral_promotion_is_repository_optional_and_idempotent(): void
    {
        $request = $this->request('promote:1', WorkForm::ExplorationPlan);
        $promoter = new TwinklePromoter();
        $calls = 0;
        $create = function () use (&$calls): CrossPackageReference { $calls++; return $this->ref('titan-plan', 'plan:1'); };
        $first = $promoter->promote($request, $create);
        $replay = $promoter->promote($request, $create);
        self::assertSame($first, $replay);
        self::assertSame(1, $calls);
        self::assertSame('twinkle:1', $first->originatingTwinkle->id);
        self::assertSame(3, $first->originatingTwinkleVersion);
    }

    public function test_repository_plan_requires_repository_context(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->request('promote:2', WorkForm::RepositoryPlan);
    }

    public function test_repository_plan_retains_context_and_concept_references(): void
    {
        $request = $this->request('promote:3', WorkForm::RepositoryPlan, $this->ref('repository', 'sifrious/burdgeon'));
        $result = (new TwinklePromoter())->promote($request, fn () => $this->ref('titan-plan', 'plan:3'));
        self::assertSame('sifrious/burdgeon', $request->repository?->id);
        self::assertSame('algebraic-effects', $request->concepts[0]->identifier);
        self::assertSame('plan:3', $result->workReferences[0]->id);
    }

    public function test_conflicting_replay_fails_explicitly(): void
    {
        $promoter = new TwinklePromoter();
        $promoter->promote($this->request('promote:4', WorkForm::ExplorationPlan), fn () => $this->ref('titan-plan', 'plan:4'));
        $this->expectException(ConflictingPromotionReplay::class);
        $promoter->promote($this->request('promote:4', WorkForm::WorkKit), fn () => $this->ref('titan-kit', 'kit:4'));
    }

    public function test_one_twinkle_can_materialize_many_work_items(): void
    {
        $result = (new TwinklePromoter())->promote(
            $this->request('promote:many', WorkForm::ExplorationPlan),
            fn () => [$this->ref('titan-plan', 'plan:a'), $this->ref('titan-kit', 'kit:b')],
        );

        self::assertSame(['plan:a', 'kit:b'], array_map(static fn (CrossPackageReference $reference): string => $reference->id, $result->workReferences));
    }

    public function test_distinct_twinkles_can_converge_on_one_work_item_and_failed_attempt_remains_replayable(): void
    {
        $promoter = new TwinklePromoter();
        $shared = $this->ref('titan-plan', 'plan:shared');
        $first = $promoter->promote($this->request('promote:converge:a', WorkForm::ExplorationPlan), fn () => $shared);
        $secondRequest = new PromotionRequest('promote:converge:b', $this->ref('elwin-twinkle', 'twinkle:2'), 1, WorkForm::ExplorationPlan, 'Converge', null, $this->ref('user', 'mary'), $this->ref('conversation', 'chat:2'));
        $second = $promoter->promote($secondRequest, fn () => $shared);
        $failedRequest = $this->request('promote:failed', WorkForm::ExplorationPlan);
        $failed = $promoter->recordUnmaterialized($failedRequest, PromotionStatus::Failed, 'planning_rejected');

        self::assertSame($first->workReferences[0]->key(), $second->workReferences[0]->key());
        self::assertFalse($first->originatingTwinkle->equals($second->originatingTwinkle));
        self::assertSame([], $failed->workReferences);
        self::assertSame($failed, $promoter->recordUnmaterialized($failedRequest, PromotionStatus::Failed, 'planning_rejected'));
    }

    private function request(string $key, WorkForm $form, ?CrossPackageReference $repository = null): PromotionRequest
    {
        return new PromotionRequest(
            $key,
            $this->ref('elwin-twinkle', 'twinkle:1'),
            3,
            $form,
            'Explore algebraic effects',
            null,
            $this->ref('user', 'mary'),
            $this->ref('conversation', 'chat:1'),
            [$this->ref('conversation', 'chat:1')],
            [new ConceptReference(new VocabularySchemeReference('sifrious/quain', 'programming-language', '1'), 'algebraic-effects')],
            $repository,
        );
    }

    private function ref(string $type, string $identifier): CrossPackageReference
    {
        return new CrossPackageReference('test/owner', $type, $identifier);
    }
}
