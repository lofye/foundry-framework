# Compiler Core Examples

Run these commands from the Foundry framework repository root with `php bin/foundry ...`.
If you copy the example slice into a generated app, switch the prefix to `foundry ...`.

## Example A: Simple App Compile

Source files (authored truth):
- `Features/PublishPost/feature.yaml`
- `Features/PublishPost/input.schema.json`
- `Features/PublishPost/output.schema.json`
- `Features/PublishPost/queries.sql`
- `Features/PublishPost/permissions.yaml`
- `Features/PublishPost/events.yaml`
- `Features/PublishPost/jobs.yaml`
- `Features/PublishPost/cache.yaml`

Compile:
```bash
php bin/foundry compile graph --json
```

Expected outputs:
- `app/.foundry/build/graph/app_graph.json`
- `app/.foundry/build/projections/routes_index.php`
- `app/.foundry/build/projections/feature_index.php`
- `app/.foundry/build/diagnostics/latest.json`

Quick check:
```bash
php bin/foundry inspect graph --json
php bin/foundry inspect build --json
```

## Example B: Impact Analysis

Change:
- edit `Features/PublishPost/input.schema.json`

Inspect impact:
```bash
php bin/foundry inspect impact --file=Features/PublishPost/input.schema.json --json
```

Expected response includes:
- affected nodes
- affected features
- affected projections
- recommended verification commands
- recommended tests
- risk level (`low|medium|high`)

## Example C: Migration / Codemod

Outdated manifest (`version: 1`, legacy `llm.risk` field):
```bash
php bin/foundry inspect migrations --json
php bin/foundry migrate definitions --dry-run --json
php bin/foundry migrate definitions --write --json
```

Typical rewrite performed by core migration rule:
- `version: 1` -> `version: 2`
- `llm.risk` -> `llm.risk_level`
- `auth.strategy` -> `auth.strategies`
- route methods normalized to uppercase

## Example D: Extension Hook

Inspect registered compiler extensions:
```bash
php bin/foundry inspect extensions --json
```

Extension foundation supports contributions for:
- pass stages
- projection emitters
- migration rules

A minimal extension can register one projection emitter and inspect it via the command above.
