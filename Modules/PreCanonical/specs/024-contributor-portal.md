# Execution Spec: 024-contributor-portal

## Historical Import Note

This spec was imported from the explicitly marked pre-canonical archive.

- Legacy name: `24 — Contributor Portal`
- Legacy id: `24`
- Canonical pre-canonical id: `024`
- Imported module: `PreCanonical`
- Source archive: `_import/pre-canonical-specs.md`

## Original Pre-Canonical Spec

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
