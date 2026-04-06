Yes. Here is a revised APP-AGENTS.md that keeps your current app-specific guidance, adds the context anchoring system, and aligns the tone/rules with the framework-level AGENTS.md without making app repos feel too framework-internal.

I also fixed a few things implicitly:
•	preserved app-specific source-of-truth rules
•	kept the current command loop style
•	added explicit refusal-to-proceed behavior
•	added spec/state/alignment rules in app-facing language
•	avoided claiming behavior that belongs only to the framework repo

⸻


# Foundry App Agent Guide

Use this file when working inside a Foundry application repository.

## Command Rule

- In Foundry app repos, prefer `foundry ...`
- If your shell does not resolve current-directory executables, use `./foundry ...`
- Prefer `--json` for inspect, verify, doctor, prompt, export, and generation commands when an agent is consuming the output

## Source Of Truth

- Treat `app/features/*` as source-of-truth application behavior
- Treat `app/definitions/*` as source-of-truth definitions when that folder exists
- Treat `app/.foundry/build/*` as canonical compiled output
- Treat `.foundry/packs/installed.json` as explicit local pack activation state when packs are in use
- Treat `.foundry/cache/registry.json` as cached hosted-registry metadata when remote pack discovery is used
- Treat `.foundry/packs/*/*/*/foundry.json` as installed pack metadata, not editable app source
- Treat `app/generated/*` as generated compatibility projections
- Treat `docs/generated/*` and `docs/inspect-ui/*` as generated documentation output
- Treat feature context documents under `docs/features/*` as the source of truth for feature intent, state, and reasoning context
- Treat code and tests as the source of truth for actual implementation and runtime behavior
- Do not hand-edit `app/generated/*`; regenerate instead
- Do not hand-edit installed pack files under `.foundry/packs/*`; reinstall or replace them from source instead

## Safe Edit Loop

1. Read the relevant feature spec, feature context document, and decision ledger before editing.
2. Inspect current feature and graph reality before changing code.
3. Edit the smallest source-of-truth files that satisfy the task.
4. Compile graph and inspect diagnostics.
5. Inspect impact, pipeline, and route surfaces when the change touches auth, routes, docs, or execution order.
6. Verify graph, context, and contract surfaces.
7. Refresh generated docs if source-of-truth changed.
8. Run PHPUnit.

## Guard Rails

- When a bug is encountered, create a test that fails because of that bug, then modify the non-test code so that the test passes while maintaining the intent of the original code.
- Never take a shortcut (such as forcing a test falsely return true) to get a test to pass.
- Keep test coverage above 90% for all new features and existing code.

## Recommended Command Loop

In a scaffolded app repo, bare `foundry` runs the first-run orientation for the current project. `foundry explain --json` without a target explains the first feature or route deterministically.
Explain, explain diff, and generate JSON payloads include deterministic confidence scores, bands, and evidence factors; prefer them when an agent is deciding whether to proceed or ask for clarification.
When Git is available, `foundry explain <target> --git --json` adds repository context for the explained target, and `foundry generate` returns Git safety metadata for the run.

```bash
foundry
foundry explain --json
foundry explain <target> --git --json
foundry explain --diff --json
foundry inspect graph --json
foundry inspect pipeline --json
foundry pack search <query> --json
foundry inspect feature <feature> --json
foundry inspect context <feature> --json
foundry inspect packs --json
foundry compile graph --json
foundry inspect impact --file=app/features/<feature>/feature.yaml --json
foundry doctor --feature=<feature> --json
foundry context doctor --feature=<feature> --json
foundry context check-alignment --feature=<feature> --json
foundry verify context --feature=<feature> --json
foundry history --kind=generate --json
foundry generate docs --format=markdown --json
foundry generate inspect-ui --json
foundry verify graph --json
foundry verify pipeline --json
foundry verify contracts --json
php vendor/bin/phpunit -c phpunit.xml.dist
```

App Rules
	•	Keep changes feature-local unless the task is explicitly cross-cutting platform work
	•	Update feature tests and calling code together when contracts or schemas change
	•	Preserve explicit manifests, schemas, spec files, state files, and decision ledgers; avoid hidden behavior
	•	Use feature-local prompts.md and context.manifest.json when present to understand the feature before editing
	•	Do not silently diverge from a feature spec; if implementation must diverge, record that divergence explicitly in the feature context document and decision ledger

Context Anchoring & Spec-State Alignment

Purpose

Ensure that feature work remains persistent, explainable, and resumable across sessions, tools, and models.

For each feature, maintain three documents:
	•	Feature Spec = intended behavior
	•	Feature Context Document = current implementation state
	•	Decision Ledger = append-only reasoning history

Core Principles
	1.	Chat history is ephemeral and must not be treated as authoritative
	2.	Feature documents are the source of truth for intent, scope, progress, and reasoning context
	3.	Code and tests are the source of truth for implementation and runtime behavior
	4.	Every meaningful technical or architectural decision must be recorded
	5.	Decision history is append-only and must never be compacted, rewritten, or pruned
	6.	Any agent must be able to resume work using only repository artifacts

Required File Structure

For each feature <feature-name>, the following files are canonical:
	•	docs/features/<feature-name>.spec.md
	•	docs/features/<feature-name>.md
	•	docs/features/<feature-name>.decisions.md

Document Hierarchy

For each feature:
	1.	Spec = intent (what should exist)
	2.	Feature Doc = state (what exists right now)
	3.	Decision Ledger = history (why it is this way)

Feature Naming Rules

Feature names must:
	•	be lowercase
	•	use kebab-case
	•	be stable over time
	•	match the filename exactly

1. Feature Spec (INTENT)

Path:
docs/features/<feature-name>.spec.md

Purpose:
	•	What this feature is intended to do
	•	Why it exists

Required structure:

# Feature Spec: <name>

## Purpose
- What this feature is intended to do
- Why it exists

## Goals
- Specific outcomes the feature must achieve

## Non-Goals
- Explicitly what this feature will NOT do

## Constraints
- Technical, business, or architectural limits

## Expected Behavior
- How the system should behave from a user/system perspective

## Acceptance Criteria
- Clear conditions that define completion

## Assumptions
- Any assumptions made during spec creation

Rules:
	•	A feature spec MUST exist before meaningful implementation continues
	•	A spec MAY be created by a human or AI
	•	A spec MUST NOT be silently modified
	•	Any spec change MUST be explicitly stated and logged in the decision ledger

Rule - Spec Requirement

Before meaningful implementation:
	•	A feature spec MUST exist

If no spec exists, you MUST:
	1.	Ask for one OR
	2.	Generate a proposed spec

Implementation MUST NOT proceed until a spec exists and is accepted.

Rule - Spec Precedence

If a spec exists:
	•	It overrides assumptions
	•	Implementation MUST align with it
	•	Any deviation MUST be logged as a decision

2. Feature Context Document (STATE)

Path:
docs/features/<feature-name>.md

Purpose:
	•	What this feature currently does
	•	Why it exists in its current form

Required structure:

# Feature: <name>

## Purpose
- What this feature currently does
- Why it exists in its current form

## Current State
- What is implemented
- What remains
- What is in progress

## Open Questions
- Unresolved decisions

## Next Steps
- Immediate actionable work

Rules:
	•	Must reflect the latest known state
	•	Must be updated after every meaningful change
	•	Must not contain historical decision logs
	•	Must not delete intent-critical information

3. Decision Ledger (APPEND-ONLY HISTORY)

Path:
docs/features/<feature-name>.decisions.md

Rules:
	•	Must be append-only
	•	Must never be edited, compacted, rewritten, or pruned
	•	Must preserve full reasoning and context
	•	Must include all meaningful technical and architectural decisions

Required entry format:

### Decision: <short title>
Timestamp: <ISO-8601>

**Context**
- What problem is being solved

**Decision**
- What was chosen

**Reasoning**
- Why this was chosen

**Alternatives Considered**
- Other options and why they were rejected

**Impact**
- Consequences of this decision

**Spec Reference**
- Relevant spec section(s), if applicable

Operational Rules

Rule 1 - Read Before Acting

Before performing meaningful feature work:
	1.	Read the feature spec
	2.	Read the feature context document
	3.	Read the feature decision ledger
	4.	Do not rely on chat history as primary context

Rule 1A - Alignment Check Before Implementation

Before writing or modifying code, you MUST verify that:
	•	the feature spec
	•	the feature context document
	•	the decision ledger

are consistent and aligned.

If misalignment exists, resolve it before proceeding.

Rule 2 - Mandatory Decision Logging

After meaningful technical or architectural decisions, append a new decision entry to the decision ledger.

This includes decisions such as:
	•	architecture choices
	•	data model changes
	•	API design choices
	•	framework or library selection
	•	tradeoff resolution
	•	constraint discovery

Rule 3 - Mandatory State Sync

After meaningful implementation or planning work, update:
	•	Current State
	•	Open Questions
	•	Next Steps

Rule 4 - Timing Enforcement

Updates must occur:
	•	immediately after a decision
	•	at the end of each meaningful step
	•	before ending a session
	•	before switching tasks
	•	before handing off to another agent

Rule 5 - No Compaction or Summarization of History

The decision ledger:
	•	must not be summarized
	•	must not be rewritten
	•	must not be pruned
	•	must not be compressed

Full historical fidelity is required.

Rule 6 - Missing Document Handling

If required feature context files do not exist, create them before continuing meaningful implementation.

When creating them:
	•	clearly label assumptions
	•	create all required files
	•	begin the decision ledger immediately

Rule 7 - Cross-Session Continuity

When starting work:
	1.	Ignore missing chat history
	2.	Use repository artifacts as sole authoritative context
	3.	Resume from Next Steps unless the spec or code requires a different order

Rule 8 - Multi-Agent Compatibility

All updates must:
	•	be human-readable
	•	avoid model-specific language
	•	avoid references to specific AI tools
	•	be understandable by any future agent

Rule 9 - Spec Alignment

If a formal spec exists:
	•	decisions must reference relevant spec sections where applicable
	•	feature state must reflect spec progress
	•	divergences must be logged as decisions
	•	if implementation diverges from spec without a logged decision, this is NON-COMPLIANT

Rule 10 - Spec vs State Mismatch Detection

You must actively check for mismatches between:
	•	feature spec (intent)
	•	feature document (state)
	•	implementation (code/tests)

A mismatch exists if:
	•	the feature document describes behavior not present in the spec
	•	the implementation contradicts the spec
	•	the feature document reflects behavior not explained by a decision
	•	the spec describes requirements that are absent from state tracking without explanation

If a mismatch is detected, it must be resolved immediately by one of:
	1.	Updating the implementation to match the spec
	2.	Updating the spec and logging the change in the decision ledger
	3.	Logging a justified divergence in the decision ledger and reflecting it in the feature document

Unresolved mismatches are NON-COMPLIANT.

Failure Conditions

The system is NON-COMPLIANT if:
	•	the feature spec is missing or ignored
	•	the feature context document is missing
	•	the feature decision ledger is missing
	•	a decision is made but not logged
	•	the ledger is modified or compacted
	•	the feature document is outdated
	•	work proceeds without reading the required documents
	•	chat history is used as primary context
	•	spec/state mismatch exists without resolution

Enforcement - Refuse to Proceed

If the system is NON-COMPLIANT, you MUST:
	•	stop work immediately
	•	explain why the system is non-compliant
	•	list the required corrective actions
	•	do not continue implementation until compliance is restored

Allowed exception:
	•	you may create or repair the required context artifacts as the immediate next step

Ask First

Stop and ask before:
	•	hand-editing generated files
	•	changing app-wide conventions, package dependencies, or generated scaffold structure without approval
	•	making a behavior choice when the requested behavior is ambiguous or conflicts with the existing feature contract
