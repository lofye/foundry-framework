# Execution Spec: 035-generate-system-end-to-end-explain-driven-pack-aware

## Historical Import Note

This spec was imported from the explicitly marked pre-canonical archive.

- Legacy name: `35 — Generate System (End-to-End, Explain-Driven, Pack-Aware)`
- Legacy id: `35`
- Canonical pre-canonical id: `035`
- Imported module: `PreCanonical`
- Source archive: `_import/pre-canonical-specs.md`

## Original Pre-Canonical Spec

🎯 Goals
	1.	Make code generation architecture-aware
	2.	Prevent invalid or unsafe modifications
	3.	Support new / modify / repair workflows
	4.	Integrate fully into Foundry CLI + pipeline
	5.	Produce deterministic, reviewable outputs
	6.	Leverage pack-provided generators (NEW)
	7.	Enable automatic pack installation when required (NEW)

⸻

🚫 Non-Goals
	•	Do not build a black-box AI agent
	•	Do not bypass graph/compiler/doctor systems
	•	Do not allow uncontrolled file writes
	•	Do not depend on a specific LLM provider

⸻

🧱 Part 1 — CLI Interface (Extended)

Command

foundry generate "<intent>"


⸻

Options
	•	--mode=new|modify|repair (required)
	•	--target=... (required for modify/repair)
	•	--dry-run
	•	--json
	•	--no-verify
	•	--allow-risky
	•	--allow-pack-install (NEW)
	•	--packs=vendor/pack,... (optional hint)

⸻

New Behavior

If generation requires missing capabilities:
	•	system may suggest required packs
	•	optionally install them if --allow-pack-install is present

⸻

🧱 Part 2 — High-Level Pipeline (Updated)
	1.	Parse intent
	2.	Resolve targets
	3.	Build ExplainModel (Spec 33)
	4.	Build GenerationContextPacket
	5.	Resolve required packs (NEW)
	6.	Create GenerationPlan (pack-aware)
	7.	Validate plan
	8.	Execute plan
	9.	Run verification loop
	10.	Return structured output

⸻

🧱 Part 3 — Core Components (Extended)

⸻

3.1 GenerateCommand

Unchanged responsibilities +:
	•	handle pack install prompts
	•	expose pack-related output

⸻

3.2 GenerateEngine

Responsibilities (extended)
	•	orchestrate pipeline
	•	resolve pack requirements
	•	coordinate generator selection

⸻

3.3 GenerationContextPacket (Updated)

Must now include:
	•	targets
	•	graph relationships
	•	constraints
	•	docs/examples
	•	validation steps
	•	available generators (core + packs)
	•	installed packs
	•	missing capabilities (NEW)

⸻

3.4 GeneratorRegistry (NEW)

Central registry of generators.

Sources:
	•	core system
	•	installed packs

⸻

Generator Interface

interface Generator
{
    public function supports(ExplainModel $model, Intent $intent): bool;

    public function plan(
        ExplainModel $model,
        Intent $intent
    ): GenerationPlan;
}


⸻

3.5 GenerationPlanner (Extended)

Responsibilities:
	•	select appropriate generator(s)
	•	merge plans if multiple generators apply
	•	ensure deterministic ordering

⸻

Selection Rules
	1.	prefer exact matches
	2.	prefer pack generators when domain-specific
	3.	fallback to core generators

⸻

3.6 GenerationPlan (Extended)

Must include:
	•	actions[]
	•	affected_files[]
	•	risks[]
	•	validations[]
	•	origin (core|pack)
	•	generator_id
	•	extension (if applicable)

⸻

Action Types (unchanged + extended meaning)
	•	create_file
	•	update_file
	•	delete_file
	•	register_component
	•	update_graph
	•	update_schema
	•	add_test
	•	update_docs

⸻

3.7 CodeWriter (Unchanged)

Rules:
	•	no blind overwrites
	•	diff-aware updates
	•	deterministic output

⸻

3.8 PlanValidator (Extended)

Additional checks:
	•	pack conflicts
	•	extension-origin validation
	•	generator consistency

⸻

3.9 VerificationRunner (Unchanged)

Runs:
	•	foundry graph:compile
	•	foundry doctor
	•	foundry verify
	•	relevant tests

⸻

🧱 Part 4 — Modes (Unchanged + Clarified)

⸻

4.1 New

May:
	•	install required packs (if allowed)
	•	create full feature sets via pack generators

⸻

4.2 Modify

Must:
	•	respect extension boundaries
	•	not break pack contracts

⸻

4.3 Repair

Must:
	•	use diagnostics + explain
	•	may leverage pack generators for fixes

⸻

🧱 Part 5 — Pack Resolution (NEW)

Purpose

Determine if intent requires capabilities not present in core.

⸻

Flow
	1.	analyze intent
	2.	match against:
	•	existing graph
	•	installed packs
	3.	identify missing capabilities
	4.	map to known packs

⸻

Output

{
  "missing_capabilities": [...],
  "suggested_packs": ["foundry/blog"]
}


⸻

Behavior
	•	if missing:
	•	warn user
	•	suggest install
	•	optionally auto-install

⸻

🧱 Part 6 — Explain ↔ Generate Contract (Strengthened)

Strict rules:
	•	Generate MUST use ExplainModel
	•	Generate MUST respect extension attribution
	•	Each generation step must map to:
	•	explain node
	•	extension (if applicable)

⸻

Traceability Requirement

Every action must include:

{
  "explain_node_id": "...",
  "origin": "core|extension",
  "extension": "vendor/pack|null"
}


⸻

🧱 Part 7 — Safety Model (Extended)

Add:
	•	pack installation requires explicit flag
	•	pack-generated plans must be labeled
	•	mixed-origin plans must be clearly segmented

⸻

🧱 Part 8 — Output Contract (Extended)

Human Output

Must include:
	•	summary
	•	files affected
	•	risks
	•	verification results
	•	pack involvement (NEW)

⸻

JSON Output

Must include:
	•	intent
	•	mode
	•	plan
	•	actions_taken
	•	verification_results
	•	errors
	•	metadata
	•	packs_used
	•	packs_installed

⸻

🧱 Part 9 — Determinism (Strengthened)

Determinism must include:
	•	generator selection
	•	pack resolution
	•	plan merging

⸻

🧱 Part 10 — Testing (Extended)

Add tests for:
	•	pack-aware generation
	•	missing capability detection
	•	generator selection logic
	•	mixed core/pack plans
	•	deterministic output across pack states

⸻

🧱 Part 11 — Failure Modes

Must handle:
	•	missing pack
	•	generator conflict
	•	invalid plan
	•	unsafe operation

All must return structured errors.

⸻

🧱 Part 12 — Future Extensions (Unchanged)
	•	interactive planning (Spec 36)
	•	rollback system
	•	diff previews
	•	workflows
	•	hosted AI

⸻

✅ Acceptance Criteria (Updated)
	•	foundry generate works end-to-end
	•	supports new/modify/repair
	•	uses ExplainModel exclusively
	•	supports pack-based generators
	•	detects missing capabilities
	•	produces deterministic plans
	•	validates before execution
	•	runs verification
	•	outputs structured results
	•	does not corrupt system state

⸻

🧠 Done Means (Updated)

Foundry can now:
	•	understand a system (Explain)
	•	extend a system (Packs)
	•	safely modify a system (Generate)

⸻

🔥 Final Insight (Important)

This upgrade does one critical thing:

It makes Generate the execution layer of your marketplace

Now:
	•	Packs define capabilities
	•	Explain describes them
	•	Generate executes them

⸻

🚀 What this unlocks

You can now: foundry generate "add blog"

And Foundry will:
	1.	detect missing capability
	2.	suggest/install foundry/blog
	3.	use its generator
	4.	produce a safe plan
	5.	execute it deterministically
