# Execution Spec: 020-interactive-architecture-explorer

## Historical Import Note

This spec was imported from the explicitly marked pre-canonical archive.

- Legacy name: `20 — Interactive Architecture Explorer`
- Legacy id: `20`
- Canonical pre-canonical id: `020`
- Imported module: `PreCanonical`
- Source archive: `_import/pre-canonical-specs.md`

## Original Pre-Canonical Spec

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
