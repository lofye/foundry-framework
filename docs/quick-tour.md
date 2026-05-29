# Quick Tour

The shortest reliable Foundry path is: understand the source-of-truth model, discover the main inspect and verify commands, then run the core compile loop.

In this repository use `./foundry ...`. In generated apps use `foundry ...`.

If you prefer a fixed onboarding sequence, start with [Guided Learning Paths](guided-learning-paths.html).

## Short path

1. Start with [Intro](intro.md).
2. Read [How It Works](how-it-works.md) and [Architecture Overview](architecture/architecture-overview.md).
3. Run `./foundry help inspect`, `./foundry help verify`, and `./foundry help generate`.
4. Run the core compile and verification loop below.
5. Open [Example Applications](example-applications.md) and start with the `Hello World` example.
6. Use the generated [Graph Overview](graph-overview.md), [Interactive CLI Index](cli-index.html), [Architecture Explorer](architecture-explorer.html), [Command Playground](command-playground.html), and [CLI Reference](cli-reference.md) once you want reference depth.

## Core commands

```bash
./foundry help inspect
./foundry help verify
./foundry compile graph --json
./foundry inspect graph --json
./foundry verify graph --json
./foundry verify pipeline --json
./foundry verify contracts --json
./foundry generate docs --format=markdown --json
```

Canonical docs are authored here, but the website repo renders and publishes the public docs site and version snapshots. `php scripts/build-docs.php` remains deprecated and exists only for framework-local preview output.

## What the generated docs cover

- curated landing pages for orientation
- generated graph snapshots
- generated schema and feature catalogs
- generated CLI and API surface reference
