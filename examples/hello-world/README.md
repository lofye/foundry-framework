# Hello World Example

`examples/hello-world` is the smallest official Foundry application slice. It is meant to be read first.

What it teaches:

- one feature folder from top to bottom
- current manifest shape (`version: 2`)
- colocated input/output schemas, context manifest, and tests
- doctor, graph inspection, and execution-plan inspection on a minimal route

How to use it:

1. Copy `examples/hello-world/app/features/say_hello` into a Foundry app's `app/features/` tree.
2. Optionally copy `examples/hello-world/app/platform/public/index.php`.
3. From that generated app, run:

```bash
php vendor/bin/foundry compile graph --json
php vendor/bin/foundry inspect graph --command="GET /hello" --json
php vendor/bin/foundry inspect graph --feature=say_hello --json
php vendor/bin/foundry doctor --feature=say_hello --json
php vendor/bin/foundry verify graph --json
php vendor/bin/foundry verify pipeline --json
```
