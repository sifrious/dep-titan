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
