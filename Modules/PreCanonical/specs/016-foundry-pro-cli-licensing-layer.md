# Execution Spec: 016-foundry-pro-cli-licensing-layer

## Historical Import Note

This spec was imported from the explicitly marked pre-canonical archive.

- Legacy name: `16 — Foundry Pro (CLI + Licensing Layer)`
- Legacy id: `16`
- Canonical pre-canonical id: `016`
- Imported module: `PreCanonical`
- Source archive: `_import/pre-canonical-specs.md`

## Original Pre-Canonical Spec

Goals

Spec 16 must:
	•	introduce a Pro feature layer without impacting core functionality
	•	provide a minimal, local-first licensing mechanism
	•	cleanly separate Pro features from core Foundry
	•	ensure Pro features degrade gracefully when not enabled

⸻

Requirements

1. Separation of concerns

Pro functionality must be isolated from core framework logic.

Acceptable approaches include:
	•	separate package (e.g., foundry-pro)
	•	or clearly isolated module within the repo

Core Foundry must not depend on Pro.

⸻

2. Licensing model (local-first)

Implement a simple license mechanism:
	•	license key stored locally (e.g., ~/.foundry/license.json)
	•	no required external API calls
	•	no runtime dependency on external services

CLI command:

foundry pro enable <license-key>

Behavior:
	•	valid key → enables Pro features
	•	no/invalid key → Pro features unavailable

⸻

3. Feature gating

Pro-only commands must include:
	•	deep diagnostics
	•	architecture explanation
	•	graph diffing
	•	trace analysis
	•	AI-assisted generation (see Spec 17)

If a Pro command is used without a valid license:
	•	output a clear message
	•	exit with non-zero status
	•	do not crash or degrade core functionality

⸻

4. CLI integration

Introduce a Pro namespace or command group:

foundry pro

Subcommands may include:

foundry doctor --deep
foundry explain <target>
foundry diff
foundry trace <target>
foundry generate "<prompt>"


⸻

5. Graceful degradation

Without Pro:
	•	commands may still appear in help
	•	but clearly marked as Pro
	•	execution blocked with informative message

⸻

6. No telemetry requirement

The Pro system must not require telemetry or usage tracking.

⸻

7. Documentation

Docs must clearly explain:
	•	what Pro is
	•	what is free vs paid
	•	that core framework is fully usable without Pro

⸻

Deliverables
	•	Pro feature layer (isolated from core)
	•	local license system
	•	CLI integration for Pro commands
	•	graceful fallback behavior
	•	documentation updates

⸻

Testing Requirements

Tests must cover:
	•	license validation
	•	Pro feature gating
	•	CLI behavior with/without license
	•	failure messaging
	•	isolation from core functionality

Coverage must remain ≥ 90%.
