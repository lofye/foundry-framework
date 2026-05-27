# Execution Spec: 015-official-example-applications

## Historical Import Note

This spec was imported from the explicitly marked pre-canonical archive.

- Legacy name: `15 — Official Example Applications`
- Legacy id: `15`
- Canonical pre-canonical id: `015`
- Imported module: `PreCanonical`
- Source archive: `_import/pre-canonical-specs.md`

## Original Pre-Canonical Spec

Goals

Spec 15 must:
	•	provide clear example applications
	•	demonstrate best practices
	•	reduce onboarding friction
	•	validate that Foundry works for real app shapes

Requirements

1. Example app set

Provide an official set of examples, ideally including:
	•	minimal hello-world app
	•	API-first example
	•	extension example
	•	workflow/event example
	•	one full reference app (blog with admin login and RSS feed via the spatie/feeds composer package). We should provide the user with everything (commands, prompts, content, etc) required so they can just type or paste to their LLM (or run on the commandline) and end up with a blog at the end.

2. Canonical patterns

Examples should demonstrate:
	•	feature structure
	•	manifests/schemas
	•	pipeline behavior
	•	graph inspection
	•	doctor usage
	•	CLI usage
	•	extension usage where relevant

3. Docs linkage

The docs site should clearly link to these examples and explain what each is meant to teach.

4. Quality expectations

Examples must be:
	•	small enough to read
	•	well organized
	•	representative of recommended patterns
	•	kept current with the framework

5. Thresholds alignment

Thresholds should be treated as the “real app” reference, while the smaller examples teach isolated ideas.

Deliverables
	•	official example app set
	•	docs integration for examples
	•	canonical usage patterns embodied in examples
	•	Thresholds positioned as the richer reference application

Testing Requirements

Tests must cover:
	•	example app generation/build validity where practical
	•	docs links/metadata for examples
	•	example architecture inspection behavior where practical

Coverage must remain ≥ 90%.
