# Execution Spec: 023-interactive-cli-index

## Historical Import Note

This spec was imported from the explicitly marked pre-canonical archive.

- Legacy name: `23 — Interactive CLI Index`
- Legacy id: `23`
- Canonical pre-canonical id: `023`
- Imported module: `PreCanonical`
- Source archive: `_import/pre-canonical-specs.md`

## Original Pre-Canonical Spec

Purpose

Provide a searchable and filterable index of all Foundry CLI commands to improve discoverability and usability.

Scope

This feature must be built using CLI metadata already exposed by the framework.

Requirements

1. Command Index

- Display all commands in a structured list
- Include:
  - command name
  - category
  - description

2. Search

- Full-text search across:
  - command names
  - descriptions
  - categories

3. Filtering

Users must be able to filter by:

- category
- pipeline stage (if applicable)
- extension (if applicable)
- command type

4. Command Detail

Each command must link to:

- detailed documentation
- command playground view
- related explain targets (if applicable)

5. Metadata Source

- Use ApiSurfaceRegistry or equivalent CLI metadata source
- Data must remain consistent with CLI help output

Constraints

- Must remain deterministic
- Must not require command execution
- Must reflect actual CLI contract

Non-Goals

- No runtime command execution
- No user customization
