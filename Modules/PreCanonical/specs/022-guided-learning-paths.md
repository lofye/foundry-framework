# Execution Spec: 022-guided-learning-paths

## Historical Import Note

This spec was imported from the explicitly marked pre-canonical archive.

- Legacy name: `22 — Guided Learning Paths`
- Legacy id: `22`
- Canonical pre-canonical id: `022`
- Imported module: `PreCanonical`
- Source archive: `_import/pre-canonical-specs.md`

## Original Pre-Canonical Spec

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
