# Execution Spec: 018-documentation-system-site-generation-structure

## Historical Import Note

This spec was imported from the explicitly marked pre-canonical archive.

- Legacy name: `18 — Documentation System (Site + Generation + Structure)`
- Legacy id: `18`
- Canonical pre-canonical id: `018`
- Imported module: `PreCanonical`
- Source archive: `_import/pre-canonical-specs.md`

## Original Pre-Canonical Spec

Goals

Spec 18 must:
	•	unify documentation structure
	•	support generated + hand-written content
	•	integrate with graph and schema metadata
	•	support static site output

⸻

Requirements

1. Documentation sources

Docs must support:
	•	hand-written pages (HTML/Markdown)
	•	generated content from:
	•	schemas
	•	features
	•	graph
	•	CLI commands

⸻

2. Build process

Provide a docs build process:

php scripts/build-docs.php

This must:
	•	generate versioned docs
	•	merge static + generated content
	•	output to /public or equivalent

⸻

3. Versioning

Docs must support:
	•	versioned snapshots
	•	mapping to framework tags

Example structure:

docs/
  versions/
    v0.4.0/
    v0.4.1/


⸻

4. Navigation structure

Docs must include:
	•	main navigation (top-level pages)
	•	side navigation (section-based)
	•	consistent linking between:
	•	intro
	•	how it works
	•	quick tour
	•	API/docs

⸻

5. Graph integration

Docs should be able to include:
	•	graph snapshots
	•	architecture explanations
	•	CLI outputs

⸻

6. CLI reference generation

CLI commands should be:
	•	discoverable
	•	documented automatically where possible

⸻

7. Static-first design

The docs system must:
	•	produce static output
	•	require no runtime backend
	•	be deployable to simple hosting (e.g., CDN)

⸻

8. Clean separation from app code

Docs generation must not:
	•	pollute application runtime
	•	interfere with framework execution

⸻

Deliverables
	•	unified docs structure
	•	build pipeline
	•	versioned docs output
	•	navigation system
	•	generated CLI/reference docs
	•	integration with graph and schemas

⸻

Testing Requirements

Tests must cover:
	•	docs generation
	•	version snapshot handling
	•	navigation consistency
	•	generated content correctness

Coverage must remain ≥ 90%.
