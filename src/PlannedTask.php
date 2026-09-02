<?php

declare(strict_types=1);

namespace Sifrious\Titan;

use InvalidArgumentException;

final readonly class PlannedTask
{
    public array $dependencies;

    public array $requiredCapabilities;

    public array $requiredApprovals;

    public array $requiredInputs;

    public array $verificationSteps;

    public array $completionCriteria;

    public array $failureCriteria;

    public function __construct(
        public PlannedTaskId $id,
        public string $objective,
        public string $outcome,
        public string $firstAction,
        array $dependencies,
        public ScopeFence $scopeFence,
        array $requiredCapabilities,
        array $requiredApprovals,
        array $requiredInputs,
        public OrbisTemplateId $orbisTemplate,
        array $verificationSteps,
        array $completionCriteria,
        array $failureCriteria,
        public bool $explicitlyParallel = false,
        public bool $completed = false,
        public ?PlannedTaskCompletionProof $completionProof = null,
        public ?LegacyPlanTaskSemantics $legacySemantics = null,
    ) {
        foreach ([['objective', $objective], ['outcome', $outcome], ['first_action', $firstAction]] as [$field, $value]) {
            if (trim($value) === '') {
                throw new InvalidArgumentException(str_replace('_', ' ', ucfirst($field)).' is required.');
            }
        }

        $this->dependencies = array_values($dependencies);
        $this->requiredCapabilities = array_values($requiredCapabilities);
        $this->requiredApprovals = array_values($requiredApprovals);
        $this->requiredInputs = array_values($requiredInputs);
        $this->verificationSteps = array_values($verificationSteps);
        $this->completionCriteria = array_values($completionCriteria);
        $this->failureCriteria = array_values($failureCriteria);

        if ($this->verificationSteps === []) {
            throw new InvalidArgumentException('A planned task requires verification steps.');
        }

        if ($this->completionCriteria === []) {
            throw new InvalidArgumentException('A planned task requires completion criteria.');
        }

        if ($this->failureCriteria === []) {
            throw new InvalidArgumentException('A planned task requires failure criteria.');
        }

        if ($completed !== ($completionProof !== null && $completionProof->permitsCompletion())) {
            throw new InvalidArgumentException('Planning completion must be backed by passing completion proof.');
        }

        if ($legacySemantics !== null) {
            if (($legacySemantics->doneAt !== null) !== $completed) {
                throw new InvalidArgumentException('PlanTask done_at must exactly represent planning completion.');
            }

            if ($completed && $legacySemantics->doneAt != $completionProof?->completedAt) {
                throw new InvalidArgumentException('PlanTask done_at must match completion proof time.');
            }
        }
    }

    public function withCompletionProof(PlannedTaskCompletionProof $proof): self
    {
        return new self(
            id: $this->id,
            objective: $this->objective,
            outcome: $this->outcome,
            firstAction: $this->firstAction,
            dependencies: $this->dependencies,
            scopeFence: $this->scopeFence,
            requiredCapabilities: $this->requiredCapabilities,
            requiredApprovals: $this->requiredApprovals,
            requiredInputs: $this->requiredInputs,
            orbisTemplate: $this->orbisTemplate,
            verificationSteps: $this->verificationSteps,
            completionCriteria: $this->completionCriteria,
            failureCriteria: $this->failureCriteria,
            explicitlyParallel: $this->explicitlyParallel,
            completed: true,
            completionProof: $proof,
            legacySemantics: $this->legacySemantics?->withPlanningCompletion($proof->completedAt),
        );
    }

    public function withApprovals(array $requiredApprovals): self
    {
        return new self(
            id: $this->id,
            objective: $this->objective,
            outcome: $this->outcome,
            firstAction: $this->firstAction,
            dependencies: $this->dependencies,
            scopeFence: $this->scopeFence,
            requiredCapabilities: $this->requiredCapabilities,
            requiredApprovals: $requiredApprovals,
            requiredInputs: $this->requiredInputs,
            orbisTemplate: $this->orbisTemplate,
            verificationSteps: $this->verificationSteps,
            completionCriteria: $this->completionCriteria,
            failureCriteria: $this->failureCriteria,
            explicitlyParallel: $this->explicitlyParallel,
            completed: $this->completed,
            completionProof: $this->completionProof,
            legacySemantics: $this->legacySemantics,
        );
    }

    public function withInputs(array $requiredInputs): self
    {
        return new self(
            id: $this->id,
            objective: $this->objective,
            outcome: $this->outcome,
            firstAction: $this->firstAction,
            dependencies: $this->dependencies,
            scopeFence: $this->scopeFence,
            requiredCapabilities: $this->requiredCapabilities,
            requiredApprovals: $this->requiredApprovals,
            requiredInputs: $requiredInputs,
            orbisTemplate: $this->orbisTemplate,
            verificationSteps: $this->verificationSteps,
            completionCriteria: $this->completionCriteria,
            failureCriteria: $this->failureCriteria,
            explicitlyParallel: $this->explicitlyParallel,
            completed: $this->completed,
            completionProof: $this->completionProof,
            legacySemantics: $this->legacySemantics,
        );
    }
}
