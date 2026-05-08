# Spec 24 — Contributor Portal

Purpose

Provide a centralized, structured entry point for developers who want to understand, extend, or contribute to Foundry.

Scope

This feature is implemented as documentation organization and content structuring.

Requirements

1. Architecture Overview

- Provide a clear explanation of:
  - core architecture (collect → analyze → assemble → render)
  - graph system
  - explain system
  - CLI structure

2. Extension Guide

- Explain how to:
  - create extensions
  - register collectors, analyzers, contributors
  - integrate with graph and explain

3. Contribution Guidelines

- Include:
  - coding standards
  - testing requirements (≥ 90% coverage)
  - spec discipline rules
  - PR checklist reference

4. Development Workflow

- Explain:
  - safe edit loop
  - compile / inspect / verify flow
  - testing workflow

5. Roadmap Visibility

- Provide a clear view of:
  - current priorities
  - upcoming areas of work
  - non-goals

Constraints

- Must align with AGENTS.md
- Must not contradict specs or tests
- Must remain accurate and deterministic

Non-Goals

- No community platform
- No issue tracker replacement
- No discussion system

------------------------------------------------------------------------------------------

RESULT

Added a new contributor hub at [contributor-portal.md](/Users/lo_fye/code/foundry-framework/docs/contributor-portal.md). It now centralizes the framework contributor mental model and workflow: architecture overview (`collect -> analyze -> assemble -> render`), graph and explain boundaries, CLI structure, extension guidance, contribution rules, the safe edit loop, and a contributor-facing roadmap section. I also tightened the extension guidance in [extension-author-guide.md](/Users/lo_fye/code/foundry-framework/docs/extension-author-guide.md) so it explicitly distinguishes extension-contributed analyzers/contributors from framework-owned explain collectors.

I wired the portal into the generated docs navigation in [DocsSiteBuilder.php](/Users/lo_fye/code/foundry-framework/src/Documentation/DocsSiteBuilder.php#L1683), exposed the existing checklist as a real docs page, and added cross-links from [intro.md](/Users/lo_fye/code/foundry-framework/docs/intro.md#L10), [how-it-works.md](/Users/lo_fye/code/foundry-framework/docs/how-it-works.md#L5), [contributor-pr-checklist.md](/Users/lo_fye/code/foundry-framework/docs/contributor-pr-checklist.md#L5), and [README.md](/Users/lo_fye/code/foundry-framework/README.md#L252). Coverage was updated in [DocsSiteBuilderTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/DocsSiteBuilderTest.php#L35) to assert the new portal/checklist pages and link rewrites.

Verified with `vendor/bin/phpunit tests/Unit/DocsSiteBuilderTest.php tests/Unit/GraphDocsGeneratorTest.php tests/Integration/CLIInitAppCommandTest.php tests/Integration/LocalFoundryExecutableTest.php`. No runtime or scaffold behavior was changed.

8 files changed
+179
-3
Undo
README.md
docs/contributor-portal.md
docs/contributor-pr-checklist.md
docs/extension-author-guide.md
docs/how-it-works.md
docs/intro.md
src/Documentation/DocsSiteBuilder.php
tests/Unit/DocsSiteBuilderTest.php

------------------------------------------------------------------------------------------

------------------------------------------------------------------------------------------

------------------------------------------------------------------------------------------
