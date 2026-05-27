# Feature: canonical-identifiers

## Purpose
- Define and enforce canonical identifier behavior across Foundry.
- Improve CLI ergonomics while preserving a single source of truth for identifiers.

## Decision Summary

Refreshed Through Spec: `001-accept-normalized-input-and-canonicalize-visibly`

- Canonical identifier normalization is owned by this standalone module because identifier policy is cross-cutting framework behavior.
- Accepted alternate input forms must canonicalize immediately, use canonical identifiers internally, and make normalization visible in user-facing output.
- Invalid identifiers must continue to fail clearly and deterministically rather than being silently guessed.

## Current State

## Open Questions
- Which CLI entry points should support safe normalization in the first implementation pass?
- Which normalized forms should be accepted beyond snake_case and surrounding whitespace?
- What is the best visible output shape for reporting normalization in text and JSON responses?

## Next Steps
- Implement safe normalized input forms such as snake_case for the initial relevant Foundry CLI commands where supported.
- Canonicalize accepted input immediately, use canonical identifiers internally after normalization, and use only the canonicalized identifier for internal resolution downstream.
- Ensure JSON and text output always show canonical identifiers, and make normalization visible in output rather than silent when it occurs.
- Cover the behavior with automated tests for accepted normalization, canonicalized output, and invalid identifiers that still fail clearly and deterministically.
