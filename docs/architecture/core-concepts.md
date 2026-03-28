# Core Concepts

Foundry organizes architecture around explicit contracts and graph-native compilation.

## Semantic compiler

The compiler reads feature-local sources and specs, validates contracts, links dependencies, and emits graph/runtime projections.

## Canonical application graph

The graph is the system model used for inspection, diagnostics, impact analysis, and downstream generation. It includes typed nodes for features, routes, schemas, jobs, events, caches, pipeline stages, execution plans, and capability-specific metadata.

## Feature contracts

A feature is a bounded unit with explicit files such as:

- `feature.yaml`
- `action.php`
- `input.schema.json`
- `output.schema.json`
- `context.manifest.json`

Optional files extend behavior for jobs, events, cache, permissions, and prompts.

## Deterministic projections

Generated indexes are derived from source contracts and graph state. Runtime uses generated projections on the hot path instead of ad-hoc scanning.
