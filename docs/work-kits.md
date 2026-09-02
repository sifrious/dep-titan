# Work kits

A work kit is the portable compile result of planning records. Titan names the intended work; Logres starts only after a kit is dispatchable.

## Contract

A compiled work kit always names:

- outcome
- first action
- dependencies, each with an explicit satisfied flag
- scope fence
- selected Quain capability identities
- verification steps
- completion criteria
- failure criteria
- source identifiers and provenance for the Landing records that seeded it

## Completeness

Empty dependencies are complete. Any unsatisfied dependency keeps the kit in `assembled` status. Assembled kits are inspectable through `WorkKitReadModel` with `executable: false`. They cannot be presented as executable and are not dispatchable.

Executable status is a constructor invariant: a kit cannot be constructed as `executable` while dependencies are incomplete.

## Transitions

Planning-record transitions happen on `PlanningRecords`:

- `selectOption` — a proposed option becomes selected; another selected option must be dismissed first
- `dismissOption` — a proposed or selected option becomes dismissed
- `recordCheckpoint` — a checkin becomes recorded; timestamps do not imply this

Work-kit transitions happen on `WorkKit`:

- `present` — available only when the kit is executable
- `supersede` — available for assembled or executable kits; a later compilation may reference the prior identity

Compilation is the explicit compile transition. Controllers and jobs must not infer selected options, recorded checkpoints, or dispatchability from timestamps or UI state.

## Planned-task graph compilation

MME-1226 adds a second compile step: executable work kit -> planned-task graph.

Each planned task names:

- stable planned task ID
- objective and outcome
- first action
- dependency edges
- scope fence
- required Quain capability identities
- required approvals
- required inputs/artifacts
- Orbis template identity
- verification steps
- completion criteria
- failure criteria

### Readiness

Titan computes readiness without Logres runtime state:

- `blocked` when dependencies are incomplete or approvals are pending
- `not_ready` when capabilities are incompatible or required inputs are unavailable
- `ready` when dependencies, approvals, capabilities, and inputs are satisfied
- `completed` after explicit completion transition

Graph compilation rejects unknown dependencies and cycles before dispatchability.

### Traversal and parallelism

`topologicalBatches()` returns deterministic dependency batches sorted by task ID. Independent tasks appear in the same batch, preserving explicit parallel branches.

### Handoff

`PlannedTaskHandoff` includes only tasks currently `ready` under the package contract. Runtime lease/running/retry/cancel semantics remain Logres-owned.

## Planning interrupts and gates

MME-1234 adds interrupt/gate planning constraints that apply before execution. One portable `PlanningInterrupt` contract covers scope/review/audit/ship/avoidance gate types.

- States: `open`, `acknowledged`, `resolved`, `waived`.
- Blocking states: `open`, `acknowledged`.
- Resolution history is append-only and remains traceable in immutable history entries.
- Resolution and waiver require stable decision references (e.g. Orual/Uqbar decision IDs), not copied external records.
- Graph readiness uses active interrupts in addition to dependency, approval, capability, and input checks.

## Residue for child tickets

- **MME-1235**: repository/file/test/proposed-change bindings remain outside this graph slice.
- Interrupt inventory model extraction (Landing STI checkins and specific interrupt model cutovers) remains outside this package slice.
