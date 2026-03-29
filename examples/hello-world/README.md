# Hello World

`examples/hello-world` is the smallest current Foundry app slice. Read this example first.

What it teaches:

- the minimum modern `app/features/<feature>` shape
- source of truth vs generated output boundaries
- one meaningful feature test instead of placeholder test files
- the current compile, inspect, doctor, and verify loop

Structure notes:

- `app/features/say_hello/*` is the authored source of truth
- `app/generated/*` is intentionally not committed here; compile it from source
- `context.manifest.json` is committed so the feature folder remains fully inspectable beside its manifest and schemas

How to use it:

1. Copy `examples/hello-world/app/features/say_hello` into a Foundry app's `app/features/` tree.
2. From that app, run:

```bash
foundry compile graph --json
foundry inspect feature say_hello --json
foundry inspect graph --command="GET /hello" --json
foundry doctor --feature=say_hello --json
foundry verify graph --json
foundry verify pipeline --json
foundry verify contracts --json
```

Read these files in order:

1. `app/features/say_hello/feature.yaml`
2. `app/features/say_hello/action.php`
3. `app/features/say_hello/output.schema.json`
4. `app/features/say_hello/tests/say_hello_feature_test.php`
