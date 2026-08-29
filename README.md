# Titan

Titan owns provider-neutral, durable `Plan` and `PlanStep` contracts.

Plans retain deliberation lineage and lifecycle independently of any model session or execution runtime. `PlanMaterialization` records the explicit zero/one/many mapping from a step to execution requests while leaving those requests to Logres.

Run `composer install && composer test` to verify the contracts.
