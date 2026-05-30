# Workflow And Events Example

`examples/workflow-events` is the canonical event-driven example. It shows a small editorial flow where each feature remains inspectable, the workflow stays explicit, and the event chain is visible in the graph.

What it teaches:

- workflow definitions under `app/definitions/workflows`
- event emit and subscribe relationships between feature-local actions
- route params, event payloads, and job dispatches without extra framework noise
- compile, inspect, doctor, and verify loops for a workflow-shaped slice

Source-of-truth rules:

- copy `Features/*` and `app/definitions/workflows/*`
- `app/generated/*` is intentionally not committed here; compile it from source
- there is no committed `public/index.php`; use the scaffolded app shell when you want a runnable app

From a generated Foundry app, run:

```bash
foundry compile graph --json
foundry inspect graph --event=story.review_requested --json
foundry graph inspect --workflow=editorial --json
foundry inspect feature publish-story --json
foundry doctor --feature=publish-story --json
foundry verify graph --json
foundry verify workflows --json
foundry verify pipeline --json
```

Read these folders in order:

- `Features/SubmitStory`
- `Features/ReviewStory`
- `Features/PublishStory`
- `app/definitions/workflows/editorial.workflow.yaml`
