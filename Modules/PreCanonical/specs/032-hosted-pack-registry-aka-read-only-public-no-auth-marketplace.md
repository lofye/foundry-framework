# Execution Spec: 032-hosted-pack-registry-aka-read-only-public-no-auth-marketplace

## Historical Import Note

This spec was imported from the explicitly marked pre-canonical archive.

- Legacy name: `32 — Hosted Pack Registry (aka read-only, public, no auth Marketplace)`
- Legacy id: `32`
- Canonical pre-canonical id: `032`
- Imported module: `PreCanonical`
- Source archive: `_import/pre-canonical-specs.md`

## Original Pre-Canonical Spec

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
