# Execution Spec: 034-deterministic-pack-system-local-remote-marketplace-integration

## Historical Import Note

This spec was imported from the explicitly marked pre-canonical archive.

- Legacy name: `34 — Deterministic Pack System + Local/Remote Marketplace Integration`
- Legacy id: `34`
- Canonical pre-canonical id: `034`
- Imported module: `PreCanonical`
- Source archive: `_import/pre-canonical-specs.md`

## Original Pre-Canonical Spec

🎯 Goals
	1.	Introduce pack install/remove lifecycle
	2.	Ensure deterministic integration into the application graph
	3.	Enable optional remote registry (read-only)
	4.	Integrate packs into ExplainModel (Spec 33)
	5.	Prepare for future monetization without gating

⸻

🚫 Non-Goals
	•	No authentication
	•	No billing
	•	No background services
	•	No implicit updates

⸻

🧱 Part 1 — Pack Discovery & Manifest

Requirement

Every pack must include:

/foundry.json


⸻

Schema (strict)

{
  "name": "vendor/pack",
  "version": "1.0.0",
  "description": "string",
  "entry": "Vendor\\Pack\\PackServiceProvider",
  "capabilities": [],
  "checksum": "string",
  "signature": "string|null"
}


⸻

Validation Rules
	•	name must match: ^[a-z0-9-_]+/[a-z0-9-_]+$
	•	version must be semver
	•	entry must be a valid PHP class
	•	checksum must match package contents

Failure → abort install with structured error

⸻

🧱 Part 2 — Local Pack Storage

Directory Layout

/foundry/packs/{vendor}/{pack}/{version}/


⸻

Rules
	•	packs are immutable once installed
	•	installing a new version does NOT overwrite previous versions
	•	only one version may be active at a time

⸻

Active Pack Registry

Create:

/foundry/packs/installed.json


⸻

Example

{
  "vendor/pack": "1.2.0"
}


⸻

🧱 Part 3 — CLI Commands (Exact)

Required Commands

foundry pack install vendor/pack
foundry pack install vendor/pack@1.2.0
foundry pack remove vendor/pack
foundry pack list
foundry pack info vendor/pack


⸻

Behavior

install
	1.	resolve version (local or registry)
	2.	download or load package
	3.	validate manifest
	4.	verify checksum
	5.	extract to correct directory
	6.	update installed.json atomically

⸻

remove
	•	remove active version reference
	•	do NOT delete files (safe uninstall)

⸻

list
	•	show installed packs
	•	include:
	•	version
	•	source (local/remote)

⸻

info
	•	display manifest
	•	display explain summary (Spec 33)

⸻

🧱 Part 4 — Registry Integration (Optional)

Registry Endpoint

GET /packs

Returns:

[
  {
    "name": "vendor/pack",
    "version": "1.2.0",
    "description": "...",
    "download_url": "...",
    "checksum": "...",
    "signature": "...",
    "verified": true
  }
]


⸻

CLI Behavior

foundry pack search blog

	•	fetch registry
	•	filter locally
	•	no server-side filtering required

⸻

Requirements
	•	registry must be optional
	•	CLI must work offline

⸻

🧱 Part 5 — Pack Loading (Critical)

Boot Process Integration

During application boot:
	1.	read installed.json
	2.	resolve active versions
	3.	load each pack’s entry class
	4.	call:

$provider->register($context);


⸻

Requirements
	•	deterministic load order:
	•	sort by vendor/pack
	•	no dynamic loading
	•	no runtime mutation

⸻

🧱 Part 6 — Graph Integration

Pack contributions must go through:

PackContext


⸻

Allowed Contributions
	•	commands
	•	schemas
	•	workflows
	•	generators
	•	events
	•	guards

⸻

Rule

Packs may not mutate existing nodes directly.

⸻

🧱 Part 7 — Conflict Detection

Types
	•	command name collision
	•	schema collision
	•	graph node duplication

⸻

Behavior
	•	fail install OR fail boot
	•	return structured error:

{
  "error": "conflict",
  "type": "command_collision",
  "conflicting_pack": "vendor/pack"
}


⸻

🧱 Part 8 — Explain Integration (Spec 33)

Requirement

All pack contributions must appear in:

"extensions": [...]


⸻

Explain Command

foundry explain pack vendor/pack

Must return:
	•	commands added
	•	schemas introduced
	•	graph impact
	•	dependencies

⸻

Rule

No pack behavior may exist that is not explainable.

⸻

🧱 Part 9 — Determinism

All operations must be deterministic:
	•	install → identical structure
	•	load → identical graph
	•	explain → identical output

⸻

🧱 Part 10 — Version Resolution

Rules
	•	exact version if specified
	•	otherwise:
	•	latest from registry OR local
	•	no automatic upgrades

⸻

🧱 Part 11 — Error Handling

All errors must be structured:
	•	install failure
	•	manifest invalid
	•	checksum mismatch
	•	conflict

No plain-text CLI-only errors

⸻

🧱 Part 12 — Testing Requirements
	•	install/remove cycle
	•	manifest validation
	•	checksum verification
	•	registry fetch
	•	offline install
	•	explain integration
	•	conflict detection
	•	deterministic ordering

⸻

✅ Acceptance Criteria
	•	packs install deterministically
	•	packs register via PackServiceProvider
	•	graph includes pack contributions
	•	explain reflects pack contributions
	•	CLI fully functional offline
	•	no feature gating exists

⸻

🧠 Done Means

Foundry now supports:

a deterministic, inspectable, extension ecosystem
