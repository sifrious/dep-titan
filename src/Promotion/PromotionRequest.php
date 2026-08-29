<?php
declare(strict_types=1);
namespace Sifrious\Titan\Promotion;
use InvalidArgumentException;
use Quain\Core\Concept\ConceptReference;
use Sifrious\Elwin\Reference;

final readonly class PromotionRequest
{
    /** @param list<Reference> $context @param list<ConceptReference> $concepts */
    public function __construct(
        public string $idempotencyKey,
        public Reference $twinkle,
        public int $twinkleVersion,
        public WorkForm $workForm,
        public string $title,
        public ?string $description,
        public Reference $promotedBy,
        public Reference $provenance,
        public array $context = [],
        public array $concepts = [],
        public ?Reference $repository = null,
    ) {
        if (trim($idempotencyKey) === '' || trim($title) === '' || $twinkleVersion < 1) {
            throw new InvalidArgumentException('Promotion requires a key, title, and positive Twinkle version.');
        }
        if ($workForm->requiresRepository() && $repository === null) {
            throw new InvalidArgumentException("{$workForm->value} requires repository context.");
        }
    }

    public function fingerprint(): string
    {
        return hash('sha256', serialize($this));
    }
}
