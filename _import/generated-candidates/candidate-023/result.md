Subject
  thresholds.create
  kind: route_action

Summary
  Creates a new threshold for the authenticated user and triggers downstream
  milestone tracking and notification workflows.

Execution Flow
  request
  -> auth guard
  -> permission guard (thresholds.create)
  -> thresholds feature action
  -> threshold.created event
  -> streak.update workflow
  -> notification.dispatch job

Depends On
  feature:account
  feature:thresholds
  schema:threshold
  workflow:streak.update

Used By
  route: POST /thresholds
  command: thresholds:create (CLI)

Emits
  event: threshold.created

Triggers
  workflow: streak.update
  job: notification.dispatch

Related Commands
  foundry inspect pipeline --json
  foundry doctor
  foundry graph inspect thresholds

Related Docs
  /docs/features/thresholds
  /docs/workflows/streaks

Diagnostics
  ✓ No issues detected


⸻

Same Command with --deep

foundry explain thresholds.create --deep


⸻

Output (Deep)

Subject
  thresholds.create
  kind: route_action

Summary
  Creates a new threshold for the authenticated user and triggers downstream
  milestone tracking and notification workflows.

Execution Flow (Detailed)
  Stage 1: request normalization
  Stage 2: auth guard (requires session)
  Stage 3: permission guard
    - required: thresholds.create
    - resolved from: account.roles -> permissions map

  Stage 4: feature execution
    - handler: thresholds/CreateThresholdHandler
    - writes: threshold schema

  Stage 5: event emission
    - threshold.created

  Stage 6: workflow trigger
    - streak.update
    - condition: category == "health"

  Stage 7: job dispatch
    - notification.dispatch

Graph Relationships (Expanded)
  inbound:
    route: POST /thresholds
  outbound:
    event: threshold.created
    workflow: streak.update
    job: notification.dispatch

Permissions
  thresholds.create
    - defined in: feature:thresholds
    - enforced by: pipeline.permissions

Schema Interaction
  threshold
    - fields: title, category, timestamp, user_id

Diagnostics
  ✓ No structural issues detected


⸻

Same Command with --json

foundry explain thresholds.create --json


⸻

Output (JSON)

{
  "subject": {
    "id": "thresholds.create",
    "kind": "route_action",
    "label": "Create Threshold"
  },
  "summary": "Creates a new threshold for the authenticated user and triggers downstream workflows.",
  "executionFlow": [
    "auth.guard",
    "permission.guard",
    "thresholds.create",
    "event.threshold.created",
    "workflow.streak.update",
    "job.notification.dispatch"
  ],
  "dependencies": [
    "feature:account",
    "feature:thresholds",
    "schema:threshold"
  ],
  "emits": ["event:threshold.created"],
  "triggers": [
    "workflow:streak.update",
    "job:notification.dispatch"
  ],
  "diagnostics": [],
  "relatedCommands": [
    "foundry inspect pipeline",
    "foundry doctor"
  ]
}


⸻

🧪 Example 2 — Explaining a Feature

Command

foundry explain feature:thresholds


⸻

Output

Subject
  thresholds
  kind: feature

Summary
  Manages threshold records, including creation, categorization, and lifecycle events.

Responsibilities
  - create thresholds
  - store metadata and notes
  - emit lifecycle events
  - integrate with workflows (streaks, insights)

Provides
  route_action: thresholds.create
  event: threshold.created
  schema: threshold

Depends On
  feature:account

Used By
  workflow: streak.update
  workflow: insight.generate

Graph Position
  central feature node with connections to:
    - routes
    - schemas
    - workflows
    - events

Diagnostics
  ✓ No issues detected


⸻

🧪 Example 3 — Explaining a Broken Case

Command

foundry explain thresholds.create


⸻

Output (With Problems)

Subject
  thresholds.create
  kind: route_action

Summary
  Creates a threshold, but the current configuration is incomplete.

Execution Flow
  request
  -> auth guard
  -> permission guard
  -> thresholds feature action

Diagnostics
  ✗ Missing permission mapping: thresholds.create
    The permission is required but not mapped in account.roles

  ✗ Unresolved workflow: streak.update
    Referenced workflow not registered in graph

  ⚠ Event emitted but not handled: threshold.created

Suggested Fixes
  - Add permission mapping in account/manifest.yaml
  - Register workflow: streak.update
  - Add event listener or workflow for threshold.created

👉 This is where foundry explain becomes insanely valuable

⸻

🧪 Example 4 — Ambiguous Target

Command

foundry explain create


⸻

Output

Ambiguous target: "create"

Did you mean:

  thresholds.create        (route_action)
  journals.create          (route_action)
  users.create             (route_action)

Use a more specific target, or prefix with type:

  foundry explain route:thresholds.create
  foundry explain feature:thresholds


⸻

🧪 Example 5 — Explaining a Workflow

Command

foundry explain workflow:streak.update


⸻

Output

Subject
  streak.update
  kind: workflow

Summary
  Updates user streak counts based on new threshold activity.

Triggered By
  event: threshold.created

Logic
  - evaluate threshold category
  - update streak counters
  - emit milestone events

Emits
  event: streak.milestone_reached

Depends On
  feature:thresholds
  feature:account

Triggers
  job: notification.dispatch

Diagnostics
  ✓ No issues detected


⸻

🧪 Example 6 — Explain + Graph Navigation

Command

foundry explain thresholds.create --neighbors


⸻

Output

Graph Neighbors

Inbound
  route: POST /thresholds

Outbound
  event: threshold.created
  workflow: streak.update

Lateral
  schema: threshold
  feature: thresholds


⸻

🧪 Example 7 — Explain + Markdown (Docs Integration)

Command

foundry explain thresholds.create --markdown


⸻

Output
