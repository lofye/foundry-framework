# Execution Spec: 010-project-scaffolding-generator

## Historical Import Note

This spec was imported from the explicitly marked pre-canonical archive.

- Legacy name: `10 — Project Scaffolding Generator`
- Legacy id: `10`
- Canonical pre-canonical id: `010`
- Imported module: `PreCanonical`
- Source archive: `_import/pre-canonical-specs.md`

## Original Pre-Canonical Spec

Goals

Spec 10 must:
	•	provide a clean app generator
	•	reduce friction for first-time users
	•	produce a canonical Foundry project structure
	•	include minimal but instructive starter examples

Requirements

1. New project command

Provide a command such as:

foundry new my-app

or equivalent stable scaffolding flow.

2. Starter modes

Support at least:
	•	minimal app
	•	standard app
	•	API-first app

Optional later:
	•	example/demo app templates

3. Generated project structure

Scaffolded projects must include:
	•	feature structure
	•	config structure
	•	pipeline setup
	•	basic auth or placeholder integration where appropriate
	•	example tests
	•	example docs/inspectability path

4. First-run guidance

After generation, output clear next steps such as:
	•	install dependencies
	•	run app
	•	inspect graph
	•	run doctor
	•	run tests

5. Generated app should demonstrate Foundry

A starter app should make it easy to see:
	•	architecture graph
	•	pipeline
	•	CLI inspection
	•	docs/metadata flow where practical

Deliverables
	•	project generator command
	•	starter templates
	•	first-run guidance
	•	canonical generated app structure

Testing Requirements

Tests must cover:
	•	scaffold generation
	•	template selection
	•	generated file correctness
	•	first-run behavior
	•	generated tests/app boot behavior

Coverage must remain ≥ 90%.
