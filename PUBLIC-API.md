# Titan Package Public API

The package is pre-release. These names define the current consumer surface; semantic-version guarantees begin with the first tagged release.

## Work-kit compilation

- `Plan`
- `PlanMaterialization`
- `PlanStatus`
- `PlanStep`
- `PlanStepDisposition`
- `WorkKitId`
- `CapabilityId`
- `SourceRecordId`
- `PlanningRecordKind`
- `PlanOptionDisposition`
- `WorkKitStatus`
- `WorkKitAction`
- `WorkKitCompilationStatus`
- `SourceProvenance`
- `ScopeFence`
- `DeclaredDependency`
- `CodeActionRecord`
- `PlanCommitRecord`
- `PlanPrRecord`
- `PlanOptionRecord`
- `CheckinRecord`
- `PlanningRecords`
- `WorkKit`
- `WorkKitCompiler`
- `WorkKitCompilationInput`
- `WorkKitCompilationResult`
- `WorkKitCompilationFailure`
- `WorkKitReadModel`
- `InvalidWorkKitTransition`

## Twinkle promotion

- `Promotion\\ConflictingPromotionReplay`
- `Promotion\\PromotionRequest`
- `Promotion\\PromotionResult`
- `Promotion\\PromotionStatus`
- `Promotion\\TwinklePromoter`
- `Promotion\\WorkForm`

## Planned-task graphs

- `PlannedTaskId`
- `OrbisTemplateId`
- `RequiredApproval`
- `RequiredInput`
- `PlannedTaskReadiness`
- `PlannedTask`
- `PlannedTaskGraphId`
- `PlannedTaskGraphStatus`
- `PlannedTaskGraphFailure`
- `PlannedTaskGraphCompilationStatus`
- `PlannedTaskGraphCompilationInput`
- `PlannedTaskGraphCompilationResult`
- `PlannedTaskGraphCompiler`
- `PlannedTaskGraph`
- `PlannedTaskGraphReadModel`
- `PlannedTaskHandoff`

## Planning interrupts and gates

- `InterruptId`
- `PlanningInterruptType`
- `PlanningInterruptState`
- `EvidenceReference`
- `DecisionReference`
- `InterruptHistoryEntry`
- `PlanningInterrupt`
