Spec 22 — Guided Learning Paths

Purpose

Provide curated learning sequences that guide users through Foundry concepts in a structured and approachable way.

Scope

This feature must be implemented within the documentation system using existing content.

No personalization or dynamic path generation is required.

Requirements

1. Learning Paths

Provide at minimum the following paths:

- Learn Foundry in 30 minutes
- Build your first extension
- Understand the execution pipeline

2. Structure

Each learning path must:

- consist of an ordered list of steps
- reference existing documentation pages
- include:
  - title
  - short description
  - estimated time (optional but recommended)

3. Step Definition

Each step must include:

- step title
- link to documentation page
- short context explaining why the step matters

4. Navigation

Users must be able to:

- move forward and backward through steps
- see progress within a path
- jump to any step

5. Integration

Learning paths must integrate with:

- docs navigation
- CLI concepts
- explain targets where applicable

Constraints

- No branching logic
- No user-specific personalization
- No progress persistence required (optional enhancement later)

Non-Goals

- No adaptive learning system
- No quizzes or assessments
- No AI-driven recommendations

------------------------------------------------------------------------------------------

RESULT

The guided learning paths feature now ships as a static docs page at `guided-learning-paths.html`, implemented in [LearningPathsPage.php](/Users/lo_fye/code/foundry-framework/src/Documentation/LearningPathsPage.php#L11) and wired into the docs build and navigation in [DocsSiteBuilder.php](/Users/lo_fye/code/foundry-framework/src/Documentation/DocsSiteBuilder.php#L42) and [DocsSiteBuilder.php](/Users/lo_fye/code/foundry-framework/src/Documentation/DocsSiteBuilder.php#L1123). It includes the three required paths, ordered steps, progress UI, previous/next controls, step jumping, and step-level links back into existing docs, command-playground CLI concepts, and explain-oriented docs where they fit.

I also surfaced it in the main docs entry points through [intro.md](/Users/lo_fye/code/foundry-framework/docs/intro.md#L5) and [quick-tour.md](/Users/lo_fye/code/foundry-framework/docs/quick-tour.md#L3), and extended coverage in [DocsSiteBuilderTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/DocsSiteBuilderTest.php#L87). This stays fully static, uses existing content only, and doesn’t touch runtime or scaffold behavior.

Verified with `vendor/bin/phpunit tests/Unit/ApiSurfaceRegistryTest.php tests/Unit/DocsSiteBuilderTest.php tests/Unit/GraphDocsGeneratorTest.php tests/Integration/CLIApplicationTest.php tests/Integration/CLIInitAppCommandTest.php tests/Integration/LocalFoundryExecutableTest.php`.

5 files changed
+704
-0
Undo
docs/intro.md
docs/quick-tour.md
src/Documentation/DocsSiteBuilder.php
src/Documentation/LearningPathsPage.php
tests/Unit/DocsSiteBuilderTest.php


------------------------------------------------------------------------------------------



------------------------------------------------------------------------------------------



------------------------------------------------------------------------------------------

