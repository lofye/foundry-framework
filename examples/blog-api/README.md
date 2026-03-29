# Blog API Example

`examples/blog-api` is the canonical HTTP API example. It stays intentionally small: one public collection route, one public item route, and one protected write route.

What it teaches:

- one feature folder per route
- public versus protected HTTP features
- route params, schemas, and event edges without extra scaffolding noise
- the normal compile, inspect, doctor, and verify loop

Source-of-truth rules:

- copy only `app/features/*`
- `app/generated/*` is intentionally not committed here; compile it from source
- there is no committed `public/index.php`; use the framework scaffold when you want a runnable app shell

From a generated Foundry app, run:

```bash
foundry compile graph --json
foundry inspect graph --command="GET /posts" --json
foundry inspect feature publish_post --json
foundry doctor --feature=list_posts --json
foundry verify graph --json
foundry verify pipeline --json
foundry verify contracts --json
```

Read these folders in order:

- `app/features/list_posts`
- `app/features/view_post`
- `app/features/publish_post`
