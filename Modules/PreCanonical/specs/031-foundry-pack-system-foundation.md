# Execution Spec: 031-foundry-pack-system-foundation

## Historical Import Note

This spec was imported from the explicitly marked pre-canonical archive.

- Legacy name: `31 — Foundry Pack System Foundation`
- Legacy id: `31`
- Canonical pre-canonical id: `031`
- Imported module: `PreCanonical`
- Source archive: `_import/pre-canonical-specs.md`

## Original Pre-Canonical Spec

Core Principle

A pack is a versioned unit of functionality that extends Foundry through explicit, deterministic registration.

Packs are:
	•	installable
	•	explainable
	•	graph-visible
	•	deterministic

Packs are not:
	•	hidden runtime plugins
	•	implicit filesystem magic
	•	mutable global state

⸻

Goals
	1.	Define the local pack manifest and directory structure
	2.	Support deterministic install / remove / list flows
	3.	Register installed packs into the application graph
	4.	Preserve full offline usability
	5.	Prepare cleanly for future hosted registry work

⸻

Non-Goals
	•	no remote marketplace fetch yet
	•	no auth or monetization yet
	•	no MCP integration yet
	•	no premium pack handling yet

⸻

Part 1 — Pack Identity and Manifest

Every pack must include a manifest file:

foundry.json

Required fields:

{
  "name": "vendor/pack-name",
  "version": "1.0.0",
  "description": "string",
  "entry": "Vendor\\Pack\\PackServiceProvider",
  "capabilities": []
}

Validation Rules
	•	name must match vendor/pack-name format
	•	version must be semver
	•	entry must be a valid PHP class name
	•	description must be non-empty
	•	capabilities must be an array of strings

Install must fail with structured error if manifest is invalid.

⸻

Part 2 — Local Storage Layout

Installed pack files must live under:

.foundry/packs/{vendor}/{pack}/{version}/

Example:

.foundry/packs/foundry/blog/1.0.0/

Packs are immutable once installed.

Installing a new version must not overwrite an older version.

⸻

Part 3 — Active Pack Registry

Create and maintain:

.foundry/packs/installed.json

Example:

{
  "foundry/blog": {
    "active_version": "1.0.0",
    "installed_versions": ["1.0.0"]
  }
}

Rules:
	•	only one active version per pack
	•	multiple installed versions may exist
	•	graph boot uses the active version only

⸻

Part 4 — CLI Surface

Required commands:

foundry pack install <path-or-name>
foundry pack remove vendor/pack
foundry pack list
foundry pack info vendor/pack

install

For this spec, install may support:
	•	local filesystem path
	•	local package source already available on disk

It does not need remote registry support yet.

Behavior:
	1.	resolve source
	2.	read and validate manifest
	3.	copy pack into .foundry/packs/...
	4.	update installed.json
	5.	make installed version active
	6.	trigger graph refresh / rebuild if required

remove

Behavior:
	•	deactivate pack in installed.json
	•	do not physically delete installed files in V1
	•	pack becomes inactive and no longer contributes to graph

list

Show:
	•	pack name
	•	active version
	•	installed versions

info

Show:
	•	manifest fields
	•	local install path
	•	capabilities
	•	activation status

⸻

Part 5 — Registration Contract

At boot / graph build time, Foundry must:
	1.	read installed.json
	2.	resolve active packs
	3.	load each pack’s entry class
	4.	call its registration hook through a pack context

For this spec, require a PackServiceProvider-style entrypoint, even if the full interface is refined later.

⸻

Part 6 — Graph Integration

Pack registration must contribute to the canonical graph through explicit registration only.

Allowed contributions may include:
	•	commands
	•	schemas
	•	workflows
	•	events
	•	guards
	•	generators
	•	docs metadata

Rules:
	•	no silent mutation of existing core nodes
	•	no implicit overrides
	•	all contributions must be attributable to the pack

⸻

Part 7 — Deterministic Load Order

Pack loading must be deterministic.

Sort active packs by:
	1.	pack name
	2.	active version

No filesystem-order loading is allowed.

This ordering must be used consistently for:
	•	registration
	•	graph integration
	•	explain surfaces later

⸻

Part 8 — Conflict Handling

At minimum, detect and fail on:
	•	command name collisions
	•	schema name collisions
	•	duplicate graph node identifiers

Behavior:
	•	installation may succeed
	•	activation / graph build must fail with structured error if conflicts exist

Do not silently override.

⸻

Part 9 — Offline Requirement

Everything in this spec must work with no remote service:
	•	install from local source
	•	remove
	•	list
	•	info
	•	graph integration

Remote registry support comes later.

⸻

Part 10 — Error Handling

All failures must return structured errors, including:
	•	invalid manifest
	•	invalid entry class
	•	missing pack source
	•	activation conflict
	•	duplicate contribution
	•	corrupt installed.json

No opaque plain-text-only failure paths.

⸻

Part 11 — Testing

Add tests for:
	•	manifest validation
	•	local install
	•	remove/deactivate
	•	installed.json updates
	•	deterministic load order
	•	graph registration
	•	conflict detection
	•	offline behavior

⸻

Acceptance Criteria
	•	packs can be installed locally
	•	packs can be deactivated/removed from active use
	•	installed packs are tracked in .foundry/packs/installed.json
	•	active packs register into the application graph
	•	graph integration is deterministic
	•	no remote dependency exists
	•	conflicts fail explicitly

⸻

Done Means

Foundry has a real local pack system that:
	•	works offline
	•	is deterministic
	•	integrates into the graph
	•	is ready to support a later marketplace layer
