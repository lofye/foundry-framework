# Spec 33 — Explain System Hardening + Extension-Aware Architecture

Canonical Model + Deterministic Contract + Marketplace Integration Surface

⸻

🧭 Purpose (Updated)

Transform the explain system into:

the canonical, deterministic, extension-aware architecture truth layer

that safely powers:
	•	CLI introspection
	•	documentation generation
	•	graph inspection
	•	generate (Spec 35)
	•	pack / extension introspection (NEW)
	•	marketplace trust + metadata surface (NEW)

This remains a hardening + normalization pass, with ecosystem integration added.

⸻

🧠 Core Principle (Expanded)

Explain must become:

the single source of truth for understanding the system including installed extensions

Not:

a core-only introspection tool

⸻

🎯 Goals (Updated)
	1.	Normalize explain into a canonical data model
	2.	Separate data truth from presentation
	3.	Ensure deterministic output
	4.	Formalize contracts for LLM and tooling consumption
	5.	Preserve existing functionality
	6.	Make explain extension-aware (NEW)
	7.	Enable explain to describe pack contributions (NEW)

⸻

🚫 Non-Goals (Unchanged)
	•	Do not rewrite explain from scratch
	•	Do not remove useful output
	•	Do not introduce AI behavior
	•	Do not break existing CLI usage unnecessarily

⸻

🧱 Part 1 — Canonical Explain Model (Extended)

Introduce:

ExplainModel

Must include all existing domains PLUS:

NEW DOMAIN
	•	extensions

⸻

Updated Required Domains
	•	subject
	•	graph
	•	execution
	•	guards
	•	events
	•	schemas
	•	relationships
	•	diagnostics
	•	docs
	•	impact
	•	commands
	•	metadata
	•	extensions (NEW)

⸻

🧩 Extensions Domain (NEW)

Each ExplainModel must include:

{
  "extensions": [
    {
      "name": "vendor/pack",
      "version": "1.2.0",
      "type": "pack",
      "provides": ["commands", "schemas", "workflows"],
      "affects": ["feature.blog"],
      "entry_points": [...],
      "nodes": [...],
      "verified": true,
      "source": "local|marketplace"
    }
  ]
}

⸻

🧠 Key Rule

The system must be explainable with extensions applied, not just core.

⸻

🧱 Part 2 — Subject Contract (Extended)

Add:
	•	origin → core | extension
	•	extension → vendor/pack (if applicable)

⸻

Example

{
  "kind": "command",
  "id": "blog.publish",
  "origin": "extension",
  "extension": "vendor/blog-pack"
}

⸻

🧱 Part 3 — Domain Normalization (Updated)

Add:
	•	extensions (NEW)

Each domain must also:

be aware of which extension contributed each element

⸻

Example

{
  "commands": [
    {
      "name": "blog.publish",
      "source": {
        "type": "extension",
        "name": "vendor/blog-pack"
      }
    }
  ]
}

⸻

🧱 Part 4 — Model vs Plan Separation (Unchanged)

No changes here — this was already correct.

But:

ExplanationPlan must now be capable of rendering extension context.

⸻

🧱 Part 5 — JSON Contract (Extended)

Add:

"extensions": [...]

⸻

New Requirements
	•	Every explain output must include extension metadata
	•	No implicit extension contributions

⸻

🧱 Part 6 — Collector Discipline (Extended)

Collectors must now:
	•	declare extension origin when applicable
	•	not merge extension + core data implicitly

⸻

New Rule

No data may exist without traceable origin (core or extension)

⸻

🧱 Part 7 — Deterministic Ordering (Extended)

Ordering must include:
	1.	core elements
	2.	extension elements sorted by:
	•	extension name
	•	then internal ordering rules

⸻

🧱 Part 8 — Target Resolution Contract (Extended)

When ambiguity occurs:

Include extension context:

{
  "candidates": [
    {
      "id": "blog.publish",
      "origin": "extension",
      "extension": "vendor/blog-pack"
    }
  ]
}

⸻

🧱 Part 9 — Integration Surface (Expanded)

ExplainModel must now support:
	•	generate (Spec 35)
	•	docs builders
	•	graph tools
	•	CLI tools
	•	pack system (Spec 31)
	•	marketplace trust system (Spec 34)

⸻

Critical New Capability

Explain must answer:

“What does this pack actually do to my system?”

⸻

🧱 Part 10 — Testing (Extended)

Add tests for:
	•	extension attribution correctness
	•	extension isolation
	•	mixed core + extension explain output
	•	deterministic ordering across extensions

⸻

🧱 Part 11 — Backward Compatibility (Unchanged)

No changes required.

⸻

🔥 NEW PART 12 — Explain as Marketplace Trust Layer

This is the big unlock.

Explain becomes the foundation for:

1. Pack Transparency

Users can run:

foundry explain pack vendor/blog-pack

And see:
	•	commands added
	•	schemas introduced
	•	workflows affected
	•	graph changes
	•	risk/impact

⸻

2. Safety & Trust

Explain enables:
	•	“what will this pack do?”
	•	“what will this change affect?”
	•	“what systems does this touch?”

⸻

3. Marketplace Integration

Future:
	•	marketplace UI uses ExplainModel
	•	pack pages show:
	•	capabilities
	•	impact graph
	•	extension footprint

⸻

🧠 Acceptance Criteria (Updated)
	•	ExplainModel includes extensions domain
	•	All data includes origin attribution
	•	Explain works identically with and without extensions
	•	Output is deterministic across extension combinations
	•	Explain can fully describe any installed pack
	•	ExplainModel is consumable by:
	•	generate
	•	packs
	•	marketplace

⸻

🧠 Done Means (Updated)

Explain becomes:
	•	the canonical system introspection layer
	•	a stable contract for humans and machines
	•	the foundation for generate
	•	the trust and transparency layer for the marketplace

------------------------------------------------------------------------------------------

RESULT

Explain is now extension-aware and pack-aware without breaking the existing CLI shape. I added a canonical model and attribution layer in [ExplainModel.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainModel.php), [ExplainOrigin.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainOrigin.php), [ExplanationPlan.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplanationPlan.php), and [ExplanationPlanAssembler.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplanationPlanAssembler.php). `foundry explain` now emits top-level `graph`, `execution`, `guards`, `events`, `schemas`, `docs`, `impact`, `commands`, and `extensions` domains, while keeping the legacy `executionFlow`/`relationships`/`relatedDocs` contract for compatibility. Subjects now carry `origin` and `extension`, and renderers surface that context in [TextExplanationRenderer.php](/Users/lo_fye/code/foundry-framework/src/Explain/Renderers/TextExplanationRenderer.php) and [MarkdownExplanationRenderer.php](/Users/lo_fye/code/foundry-framework/src/Explain/Renderers/MarkdownExplanationRenderer.php).

The resolver and analyzer stack now understand installed packs directly. [ExplainTarget.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainTarget.php), [ExplainTargetResolver.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainTargetResolver.php), [ExplainSubjectFactory.php](/Users/lo_fye/code/foundry-framework/src/Explain/ExplainSubjectFactory.php), [ExtensionContextCollector.php](/Users/lo_fye/code/foundry-framework/src/Explain/Collectors/ExtensionContextCollector.php), and [PackSubjectAnalyzer.php](/Users/lo_fye/code/foundry-framework/src/Explain/Analyzers/PackSubjectAnalyzer.php) now support `foundry explain pack:<vendor/pack>` and attribute core-vs-extension contributions deterministically. I also persisted pack install source metadata through [InstalledPackRegistry.php](/Users/lo_fye/code/foundry-framework/src/Packs/InstalledPackRegistry.php), [LocalPackLoader.php](/Users/lo_fye/code/foundry-framework/src/Packs/LocalPackLoader.php), [InstalledPackExtension.php](/Users/lo_fye/code/foundry-framework/src/Packs/InstalledPackExtension.php), and [PackManager.php](/Users/lo_fye/code/foundry-framework/src/Packs/PackManager.php), so the extensions domain can distinguish local vs marketplace installs. Public help/docs were updated in [ApiSurfaceRegistry.php](/Users/lo_fye/code/foundry-framework/src/Support/ApiSurfaceRegistry.php), [README.md](/Users/lo_fye/code/foundry-framework/README.md), and [architecture-tools.md](/Users/lo_fye/code/foundry-framework/docs/architecture-tools.md).

Verification passed:
- `php bin/foundry compile graph --json`
- `php bin/foundry verify graph --json`
- `php bin/foundry verify cli-surface --json` -> `128` valid, `0` invalid, `0` ambiguous, coverage `1`
- `php vendor/bin/phpunit` -> `386` tests passed, `3081` assertions

29 files changed
+1205
-56
Undo
README.md
docs/architecture-tools.md
src/Explain/Analyzers/PackSubjectAnalyzer.php
src/Explain/Analyzers/RelatedCommandsAnalyzer.php
src/Explain/Collectors/ExtensionContextCollector.php
src/Explain/Collectors/WorkflowContextCollector.php
src/Explain/ExplainArtifactCatalog.php
src/Explain/ExplainEngineFactory.php
src/Explain/ExplainModel.php
src/Explain/ExplainOrigin.php
src/Explain/ExplainSubject.php
src/Explain/ExplainSubjectFactory.php
src/Explain/ExplainSupport.php
src/Explain/ExplainTarget.php
src/Explain/ExplainTargetResolver.php
src/Explain/ExplanationPlan.php
src/Explain/ExplanationPlanAssembler.php
src/Explain/Renderers/MarkdownExplanationRenderer.php
src/Explain/Renderers/TextExplanationRenderer.php
src/Explain/SummarySectionBuilder.php
src/Packs/InstalledPackExtension.php
src/Packs/InstalledPackRegistry.php
src/Packs/LocalPackLoader.php
src/Packs/PackManager.php
src/Support/ApiSurfaceRegistry.php
tests/Integration/CLIPackCommandsTest.php
tests/Unit/ExplainArchitectureCoverageTest.php
tests/Unit/ExplainEngineTest.php
tests/Unit/ExplainPolishTest.php

------------------------------------------------------------------------------------------

------------------------------------------------------------------------------------------

------------------------------------------------------------------------------------------
