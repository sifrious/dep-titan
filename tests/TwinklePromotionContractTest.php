<?php
declare(strict_types=1);
namespace Sifrious\Titan\Tests;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Quain\Core\Concept\ConceptReference;
use Quain\Core\Concept\VocabularySchemeReference;
use Sifrious\Elwin\Reference;
use Sifrious\Titan\Promotion\ConflictingPromotionReplay;
use Sifrious\Titan\Promotion\PromotionRequest;
use Sifrious\Titan\Promotion\TwinklePromoter;
use Sifrious\Titan\Promotion\WorkForm;

final class TwinklePromotionContractTest extends TestCase
{
    public function test_domain_neutral_promotion_is_repository_optional_and_idempotent(): void
    {
        $request = $this->request('promote:1', WorkForm::ExplorationPlan);
        $promoter = new TwinklePromoter();
        $calls = 0;
        $create = function () use (&$calls): Reference { $calls++; return $this->ref('titan-plan', 'plan:1'); };
        $first = $promoter->promote($request, $create);
        $replay = $promoter->promote($request, $create);
        self::assertSame($first, $replay);
        self::assertSame(1, $calls);
        self::assertSame('twinkle:1', $first->originatingTwinkle->identifier);
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
        self::assertSame('sifrious/burdgeon', $request->repository?->identifier);
        self::assertSame('algebraic-effects', $request->concepts[0]->identifier);
        self::assertSame('plan:3', $result->work->identifier);
    }

    public function test_conflicting_replay_fails_explicitly(): void
    {
        $promoter = new TwinklePromoter();
        $promoter->promote($this->request('promote:4', WorkForm::ExplorationPlan), fn () => $this->ref('titan-plan', 'plan:4'));
        $this->expectException(ConflictingPromotionReplay::class);
        $promoter->promote($this->request('promote:4', WorkForm::WorkKit), fn () => $this->ref('titan-kit', 'kit:4'));
    }

    private function request(string $key, WorkForm $form, ?Reference $repository = null): PromotionRequest
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

    private function ref(string $type, string $identifier): Reference
    {
        return new Reference('test/owner', $type, $identifier);
    }
}
