# Execution Spec: 027-examples-taxonomy-cleanup

## Historical Import Note

This spec was imported from the explicitly marked pre-canonical archive.

- Legacy name: `27 - Examples Taxonomy Cleanup`
- Legacy id: `27`
- Canonical pre-canonical id: `027`
- Imported module: `PreCanonical`
- Source archive: `_import/pre-canonical-specs.md`

## Original Pre-Canonical Spec

Purpose

Make example terminology consistent across the framework repo.

Required terms:
	•	canonical
	•	reference
	•	framework

Remove outdated terminology and remove Thresholds references from the framework examples docs/catalog.

⸻

Required Changes
	1.	Update examples/README.md
	2.	Update examples/catalog.php
	3.	Update docs/example-applications.md
	4.	Update any related README/docs references that still use the old example taxonomy

⸻

Required Taxonomy

Use exactly these three categories:
	•	canonical
	•	reference
	•	framework

Do not use:
	•	official
	•	supplemental
	•	framework-example
	•	reference-kit
	•	thresholds

⸻

Category Intent

canonical
= primary copyable example applications showing how to build with Foundry today

reference
= richer kits or larger build references that are still inside the repo

framework
= examples that explain framework/compiler/tooling surfaces

⸻

Thresholds

Remove Thresholds from:
	•	examples/README.md
	•	examples/catalog.php
	•	docs/example-applications.md

Thresholds is no longer an in-repo example and must not appear as part of the framework example taxonomy.

⸻

Consistency Rules

The same taxonomy must be used consistently in:
	•	prose
	•	array keys
	•	labels
	•	docs headings
	•	comments if relevant

⸻

Acceptance Criteria
	•	the code and docs use only: canonical, reference, framework
	•	no Thresholds references remain in framework examples docs/catalog
	•	no stale references to Dashboard or AI Pipeline remain if those examples were removed
	•	docs/example-applications.md matches the actual current example set
