# Execution Spec: 029-release-readiness-reconciliation-pass

## Historical Import Note

This spec was imported from the explicitly marked pre-canonical archive.

- Legacy name: `29 - Release Readiness Reconciliation Pass`
- Legacy id: `29`
- Canonical pre-canonical id: `029`
- Imported module: `PreCanonical`
- Source archive: `_import/pre-canonical-specs.md`

## Original Pre-Canonical Spec

Purpose

Prepare the framework for release-candidate / 1.0 readiness by:
	1.	reconciling ARCHITECTURE.md with current framework reality
	2.	performing a full confidence pass on:
	•	docs
	•	CLI discovery
	•	first-run experience
	•	contributor starting points

Do not redesign the framework in this spec.
Do not add major new capabilities in this spec.
This is a truthfulness, usability, and readiness pass.

⸻

Goals
	1.	Ensure ARCHITECTURE.md is accurate, current, and aligned with the real framework
	2.	Ensure documentation does not contradict implementation
	3.	Ensure CLI discovery is strong enough for a new user
	4.	Ensure first-run experience is understandable and non-overwhelming
	5.	Ensure contributors can clearly tell where to start
	6.	Surface any remaining release blockers explicitly

⸻

Non-Goals
	•	Do not add broad new framework features
	•	Do not redesign the docs architecture
	•	Do not redesign the CLI surface
	•	Do not do speculative cleanup unrelated to release readiness
	•	Do not preserve stale architecture claims for historical reasons

⸻

Part 1 — Reconcile ARCHITECTURE.md

Audit the current ARCHITECTURE.md against the real framework code and current docs.

Required checks include at minimum:
	1.	Runtime shape
	2.	Core subsystem list
	3.	Storage claims
	4.	Determinism rules
	5.	Safety rules
	6.	Source-of-truth boundaries
	7.	Current CLI surface language
	8.	Current graph / pipeline / verification story

Examples of stale or suspicious claims must be corrected, including any references that no longer match the codebase.

Important:
	•	do not treat ARCHITECTURE.md as sacred
	•	update it to match reality
	•	remove claims that are no longer true
	•	add concise high-level framing where current reality is stronger than the document

⸻

Required outcome for ARCHITECTURE.md

Either:

A. Keep it, fully updated
or

B. Move it into:

docs/architecture/architecture-overview.md

and replace root ARCHITECTURE.md with a short pointer file

Preferred direction:
	•	canonical human-readable architecture overview should live under docs/architecture/
	•	root-level ARCHITECTURE.md may remain only as a short redirect/pointer if desired

Choose the cleaner structure, but keep repo navigation easy.

⸻

Part 2 — Full Confidence Pass on Docs

Audit the major docs surfaces for truthfulness and first-experience quality.

Required surfaces include at minimum:
	•	README.md
	•	example docs
	•	docs/example-applications.md
	•	philosophy docs
	•	execution model docs
	•	graph docs
	•	architecture overview/reference docs
	•	contributing/contributor docs
	•	any docs that define how a new user starts

Required checks:
	1.	Do commands shown in docs actually exist?
	2.	Do the commands behave as described?
	3.	Are example references current?
	4.	Are docs using current taxonomy and terminology?
	5.	Is the first recommended path through the docs sensible?
	6.	Are there places where docs overwhelm new users too early?
	7.	Do docs clearly distinguish:
	•	canonical framework docs
	•	generated reference docs
	•	website/presentation docs
	•	examples/reference/framework categories

Fix mismatches directly.

⸻

Part 3 — Full Confidence Pass on CLI Discovery

Audit CLI discovery from the perspective of a new user.

Required surfaces:
	•	foundry help
	•	foundry help <command>
	•	command grouping/index output
	•	stable vs experimental/internal labeling
	•	README/docs command examples

Use the actual registered CLI/help system as source of truth.  ￼

Required checks:
	1.	Is the top-level help useful to a first-time user?
	2.	Are command groupings understandable?
	3.	Are the most important commands easy to discover?
	4.	Are there commands that exist but are poorly surfaced?
	5.	Are any commands described in docs but hard to find from help output?
	6.	Is command classification clear enough for users to know what is safe/stable?

Make targeted improvements, but do not redesign the entire CLI.

⸻

Part 4 — Full Confidence Pass on First-Run Experience

Evaluate the experience of a new developer trying to use Foundry for the first time.

Required path to evaluate:
	1.	Land on repo / docs
	2.	Understand what Foundry is
	3.	Install or scaffold
	4.	Run a first command
	5.	Inspect/verify something meaningful
	6.	Open an example and understand what to do next

Required questions:
	•	Is the first-run path obvious?
	•	Is the first-run path too overwhelming?
	•	Is there a clear “start here” sequence?
	•	Does the first example actually match the first-run commands/docs?
	•	Do docs and examples reinforce each other?

Fix obvious friction.

⸻

Part 5 — Contributor Starting Point Pass

Audit contributor entry points.

Required question:
If someone wants to contribute to the framework itself, can they tell:
	•	where architecture is documented
	•	where examples live
	•	where the CLI entry surface lives
	•	where compiler/core logic begins
	•	where to verify changes
	•	what docs are canonical vs generated

If this is unclear, fix it with small, explicit improvements to:
	•	contributor docs
	•	architecture overview
	•	README pointers
	•	docs navigation

⸻

Part 6 — Release Blocker Report

At the end of the pass, produce a short explicit report of any remaining 1.0 blockers.

If no blockers remain, say so directly.

If blockers remain, list only real blockers, not wishlist items.

Suggested artifact:

docs-build/release-readiness-report.md

Optional JSON companion:

docs-build/release-readiness-report.json

Include sections for:
	•	architecture reconciliation complete/incomplete
	•	docs confidence
	•	CLI discovery confidence
	•	first-run confidence
	•	contributor confidence
	•	remaining blockers

⸻

Acceptance Criteria
	•	ARCHITECTURE.md is reconciled with current reality, or cleanly replaced by docs/architecture/architecture-overview.md
	•	no obviously stale architecture claims remain
	•	docs command examples match the real CLI
	•	example/docs taxonomy is consistent
	•	first-run path is clear and non-chaotic
	•	CLI discovery is strong enough for a new user
	•	contributor starting points are clear
	•	a release readiness report is produced
	•	any remaining blockers are explicit

⸻

Implementation Bias

Prefer:
	•	truthfulness over legacy wording
	•	clarity over completeness
	•	small targeted fixes over sweeping redesign
	•	release readiness over speculative cleanup

This spec is about making the framework explain itself honestly and confidently at its current maturity level.

⸻

Done Means

A new user, contributor, or LLM should be able to answer:
	•	What is Foundry?
	•	Where do I start?
	•	How do I inspect and verify it?
	•	Where is the architecture explained?
	•	Which docs are canonical?
	•	Which examples should I look at first?

without stumbling into stale, conflicting, or overwhelming guidance.
