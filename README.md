# Titan Package

This repository contains the framework-neutral contracts that compile planning records into executable work kits.

It deliberately has no Laravel, Eloquent, queue, HTTP, Blade, NativePHP, or provider-SDK dependency.

The current consumer surface is recorded in [PUBLIC-API.md](PUBLIC-API.md).

## Development

```bash
composer install
composer check
```

Local applications consume this package through a Composer path repository during development.

## Work kits

Titan owns the transition from portable planning records into dependency-complete work kits. A compiled kit names outcome, first action, dependencies, scope fence, selected Quain capability identities, verification steps, completion criteria, and failure criteria.

Logres executes after dispatch. Titan does not own runtime or current-state. Funes owns observed historical evidence. Quain owns capability semantics; Titan stores capability identities only.

## Planning records

`CodeAction`, `PlanCommit`, `PlanPr`, `PlanOption`, and `Checkin` arrive as adapter/input DTOs that preserve Landing source identifiers and provenance. They are not Eloquent models and not UI. Mapping and Landing-only residue are recorded in [docs/landing-adapters.md](docs/landing-adapters.md).

Option selection and checkpoint recording are explicit transitions. Dependency-incomplete work can be assembled for inspection but cannot be presented as executable or dispatchable.

## Planned-task graphs

Titan can compile an executable work kit into a planned-task dependency graph with explicit task contracts:

- stable planned-task identity
- objective and outcome
- first action
- explicit dependency edges
- scope fence
- required Quain capability identities
- required approvals
- required inputs/artifacts
- Orbis template reference by identity
- verification steps
- completion criteria
- failure criteria

Graph readiness is package-computed from dependency completion, approval state, capability compatibility, and required inputs. It uses planning labels (`ready`, `blocked`, `not_ready`, `completed`) and does not expose Logres runtime labels.

Only dependency-complete, approval-complete, capability-compatible, input-ready tasks are emitted in `PlannedTaskHandoff`.

## Out of scope in this slice

- MME-1234 planning interrupts and approval-gate policy families (this slice only supports portable required-approval contracts and readiness).
- MME-1235 repository/file/proposed-change bindings (task repo/file/test and change proposal contracts stay for that ticket).
- Catalogue persistence extraction and any UI/browse stories.
