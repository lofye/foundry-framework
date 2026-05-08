# Spec 20 — Interactive Architecture Explorer

Purpose

Provide a fully interactive visualization of the Foundry architecture graph to improve understanding, navigation, and discovery.

Scope

This feature must be implemented within the documentation website using the existing graph JSON produced by the framework.

No new backend graph system may be introduced.

Requirements

1. Graph Visualization
- Render nodes and edges from graph JSON
- Support a stable layout (force-directed or hierarchical)
- Nodes must include:
  - identifier
  - type
  - label

2. Node Interaction
- Clicking a node must:
  - open the corresponding documentation page, or
  - display node details inline (type, relationships, metadata)

3. Filtering
- Users must be able to filter nodes by:
  - type (feature, route, workflow, event, schema, command, extension)
  - extension
  - pipeline stage (if applicable)

4. Search
- Provide full-text search over:
  - node name
  - type
  - labels
- Search must highlight matching nodes in the graph

5. Relationship Highlighting
- Selecting a node must highlight:
  - dependencies
  - dependents
- Non-related nodes should be visually de-emphasized

6. Navigation Integration
- Graph must integrate with docs:
  - node → docs
  - docs → node (via deep link or anchor)

Constraints

- Graph is read-only
- No graph mutation or editing
- No AI or inference
- Must be fully deterministic based on graph JSON

Non-Goals

- No live runtime data
- No editing or annotation
- No user accounts or saved views

------------------------------------------------------------------------------------------

RESULT

The interactive explorer now lives in [DocsSiteBuilder.php](/Users/lo_fye/code/foundry-framework/src/Documentation/DocsSiteBuilder.php#L37) and [DocsSiteBuilder.php](/Users/lo_fye/code/foundry-framework/src/Documentation/DocsSiteBuilder.php#L183). It generates `architecture-explorer.html` from the existing compiled graph JSON, with a deterministic SVG layout, full-text search, type/extension/pipeline filters, clickable nodes, dependency/dependent highlighting, inline metadata, and related-doc links. This stays read-only and does not introduce any new backend graph system.

Docs now link both directions: [GraphDocsGenerator.php](/Users/lo_fye/code/foundry-framework/src/Documentation/GraphDocsGenerator.php#L92) adds explorer entry points from graph overview and node-oriented reference pages, while [reference.md](/Users/lo_fye/code/foundry-framework/docs/reference.md#L5) and [quick-tour.md](/Users/lo_fye/code/foundry-framework/docs/quick-tour.md#L7) surface the explorer in the docs flow. Coverage was updated in [DocsSiteBuilderTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/DocsSiteBuilderTest.php#L87) and [GraphDocsGeneratorTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/GraphDocsGeneratorTest.php#L81). Because all changes were confined to the framework repo, I did not touch the website repo, `framework/public/`, or scaffold/runtime paths.

Verified with `vendor/bin/phpunit tests/Unit/DocsSiteBuilderTest.php tests/Unit/GraphDocsGeneratorTest.php tests/Integration/CLIInitAppCommandTest.php tests/Integration/LocalFoundryExecutableTest.php`.

6 files changed
+1026
-2
Undo
docs/quick-tour.md
docs/reference.md
src/Documentation/DocsSiteBuilder.php
src/Documentation/GraphDocsGenerator.php
tests/Unit/DocsSiteBuilderTest.php
tests/Unit/GraphDocsGeneratorTest.php

------------------------------------------------------------------------------------------

------------------------------------------------------------------------------------------

------------------------------------------------------------------------------------------
