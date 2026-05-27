# Execution Spec: 004-roadmap-phase-4-documentation-system-and-website-integration-wr

## Historical Import Note

This spec was imported from the explicitly marked pre-canonical archive.

- Legacy name: `4 - Roadmap Phase 4 - Documentation System and Website Integration (WR)`
- Legacy id: `4`
- Canonical pre-canonical id: `004`
- Imported module: `PreCanonical`
- Source archive: `_import/pre-canonical-specs.md`

## Original Pre-Canonical Spec

To make auto-generated, version-aware documentation practical and deterministic, the website repo must include a dedicated documentation build pipeline that reads from the Foundry framework submodule and emits both human-readable and machine-readable docs artifacts.


Documentation Build Pipeline

Goal

Implement a deterministic documentation build pipeline inside the website repo that reads from the Foundry framework submodule at:

framework/

and produces:
	•	human-authored rendered documentation
	•	machine-derived reference documentation
	•	version-aware website pages
	•	final rendered static site output

The docs pipeline must ensure that the website documentation always reflects the exact checked-out framework submodule commit or tag.

⸻

Documentation Pipeline Responsibilities

The documentation build system must:
	1.	read authored content from the website repo
	2.	inspect the Foundry framework submodule
	3.	extract structured framework metadata
	4.	generate reference documentation
	5.	merge authored and generated documentation
	6.	render final HTML output for the site
	7.	expose framework version information in the rendered docs

⸻

Required Inputs

The docs build pipeline must use these input categories.

1. Authored content

Human-written files in the website repo, such as:

content/
  homepage/
  docs/
    authored/

These include:
	•	homepage copy
	•	how-foundry-works narrative
	•	philosophy and goals
	•	onboarding guides
	•	conceptual explanations

2. Framework source and metadata

Read from the Foundry submodule:

framework/

This includes, where present:
	•	README and architecture docs
	•	CLI command definitions
	•	compiler and graph metadata
	•	pipeline/guard/interceptor definitions
	•	extension and pack definitions
	•	diagnostics catalogs
	•	version metadata
	•	machine-readable exports if present

3. Templates

Reusable rendering templates in the website repo, such as:

templates/


⸻

Required Outputs

The docs build pipeline must produce:

Generated content artifacts

For example:

content/docs/generated/
  cli-reference.md
  graph-reference.md
  pipeline-reference.md
  diagnostics-reference.md
  extension-reference.md
  pack-reference.md
  version-metadata.json
  framework-summary.md

Rendered site output

For example:

public/
  index.html
  how-foundry-works.html
  docs/
    index.html
    getting-started/
    core-concepts/
    execution-model/
    framework-capabilities/
    cli/
    extension-development/
    ai-development/
    architecture-reference/

If you prefer a build/ directory first and then deployment to public/, that is also acceptable, but it must be deterministic and documented.

⸻

Required Build Steps

Implement the docs pipeline with explicit steps.

Step 1 — Read framework version

Detect and store the framework version from the submodule checkout.

This should include at least:
	•	current git commit hash
	•	current git tag if present
	•	framework package version if available

Emit this into generated metadata such as:

content/docs/generated/version-metadata.json

Step 2 — Extract framework metadata

Implement scripts that inspect the framework submodule and extract structured information such as:
	•	CLI command metadata
	•	graph/compiler concepts
	•	pipeline stages
	•	guards
	•	interceptors
	•	diagnostics codes
	•	extensions
	•	packs
	•	migration/codemod concepts
	•	version compatibility concepts

Prefer machine-readable exports from the framework if they already exist.
If they do not exist, implement deterministic extraction in the website repo.

Step 3 — Generate reference docs

Transform extracted metadata into Markdown or intermediate content files for the docs area.

These generated docs should be reference-oriented and precise.

Step 4 — Merge authored + generated docs

Combine human-authored docs with generated reference docs.

Human-authored docs explain:
	•	why Foundry exists
	•	how to think about the architecture
	•	how humans and LLMs collaborate

Generated docs explain:
	•	exact commands
	•	exact structures
	•	exact metadata
	•	exact reference material

Step 5 — Render final HTML

Use templates/layouts to render final website pages.

Step 6 — Emit build manifest

Emit a build manifest containing at least:
	•	framework version
	•	framework commit
	•	build timestamp
	•	generated doc files
	•	rendered page outputs

⸻

Required Scripts

Implement the docs pipeline using explicit scripts in:

scripts/

At minimum, provide scripts or equivalent commands for:

scripts/build-docs.php
scripts/extract-framework-version.php
scripts/extract-cli.php
scripts/extract-graph-metadata.php
scripts/extract-pipeline-metadata.php
scripts/extract-diagnostics.php
scripts/extract-extensions.php
scripts/render-pages.php

These may be reorganized, but the separation of responsibilities should remain clear.

⸻

Required Determinism

The docs pipeline must be deterministic.

Given:
	•	the same website repo
	•	the same framework submodule commit/tag
	•	the same authored docs
	•	the same templates

the generated docs and rendered output must be the same.

Avoid non-deterministic timestamps inside content unless they are placed only in manifests or hidden metadata.

⸻

Required Documentation Categories

The docs build pipeline must support generation/rendering for these categories:
	•	getting started
	•	core concepts
	•	execution model
	•	framework capabilities
	•	CLI reference
	•	extension development
	•	AI development
	•	architecture reference

The system should make it easy to add future categories without redesigning the build pipeline.

⸻

Required Machine-Readable Outputs

In addition to human-readable docs, emit machine-readable reference files where useful, such as:
	•	version-metadata.json
	•	cli-reference.json
	•	graph-reference.json
	•	pipeline-reference.json
	•	diagnostics-reference.json
	•	extensions-reference.json

These may be placed under:

content/docs/generated/

or another documented location.

These files will help future LLM tooling and structured integrations.

⸻

Required Version Awareness

The docs pipeline must make the current documented framework version visible on the website.

At minimum, surface:
	•	framework version/tag
	•	commit hash
	•	“docs generated from this version” note

Later versioned docs support should remain possible, so structure the pipeline with that future in mind.

⸻

Required Failure Behavior

If the framework submodule is missing, uninitialized, or in an invalid state, the docs build must fail clearly with actionable errors.

Examples:
	•	framework submodule not initialized
	•	framework version not detectable
	•	extraction step failed
	•	required generated metadata missing

These failures should not silently produce stale docs.

⸻

Required Testing

The docs build pipeline must have automated tests.

At minimum, test:
	•	framework version extraction
	•	CLI metadata extraction
	•	graph/pipeline/diagnostics extraction
	•	generated reference docs creation
	•	authored + generated docs merge behavior
	•	final page rendering
	•	build manifest output
	•	failure behavior when submodule is missing or invalid
	•	deterministic outputs

Overall repository coverage must remain ≥ 90%, and this docs pipeline must have strong direct test coverage.

⸻

Deliverables for the Docs Pipeline

Implement:
	•	a deterministic docs build pipeline
	•	framework version extraction
	•	framework metadata extraction
	•	generated reference docs
	•	rendered site page generation
	•	build manifest output
	•	machine-readable reference outputs
	•	automated tests

The docs pipeline should make the website feel like a live, version-aware explanation layer on top of the actual framework.

----

Versioned Documentation

Docs should support versions such as:

v1.0
v1.1
v2.0

Older documentation should remain accessible.

⸻

Machine-Readable Documentation

In addition to narrative documentation, generate structured docs for LLMs.

Examples:

JSON schema of graph nodes.

CLI command metadata.

Pipeline stage definitions.

Extension APIs.

These files can be used as prompt context for LLM tools.

⸻

Continuous Documentation Updates

Whenever a framework release occurs:

Documentation generation must run automatically.

The website should update documentation to match the new version.

⸻

Testing Requirements

Automated tests must verify:

Documentation generation.

CLI command extraction.

Versioned documentation builds.

Docs remain synchronized with framework metadata.

Overall test coverage must remain ≥ 90%.

⸻

Deliverables

Phase 4 must produce:

A redesigned homepage.

A rewritten architecture explanation page.

A full documentation site.

Automatic documentation generation.

Versioned documentation.

Machine-readable documentation exports.
