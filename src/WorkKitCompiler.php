<?php

declare(strict_types=1);

namespace Sifrious\Titan;

final readonly class WorkKitCompiler
{
    public function compile(WorkKitCompilationInput $input): WorkKitCompilationResult
    {
        $failures = $this->validate($input);

        if ($failures !== []) {
            return new WorkKitCompilationResult(WorkKitCompilationStatus::Rejected, null, $failures);
        }

        $codeAction = $input->records->codeActions[0];
        $selectedOption = $input->records->selectedOption();
        $outcome = $selectedOption instanceof PlanOptionRecord
            ? $selectedOption->outcome
            : $codeAction->intent;
        $complete = $this->dependenciesComplete($input->dependencies);

        $kit = new WorkKit(
            id: $input->id,
            outcome: $outcome,
            firstAction: $codeAction->firstAction,
            dependencies: $input->dependencies,
            scopeFence: $input->scopeFence,
            selectedCapabilities: $input->selectedCapabilities,
            verificationSteps: $input->verificationSteps,
            completionCriteria: $input->completionCriteria,
            failureCriteria: $input->failureCriteria,
            sourceRecords: $input->records->provenance(),
            status: $complete ? WorkKitStatus::Executable : WorkKitStatus::Assembled,
            supersedes: $input->supersedes,
        );

        return new WorkKitCompilationResult(WorkKitCompilationStatus::Accepted, $kit);
    }

    private function validate(WorkKitCompilationInput $input): array
    {
        $failures = [];

        if ($input->records->codeActions === []) {
            $failures[] = new WorkKitCompilationFailure(
                'code_action_required',
                'records.code_actions',
                'Compilation requires a CodeAction that names the first action.',
            );
        }

        if ($input->records->planOptions !== [] && $input->records->selectedOption() === null) {
            $failures[] = new WorkKitCompilationFailure(
                'option_not_selected',
                'records.plan_options',
                'A plan option must be selected through an explicit transition before compilation.',
            );
        }

        $selectedCount = 0;
        foreach ($input->records->planOptions as $option) {
            if ($option->disposition === PlanOptionDisposition::Selected) {
                $selectedCount++;
            }
        }

        if ($selectedCount > 1) {
            $failures[] = new WorkKitCompilationFailure(
                'option_ambiguous',
                'records.plan_options',
                'Exactly one plan option may be selected.',
            );
        }

        foreach ($input->selectedCapabilities as $index => $capability) {
            if (! $capability instanceof CapabilityId) {
                $failures[] = new WorkKitCompilationFailure(
                    'capability_identity_required',
                    "selected_capabilities.{$index}",
                    'Selected capabilities must be Quain identities, not duplicated capability definitions.',
                );
            }
        }

        foreach ([['verification_steps', $input->verificationSteps], ['completion_criteria', $input->completionCriteria], ['failure_criteria', $input->failureCriteria]] as [$field, $values]) {
            if ($values === []) {
                $failures[] = new WorkKitCompilationFailure(
                    "{$field}_required",
                    $field,
                    str_replace('_', ' ', ucfirst($field)).' are required.',
                );
            }

            foreach ($values as $index => $value) {
                if (! is_string($value) || trim($value) === '') {
                    $failures[] = new WorkKitCompilationFailure(
                        "{$field}_invalid",
                        "{$field}.{$index}",
                        str_replace('_', ' ', ucfirst($field)).' must be non-empty statements.',
                    );
                }
            }
        }

        foreach ($input->dependencies as $index => $dependency) {
            if (! $dependency instanceof DeclaredDependency) {
                $failures[] = new WorkKitCompilationFailure(
                    'dependency_invalid',
                    "dependencies.{$index}",
                    'Every dependency must be a declared dependency with explicit satisfaction.',
                );
            }
        }

        return $failures;
    }

    private function dependenciesComplete(array $dependencies): bool
    {
        foreach ($dependencies as $dependency) {
            if (! $dependency instanceof DeclaredDependency || ! $dependency->satisfied) {
                return false;
            }
        }

        return true;
    }
}
