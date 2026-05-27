# Execution Spec: 033-explain-system-hardening-extension-aware-architecture

## Historical Import Note

This spec was imported from the explicitly marked pre-canonical archive.

- Legacy name: `33 — Explain System Hardening + Extension-Aware Architecture`
- Legacy id: `33`
- Canonical pre-canonical id: `033`
- Imported module: `PreCanonical`
- Source archive: `_import/pre-canonical-specs.md`

## Original Pre-Canonical Spec

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
