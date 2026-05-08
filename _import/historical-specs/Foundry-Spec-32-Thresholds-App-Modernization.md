BUILD ORDER:
32, 33, 34, 35, 30D, 30E, 30F, 30G, 36, 37, 38, 39, 40, 41, 42, 43, 44, 44B, 45A, 45B, 45C, 46, 47

Spec 32 — Hosted Marketplace (Minimal Viable)

⸻

Title

Hosted Pack Registry (Read-Only, Public, No Auth)

⸻

Purpose

Introduce a minimal hosted registry that allows Foundry to:
	•	discover packs remotely
	•	search available packs
	•	install packs via download URLs

This spec is intentionally simple and must:
	•	require no authentication
	•	require no payments
	•	remain fully optional (offline-first still works via Spec 31)

⸻

Core Principle

The hosted registry is a read-only index, not a control system.

It provides discovery and metadata only.
All installation and execution logic remains local.

⸻

Goals
	1.	Provide a public JSON registry of packs
	2.	Enable CLI search and install from remote
	3.	Maintain deterministic, simple behavior
	4.	Avoid introducing auth, licensing, or payments
	5.	Establish a stable contract for future marketplace expansion

⸻

Non-Goals
	•	no authentication
	•	no user accounts
	•	no licensing or entitlements
	•	no payments
	•	no ratings/reviews
	•	no analytics

⸻

🧱 Part 1 — Registry Endpoint

Base Endpoint

GET /registry.json


⸻

Response Format

[
  {
    "name": "vendor/pack",
    "version": "1.0.0",
    "description": "Short description",
    "download_url": "https://example.com/packs/vendor-pack-1.0.0.zip"
  }
]


⸻

Field Requirements

Field	Required	Notes
name	yes	must match vendor/pack format
version	yes	must be semver
description	yes	short, human-readable
download_url	yes	must be HTTPS


⸻

Constraints
	•	registry must return valid JSON
	•	ordering must be stable (sorted by name, then version)
	•	no duplicate name + version entries

⸻

⸻

🧱 Part 2 — Version Handling

Each entry represents a single version of a pack.

The registry may include multiple versions:

[
  { "name": "foundry/blog", "version": "1.0.0", ... },
  { "name": "foundry/blog", "version": "1.1.0", ... }
]


⸻

CLI Resolution Rule

When installing without specifying version:

foundry pack install foundry/blog

System must:
	•	select the highest semver version
	•	deterministically

⸻

Optional Future (not required now)
	•	version constraints (@1.0, @^1.0)
	•	latest alias

⸻

⸻

🧱 Part 3 — CLI Integration

Search

foundry pack search <query>


⸻

Behavior
	•	fetch /registry.json
	•	filter by:
	•	name
	•	description
	•	return sorted results

⸻

Install (Remote)

foundry pack install vendor/pack


⸻

Behavior
	1.	fetch registry
	2.	resolve pack name → latest version
	3.	retrieve download_url
	4.	download archive
	5.	extract to temporary location
	6.	validate foundry.json
	7.	delegate to local install (Spec 31)
	8.	update .foundry/packs/installed.json

⸻

Important Rule

Remote install must reuse exact same install pipeline as local install.

No duplicate logic.

⸻

⸻

🧱 Part 4 — Download Format

Packs must be downloadable as:

.zip archive


⸻

Archive Requirements

Archive must contain:

/foundry.json
/src/...


⸻

Validation Rules
	•	foundry.json must exist at root
	•	manifest must pass Spec 31 validation
	•	extraction must not escape target directory (security)

⸻

⸻

🧱 Part 5 — Caching (Minimal)

CLI may cache registry response:

.foundry/cache/registry.json


⸻

Rules
	•	cache is optional
	•	must be refreshable
	•	must not break determinism

⸻

⸻

🧱 Part 6 — Offline Behavior

If registry is unavailable:
	•	search fails gracefully
	•	remote install fails with clear error
	•	local install (Spec 31) still works

⸻

⸻

🧱 Part 7 — Error Handling

Structured errors for:
	•	registry unavailable
	•	invalid JSON
	•	missing pack
	•	invalid download URL
	•	failed download
	•	invalid archive
	•	manifest validation failure

⸻

Example

{
  "error": "pack_not_found",
  "pack": "vendor/missing-pack"
}


⸻

⸻

🧱 Part 8 — Determinism
	•	registry sorting must be stable
	•	version resolution must be deterministic
	•	install behavior must be identical across runs

⸻

⸻

🧱 Part 9 — Security (Minimal)
	•	only allow HTTPS download URLs
	•	prevent directory traversal during extraction
	•	validate archive contents before install

⸻

⸻

🧱 Part 10 — Testing

Must test:
	•	registry parsing
	•	search filtering
	•	version resolution
	•	remote install flow
	•	archive validation
	•	fallback to local install
	•	error handling

⸻

⸻

🧱 Part 11 — Acceptance Criteria
	•	registry endpoint works and returns valid JSON
	•	CLI can search packs
	•	CLI can install packs from registry
	•	latest version is resolved deterministically
	•	downloaded packs install via Spec 31 pipeline
	•	no auth or payment required
	•	system remains fully usable offline (with local packs)

⸻

⸻

🧠 Done Means

You now have:
	•	a functioning hosted pack index
	•	remote install capability
	•	a stable contract for future marketplace expansion

⸻

🔥 Final Insight

This spec deliberately keeps the registry “dumb”:

It does not decide access, identity, or entitlement.

That allows you to layer:
	•	Spec 45B (auth + entitlements)
	•	Spec 45C (license-aware execution)

on top of it cleanly, without rewriting this layer later.


------------------------------------------------------------------------------------------

RESULT

Spec 32 is implemented. Foundry now has a minimal hosted pack registry client in [HostedPackRegistry.php](/Users/lo_fye/code/foundry-framework/src/Packs/HostedPackRegistry.php) and [HostedPackRegistryEntry.php](/Users/lo_fye/code/foundry-framework/src/Packs/HostedPackRegistryEntry.php), plus secure zip extraction in [PackArchiveExtractor.php](/Users/lo_fye/code/foundry-framework/src/Packs/PackArchiveExtractor.php). `pack install` now resolves local paths first and valid `vendor/pack` names second through the hosted registry inside [PackManager.php](/Users/lo_fye/code/foundry-framework/src/Packs/PackManager.php), so remote installs still reuse the same local install/activation pipeline from Spec 31 after download and extraction. The CLI now supports `foundry pack search <query>` and hosted installs in [PackCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/PackCommand.php), with help/discovery/docs surfaced through [ApiSurfaceRegistry.php](/Users/lo_fye/code/foundry-framework/src/Support/ApiSurfaceRegistry.php), [Application.php](/Users/lo_fye/code/foundry-framework/src/CLI/Application.php), and [CommandCatalog.php](/Users/lo_fye/code/foundry-framework/src/Documentation/CommandCatalog.php).

The hosted layer stays read-only and optional: it reads a public `registry.json`, chooses the highest semver deterministically, requires HTTPS `download_url` values, caches registry metadata at `.foundry/cache/registry.json`, and leaves local/offline pack installs unchanged when the registry is unavailable. I also updated the framework/app docs in [README.md](/Users/lo_fye/code/foundry-framework/README.md), [APP-README.md](/Users/lo_fye/code/foundry-framework/APP-README.md), [APP-AGENTS.md](/Users/lo_fye/code/foundry-framework/APP-AGENTS.md), [extension-author-guide.md](/Users/lo_fye/code/foundry-framework/docs/extension-author-guide.md), and [extensions-and-migrations.md](/Users/lo_fye/code/foundry-framework/docs/extensions-and-migrations.md), and added coverage in [HostedPackRegistryTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/HostedPackRegistryTest.php), [PackArchiveExtractorTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/PackArchiveExtractorTest.php), and [CLIPackCommandsTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIPackCommandsTest.php).

Verification passed:
- `php bin/foundry verify cli-surface --json` -> `128` valid, `0` invalid, `0` ambiguous, `0` orphan handlers, coverage `1`
- `php bin/foundry verify graph --json` -> `ok: true`
- `php vendor/bin/phpunit` -> `386` tests passed, `3062` assertions

19 files changed
+1372
-33
Undo
APP-AGENTS.md
APP-README.md
README.md
docs/extension-author-guide.md
docs/extensions-and-migrations.md
src/CLI/Application.php
src/CLI/Commands/PackCommand.php
src/Documentation/CommandCatalog.php
src/Packs/HostedPackRegistry.php
src/Packs/HostedPackRegistryEntry.php
src/Packs/PackArchiveExtractor.php
src/Packs/PackManager.php
src/Support/ApiSurfaceRegistry.php
tests/Integration/CLIApplicationTest.php
tests/Integration/CLIPackCommandsTest.php
tests/Unit/ApiSurfaceRegistryTest.php
tests/Unit/CLICommandMatchesTest.php
tests/Unit/HostedPackRegistryTest.php
tests/Unit/PackArchiveExtractorTest.php

------------------------------------------------------------------------------------------



------------------------------------------------------------------------------------------



------------------------------------------------------------------------------------------