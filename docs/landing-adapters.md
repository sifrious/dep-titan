# Landing adapter mapping

Titan consumes portable input records. It does not own Eloquent models, Landing tables, browse/inspect routes, or provider SDKs. Field-by-field catalogue extraction and persistence cutover remain on the child tickets.

| Landing record | Titan domain behavior | Landing adapter residue |
| --- | --- | --- |
| `CodeAction` | Work-preparation evidence. Its declared first action becomes the work-kit first action. Source id is preserved as `code_action` provenance. | Eloquent `App\Models\CodeAction`, `code_actions` table, browse UI ([MME-1194](https://linear.app/sifirous/issue/MME-1194)), persistence extraction ([MME-873](https://linear.app/sifirous/issue/MME-873)). Runtime/provider fields stay out of Titan. |
| `PlanCommit` | Planned source-control intent attached as provenance. Distinct from an observed Git commit. | Eloquent `App\Models\PlanCommit`, browse UI ([MME-1201](https://linear.app/sifirous/issue/MME-1201)), persistence extraction ([MME-1029](https://linear.app/sifirous/issue/MME-1029)). Observed commits belong to Funes. Repository/file bindings belong to [MME-1235](https://linear.app/sifirous/issue/MME-1235). |
| `PlanPr` | Planned review intent attached as provenance. Distinct from an observed provider PR. | Eloquent `App\Models\PlanPr`, browse UI ([MME-1202](https://linear.app/sifirous/issue/MME-1202)), persistence extraction ([MME-1032](https://linear.app/sifirous/issue/MME-1032)). Observed PR history belongs to Funes. |
| `PlanOption` | Planning alternative. Selection and dismissal are explicit `PlanningRecords` transitions. The selected option's outcome becomes the work-kit outcome. Unselected options cannot compile. | Eloquent `App\Models\PlanOption`, inspect UI ([MME-1207](https://linear.app/sifirous/issue/MME-1207)), persistence extraction ([MME-1031](https://linear.app/sifirous/issue/MME-1031)). |
| `Checkin` | Planning checkpoint. Recording is an explicit transition and is not inferred from timestamps. Runtime telemetry is not accepted. | Eloquent `App\Models\Checkin`, inspect UI ([MME-1206](https://linear.app/sifirous/issue/MME-1206)), mixed-ownership classification ([MME-863](https://linear.app/sifirous/issue/MME-863)). Execution current-state belongs to Logres; durable observations belong to Funes. |

Landing user stories keep their original acceptance criteria. Display tickets consume Titan read models later; this slice does not implement browse or inspect routes.

MME-1226 now defines task-graph and readiness semantics in-package. Remaining non-catalogue residues stay intentionally separated:

- **MME-1234** interrupt/gate families (audit, scope, ship, code-review, avoidance policy objects)
- **MME-1235** repository/file/test/proposed-change bindings and approval objects tied to those bindings
