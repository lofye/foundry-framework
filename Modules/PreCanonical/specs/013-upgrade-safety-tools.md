# Execution Spec: 013-upgrade-safety-tools

## Historical Import Note

This spec was imported from the explicitly marked pre-canonical archive.

- Legacy name: `13 — Upgrade Safety Tools`
- Legacy id: `13`
- Canonical pre-canonical id: `013`
- Imported module: `PreCanonical`
- Source archive: `_import/pre-canonical-specs.md`

## Original Pre-Canonical Spec

Goals

Spec 13 must:
	•	help users assess upgrade readiness
	•	detect deprecated usage
	•	detect incompatible extension usage
	•	reduce fear of framework upgrades

Requirements

1. Upgrade-check command

Provide a command such as:

foundry upgrade-check

with machine-readable output support.

2. Detectable issues

The upgrade-check system should detect at least:
	•	deprecated APIs or config
	•	removed/changed CLI usage
	•	incompatible extensions/packs
	•	unsupported schema versions
	•	risky graph/compiler changes where detectable

3. Actionable reporting

Reports should show:
	•	what is affected
	•	why it matters
	•	what version introduced the issue
	•	how to migrate

4. Deprecation metadata

Support framework-level deprecation metadata so the tool has structured information to work from.

5. Docs integration

Upgrade guidance should be documentable and referenceable.

Deliverables
	•	upgrade-check command
	•	deprecation metadata system
	•	actionable upgrade reports
	•	extension compatibility checks

Testing Requirements

Tests must cover:
	•	deprecated usage detection
	•	extension incompatibility detection
	•	JSON output
	•	report quality
	•	version-aware behavior

Coverage must remain ≥ 90%.
