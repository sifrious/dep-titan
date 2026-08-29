# Titan

Titan owns provider-neutral, durable `Plan` and `PlanStep` contracts.

Plans retain deliberation lineage and lifecycle independently of any model session or execution runtime. `PlanMaterialization` records the explicit zero/one/many mapping from a step to execution requests while leaving those requests to Logres.

`PromotionRequest` is the boundary for turning an Elwin Twinkle into Titan-owned work. It retains the exact Twinkle version, provenance, selected context and Quain concept references. `TwinklePromoter` makes exact retries idempotent and rejects conflicting reuse of an idempotency key; it never mutates the source Twinkle.

Run `composer install && composer test` to verify the contracts.
