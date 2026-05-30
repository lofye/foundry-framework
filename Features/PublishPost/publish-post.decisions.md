### Decision: keep publish-post as a canonical smoke feature fixture

Timestamp: 2026-05-29T00:00:00-04:00

**Context**

The framework repository contains a root `app/` smoke app used to exercise Foundry behavior. Its older feature fixture previously lived under `app/features/publish_post/`.

**Decision**

Move the fixture to `Features/PublishPost/`, canonicalize the feature slug as `publish-post`, and keep all authored runtime/test files under the feature root.

**Reasoning**

This preserves a small smoke fixture while aligning the repository with the canonical application feature layout.

**Alternatives Considered**

- Delete the smoke fixture entirely.
- Preserve the obsolete `app/features/publish_post/` location as a compatibility path.

**Impact**

Feature verification can validate the new app feature layout without relying on obsolete app feature source paths.

**Spec Reference**

Modules/FeatureSystem/specs/014-canonical-app-feature-roots-without-legacy-layout.md
