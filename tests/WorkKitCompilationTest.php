<?php

declare(strict_types=1);

namespace Sifrious\Titan\Tests;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Sifrious\Titan\InvalidWorkKitTransition;
use Sifrious\Titan\PlanningRecords;
use Sifrious\Titan\ScopeFence;
use Sifrious\Titan\Tests\Fixtures\PlanningRecordFixtures;
use Sifrious\Titan\WorkKitAction;
use Sifrious\Titan\WorkKitCompilationInput;
use Sifrious\Titan\WorkKitCompiler;
use Sifrious\Titan\WorkKitId;
use Sifrious\Titan\WorkKitReadModel;
use Sifrious\Titan\WorkKitStatus;

final class WorkKitCompilationTest extends TestCase
{
    #[Test]
    public function a_compiled_work_kit_names_the_package_contract(): void
    {
        $result = (new WorkKitCompiler)->compile(PlanningRecordFixtures::executableInput());
        $kit = $result->workKit;

        self::assertTrue($result->acceptedSuccessfully());
        self::assertTrue($result->dispatchable());
        self::assertNotNull($kit);
        self::assertSame('Ship the work-kit compiler.', $kit->outcome);
        self::assertSame('Map Landing planning records onto an explicit work-kit contract.', $kit->firstAction);
        self::assertCount(2, $kit->dependencies);
        self::assertSame('Compile portable work kits only.', $kit->scopeFence->description);
        self::assertSame(['quain:compile-work-kit'], array_map(static fn ($capability): string => $capability->value, $kit->selectedCapabilities));
        self::assertNotSame([], $kit->verificationSteps);
        self::assertNotSame([], $kit->completionCriteria);
        self::assertNotSame([], $kit->failureCriteria);
        self::assertSame(WorkKitStatus::Executable, $kit->status);
    }

    #[Test]
    public function compiled_kits_preserve_landing_source_identifiers_and_provenance(): void
    {
        $kit = (new WorkKitCompiler)->compile(PlanningRecordFixtures::executableInput())->workKit;
        $sourceIds = array_map(static fn ($provenance): string => $provenance->sourceId->value, $kit?->sourceRecords ?? []);

        self::assertSame([
            'landing:code-action-1',
            'landing:plan-commit-1',
            'landing:plan-pr-1',
            'landing:plan-option-1',
            'landing:plan-option-2',
            'landing:checkin-1',
        ], $sourceIds);
        self::assertSame(['landing'], array_unique(array_map(static fn ($provenance): string => $provenance->origin, $kit?->sourceRecords ?? [])));
    }

    #[Test]
    public function dependency_incomplete_work_is_assembled_but_not_dispatchable(): void
    {
        $result = (new WorkKitCompiler)->compile(PlanningRecordFixtures::incompleteInput());
        $kit = $result->workKit;

        self::assertTrue($result->acceptedSuccessfully());
        self::assertFalse($result->dispatchable());
        self::assertNotNull($kit);
        self::assertSame(WorkKitStatus::Assembled, $kit->status);
        self::assertFalse($kit->isExecutable());
        self::assertFalse($kit->dependenciesComplete());
        self::assertFalse(WorkKitReadModel::fromWorkKit($kit)->executable);
        self::assertSame(['supersede'], WorkKitReadModel::fromWorkKit($kit)->actions);
    }

    #[Test]
    public function dependency_incomplete_work_cannot_be_presented_as_executable(): void
    {
        $this->expectException(InvalidWorkKitTransition::class);

        (new WorkKitCompiler)->compile(PlanningRecordFixtures::incompleteInput())->workKit?->present();
    }

    #[Test]
    public function compilation_refuses_an_unselected_option_set(): void
    {
        $result = (new WorkKitCompiler)->compile(
            PlanningRecordFixtures::input(PlanningRecordFixtures::completeRecords(), []),
        );

        self::assertFalse($result->acceptedSuccessfully());
        self::assertFalse($result->dispatchable());
        self::assertNull($result->workKit);
        self::assertSame(['option_not_selected'], array_map(static fn ($failure): string => $failure->code, $result->failures));
    }

    #[Test]
    public function compilation_refuses_missing_code_action_or_contract_fields(): void
    {
        $result = (new WorkKitCompiler)->compile(new WorkKitCompilationInput(
            id: new WorkKitId('workkit:invalid'),
            records: new PlanningRecords(codeActions: []),
            dependencies: [],
            scopeFence: new ScopeFence('Refuse incomplete contract fields.'),
            selectedCapabilities: [],
            verificationSteps: [],
            completionCriteria: [],
            failureCriteria: [],
        ));

        self::assertSame(
            ['code_action_required', 'verification_steps_required', 'completion_criteria_required', 'failure_criteria_required'],
            array_map(static fn ($failure): string => $failure->code, $result->failures),
        );
    }

    #[Test]
    public function selected_capabilities_are_quain_identities_not_capability_definitions(): void
    {
        $kit = (new WorkKitCompiler)->compile(PlanningRecordFixtures::executableInput())->workKit;

        self::assertSame('quain:compile-work-kit', $kit?->selectedCapabilities[0]->value);
        self::assertFalse(property_exists($kit->selectedCapabilities[0], 'inputs'));
        self::assertFalse(property_exists($kit->selectedCapabilities[0], 'readiness'));
    }

    #[Test]
    public function an_executable_read_model_exposes_package_computed_dispatchability(): void
    {
        $kit = (new WorkKitCompiler)->compile(PlanningRecordFixtures::executableInput())->workKit;
        self::assertNotNull($kit);
        $model = $kit->present();

        self::assertSame('workkit:atlas-compiler', $model->id);
        self::assertTrue($model->executable);
        self::assertSame('executable', $model->status);
        self::assertSame(['present', 'supersede'], $model->actions);
        self::assertSame('landing:code-action-1', $model->sourceRecords[0]['source_id']);
    }

    #[Test]
    public function a_superseding_compilation_preserves_prior_identity(): void
    {
        $first = (new WorkKitCompiler)->compile(PlanningRecordFixtures::executableInput())->workKit;
        self::assertNotNull($first);

        $secondInput = new WorkKitCompilationInput(
            id: new WorkKitId('workkit:atlas-compiler:v2'),
            records: PlanningRecordFixtures::selectedRecords(),
            dependencies: PlanningRecordFixtures::executableInput()->dependencies,
            scopeFence: PlanningRecordFixtures::executableInput()->scopeFence,
            selectedCapabilities: PlanningRecordFixtures::executableInput()->selectedCapabilities,
            verificationSteps: PlanningRecordFixtures::executableInput()->verificationSteps,
            completionCriteria: PlanningRecordFixtures::executableInput()->completionCriteria,
            failureCriteria: PlanningRecordFixtures::executableInput()->failureCriteria,
            supersedes: $first->id,
        );
        $second = (new WorkKitCompiler)->compile($secondInput)->workKit;

        self::assertSame('workkit:atlas-compiler', $second?->supersedes?->value);
        self::assertSame(WorkKitStatus::Superseded, $first->apply(WorkKitAction::Supersede)->status);
    }
}
