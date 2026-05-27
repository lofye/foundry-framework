# Execution Spec: 009-framework-doctor-and-environment-diagnostics

## Historical Import Note

This spec was imported from the explicitly marked pre-canonical archive.

- Legacy name: `9 — Framework Doctor and Environment Diagnostics`
- Legacy id: `9`
- Canonical pre-canonical id: `009`
- Imported module: `PreCanonical`
- Source archive: `_import/pre-canonical-specs.md`

## Original Pre-Canonical Spec

Goals

Spec 9 must:
	•	strengthen foundry doctor
	•	make installation problems obvious
	•	validate architecture integrity
	•	validate runtime environment assumptions
	•	produce machine-readable diagnostics

Requirements

1. Doctor command expansion

foundry doctor should validate at least:
	•	PHP version compatibility
	•	required extensions
	•	writable/cache/build directories
	•	framework install completeness
	•	extension compatibility
	•	graph integrity
	•	config/schema validity
	•	route/pipeline consistency
	•	generated metadata freshness where applicable

2. Structured output

Doctor output should support:
	•	human-readable mode
	•	JSON mode
	•	clear exit codes

Example:

foundry doctor
foundry doctor --json

3. Diagnostic codes

Use stable diagnostic codes for major failure categories so they can be:
	•	documented
	•	searched
	•	consumed by tools/LLMs

4. Fix guidance

Where practical, diagnostics should include:
	•	what failed
	•	why it matters
	•	how to fix it

5. App-level extensibility

Allow applications and extensions to register additional doctor checks.

Deliverables
	•	stronger foundry doctor
	•	structured JSON diagnostics
	•	stable diagnostic codes
	•	fix guidance
	•	app/extension diagnostic hook support

Testing Requirements

Tests must cover:
	•	doctor checks
	•	JSON output
	•	exit codes
	•	extension/app-added checks
	•	representative failure scenarios

Coverage must remain ≥ 90%.
