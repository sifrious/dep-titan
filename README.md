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

## Planning interrupts and gates

Titan defines one portable interrupt contract for planning gates before dispatch:

- stable interrupt identity
- affected graph and optional affected task
- interrupt type (`scope`, `code_review`, `audit`, `ship`, `avoidance`)
- reason
- source/evidence references
- created-by and created-at
- state (`open`, `acknowledged`, `resolved`, `waived`)

Interrupt state changes are append-only history entries. Resolution and waiver require stable decision references (for example Orual/Uqbar decision identities) and do not copy external records into Titan.

Active interrupts (`open`, `acknowledged`) deterministically block planned-task readiness and therefore block Logres handoff for affected tasks.

## Out of scope in this slice

- MME-1235 repository/file/proposed-change bindings (task repo/file/test and change proposal contracts stay for that ticket).
- Catalogue persistence extraction for inventory models and any UI/browse stories.

## Durable plans and proposal promotion

Titan also owns provider-neutral, durable `Plan` and `PlanStep` contracts.

Plans retain deliberation lineage and lifecycle independently of any model session or execution runtime. `PlanMaterialization` records the explicit zero/one/many mapping from a step to execution requests while leaving those requests to Logres.

`PromotionRequest` is the boundary for turning an Elwin Twinkle into Titan-owned work. It retains the exact Twinkle version, provenance, selected context and Quain concept references. `TwinklePromoter` makes exact retries idempotent and rejects conflicting reuse of an idempotency key; it never mutates the source Twinkle.


## License

Copyright © 2026 Sifrious. All rights reserved. This is publicly viewable
proprietary software, not open-source software. See [LICENSE.md](LICENSE.md).
