# Feature Spec: publish-post

## Purpose

Provide a small framework-owned smoke feature that exercises Foundry's application feature layout from `Features/PublishPost/`.

## Goals

- Preserve a deterministic HTTP feature fixture for compiler, verifier, and runtime smoke coverage.
- Keep feature-owned runtime code under `Features/PublishPost/src/`.
- Keep feature-owned tests under `Features/PublishPost/tests/`.

## Non-Goals

- This is not a production blog or publishing workflow.
- This is not a framework module.

## Constraints

- Auth, event, job, cache, schema, and manifest declarations must remain local to the feature root.
- Generated output must remain outside the authored feature source tree.

## Expected Behavior

A request to the smoke feature action creates a deterministic response shape for a post-like payload and emits/dispatches the declared smoke integrations.

## Acceptance Criteria

- `Features/PublishPost/feature.yaml` defines the `publish-post` feature.
- `Features/PublishPost/src/Action.php` contains the feature action.
- `Features/PublishPost/tests/` contains the feature-owned tests.

## Assumptions

- The root `app/` tree in this repository remains a framework smoke app.
