# Spec 34 — Foundry Marketplace System

Title

Deterministic Pack System + Local/Remote Marketplace Integration

⸻

🧭 Preface

This spec introduces the Foundry Pack System, enabling:
	•	deterministic installation of extensions (“packs”)
	•	integration into the canonical application graph
	•	optional remote discovery via marketplace registry

This is a core architectural layer, not a UI feature.

⸻

🧠 Core Principle

Packs extend the system through deterministic graph contributions.
Installation, resolution, and execution must be fully explainable.

⸻

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

------------------------------------------------------------------------------------------

RESULT

Implemented Spec 34 across the pack pipeline. The core changes are in [PackManifest.php](/Users/lo_fye/code/foundry-framework/src/Packs/PackManifest.php), [PackChecksum.php](/Users/lo_fye/code/foundry-framework/src/Packs/PackChecksum.php), [HostedPackRegistry.php](/Users/lo_fye/code/foundry-framework/src/Packs/HostedPackRegistry.php), [HostedPackRegistryEntry.php](/Users/lo_fye/code/foundry-framework/src/Packs/HostedPackRegistryEntry.php), [PackManager.php](/Users/lo_fye/code/foundry-framework/src/Packs/PackManager.php), [PackArchiveExtractor.php](/Users/lo_fye/code/foundry-framework/src/Packs/PackArchiveExtractor.php), [InstalledPackRegistry.php](/Users/lo_fye/code/foundry-framework/src/Packs/InstalledPackRegistry.php), and [LocalPackLoader.php](/Users/lo_fye/code/foundry-framework/src/Packs/LocalPackLoader.php). Packs now require `checksum` and `signature`, installs verify package checksums, hosted registry entries use the `/packs` contract with `checksum`/`signature`/`verified`, exact hosted versions resolve via `vendor/pack@1.2.0`, archive extraction requires root `foundry.json` plus `src/`, and registry writes are atomic.

The CLI and docs were aligned too. [PackCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/PackCommand.php) now reports local vs remote source and includes explain-backed pack summaries in `pack info`, while [ApiSurfaceRegistry.php](/Users/lo_fye/code/foundry-framework/src/Support/ApiSurfaceRegistry.php), [README.md](/Users/lo_fye/code/foundry-framework/README.md), [extensions-and-migrations.md](/Users/lo_fye/code/foundry-framework/docs/extensions-and-migrations.md), and [extension-author-guide.md](/Users/lo_fye/code/foundry-framework/docs/extension-author-guide.md) now describe the stricter manifest and hosted registry contract. Test coverage was updated in [CLIPackCommandsTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIPackCommandsTest.php), [HostedPackRegistryTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/HostedPackRegistryTest.php), [PackArchiveExtractorTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/PackArchiveExtractorTest.php), [PackManifestTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/PackManifestTest.php), plus the pack fixtures under [tests/Fixtures/Packs](/Users/lo_fye/code/foundry-framework/tests/Fixtures/Packs).

Verification passed:
- `php bin/foundry verify cli-surface --json` -> `128` valid, `0` invalid, `0` ambiguous, coverage `1`
- `php bin/foundry verify graph --json` -> `ok: true`
- `php bin/foundry compile graph --json` -> completed successfully
- `php vendor/bin/phpunit` -> `390` tests passed, `3103` assertions

Assumption I kept intentionally: pack storage stays under `.foundry/packs/...` to preserve the repository’s existing deterministic project-state convention, while aligning the manifest, hosted registry, install semantics, explain surface, and CLI behavior with Spec 34.

21 files changed
+690
-42
Undo
README.md
docs/extension-author-guide.md
docs/extensions-and-migrations.md
src/CLI/Commands/PackCommand.php
src/Packs/HostedPackRegistry.php
src/Packs/HostedPackRegistryEntry.php
src/Packs/InstalledPackRegistry.php
src/Packs/LocalPackLoader.php
src/Packs/PackArchiveExtractor.php
src/Packs/PackChecksum.php
src/Packs/PackManager.php
src/Packs/PackManifest.php
src/Support/ApiSurfaceRegistry.php
tests/Fixtures/Packs/acme-zeta/foundry.json
tests/Fixtures/Packs/foundry-blog-duplicate/foundry.json
tests/Fixtures/Packs/foundry-blog-tools/foundry.json
tests/Fixtures/Packs/foundry-blog/foundry.json
tests/Integration/CLIPackCommandsTest.php
tests/Unit/HostedPackRegistryTest.php
tests/Unit/PackArchiveExtractorTest.php
tests/Unit/PackManifestTest.php

------------------------------------------------------------------------------------------

------------------------------------------------------------------------------------------

------------------------------------------------------------------------------------------
