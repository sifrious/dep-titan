# Owned Diff

This package starts from the smallest Composer library shape: one PSR-4 namespace, one test suite, and no runtime dependency beyond PHP.

## `phpunit/phpunit ^12.5` — 2026-08-29, MME-1211

SEAM: borrowed — maintained by Sebastian Bergmann and PHPUnit contributors; 25 installed packages, 24 transitive.

PAYS WHEN: package behavior is verified without authoring a test runner or coupling tests to the Laravel application.

CHARGES WHEN: the 24-package development tree produces upgrade or supply-chain churn, or PHPUnit raises its PHP floor beyond the package support window; removal is confined to the package test suite.

TRIGGER: the package must prove its public contracts independently of Landing before any host consumes them.

Signals: 12.5.33 was released in August 2026; the repository reports active 12.x and 13.x release lines. The transitive count is justified because a maintained, independent test runner replaces an authored runner and its usage is confined to `tests/`.

## `phpstan/phpstan ^2.2` — 2026-08-29, MME-1211

SEAM: borrowed — maintained by Ondřej Mirtes, Markus Staab, Vincent Langlet, and contributors; one installed package and zero transitive packages.

PAYS WHEN: package contracts receive static type and control-flow checks without coupling quality gates to the Laravel application or authoring an analyzer.

CHARGES WHEN: analysis configuration is weakened to suppress real defects or a major release makes current source patterns invalid; removal is confined to one Composer script and one configuration file.

TRIGGER: Package v0.1 requires an independent static-analysis gate after its public work-kit contracts became executable.

Signals: Packagist reports active 2.2 lines and zero runtime dependencies beyond PHP.

## `WorkKit` and `WorkKitCompiler` — 2026-08-29, MME-1211

SEAM: authored substitution boundary — hosts supply portable planning records; the compiler owns mapping, dependency completeness, and executable presentation.

PAYS WHEN: Landing controllers, jobs, and browse views cannot infer lifecycle or present incomplete work as dispatchable.

CHARGES WHEN: the compiler accumulates task-graph readiness, interrupt/gate policy, or repository/change bindings that belong to later tickets.

TRIGGER: MME-1211 required a compiled work kit with explicit scope, verification, and completion before MME-1226 can define a task graph.
