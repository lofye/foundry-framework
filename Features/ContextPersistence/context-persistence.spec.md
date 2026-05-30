# Feature Spec: context-persistence

## Purpose

Provide a small framework-owned smoke feature that exercises canonical app feature context and runtime placement.

## Goals

- Preserve the existing context-persistence smoke fixture in `Features/ContextPersistence/`.
- Keep feature-owned runtime code under `Features/ContextPersistence/src/`.
- Keep feature-owned tests under `Features/ContextPersistence/tests/`.

## Non-Goals

- This fixture does not define framework module governance for `Modules/ContextPersistence/`.
- This fixture is not a user-facing example to expand during this refactor.

## Constraints

- The smoke feature must remain separate from the framework module of the same display name.
- Authored app feature files must not live under `app/features/`.

## Expected Behavior

The smoke action returns a simple success payload for the `context-persistence` feature.

## Acceptance Criteria

- `Features/ContextPersistence/feature.yaml` defines the `context-persistence` feature.
- `Features/ContextPersistence/src/Action.php` contains the feature action.
- `Features/ContextPersistence/tests/` contains the feature-owned tests.

## Assumptions

- The root `app/` tree in this repository remains a framework smoke app.
