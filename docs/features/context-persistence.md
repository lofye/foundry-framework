# Feature: context-persistence

## Purpose
- Introduce feature-level context files for Foundry.
- Support resumable, deterministic feature work.

## Current State
- Feature spec created.
- Feature state document created.
- Decision ledger created.
- 35D1 implementation completed.
- Execution spec 002-context-init-and-doctor created.
- 35D2 not yet implemented.

## Open Questions
- How much context creation should be automated in 35D2?
- When should execution specs become first-class inputs?

## Next Steps
- Implement execution spec 002-context-init-and-doctor.
- Implement context init CLI command.
- Implement context doctor CLI command.
- Validate real feature context files using doctor.