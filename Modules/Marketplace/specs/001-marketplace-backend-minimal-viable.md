# Execution Spec: 001-marketplace-backend-minimal-viable

## Purpose

Introduce the minimal viable Marketplace backend needed for Foundry pack distribution.

This spec creates a small, deterministic backend surface that can publish, inspect, and download framework extension packs without introducing authentication, monetization, search ranking, reviews, dependency solving, or scaling concerns.

The Marketplace backend is intentionally minimal. It exists to establish the distribution contract that later specs can extend.

## Placement

This is a framework module spec and must live at:

```text
Modules/Marketplace/specs/drafts/001-marketplace-backend-minimal-viable.md
```

When implemented, it must be promoted to:

```text
Modules/Marketplace/specs/001-marketplace-backend-minimal-viable.md
```

If `Modules/Marketplace/` does not exist, create the module context files:

```text
Modules/Marketplace/marketplace.md
Modules/Marketplace/marketplace.spec.md
Modules/Marketplace/marketplace.decisions.md
Modules/Marketplace/specs/
Modules/Marketplace/specs/drafts/
```

Append the implementation result to:

```text
Modules/implementation.log
```

## Terminology

- **Marketplace**: the backend module responsible for publishing pack metadata and downloadable pack artifacts.
- **Pack**: a Foundry extension/distribution unit compatible with the extension-system module.
- **Pack name**: a stable lowercase package identifier such as `vendor/name` or `name`.
- **Pack version**: an immutable semantic version string such as `1.0.0`.
- **Pack artifact**: a downloadable archive file for a specific pack version.
- **Pack index**: the deterministic JSON listing returned by `GET /packs`.

## Goals

1. Create a minimal Marketplace backend module.
2. Add deterministic pack metadata loading from repository-local storage.
3. Add read-only HTTP-style endpoint handlers for:
   - `GET /packs`
   - `GET /packs/{name}`
   - `GET /packs/{name}/{version}/download`
4. Add CLI inspection and verification surfaces for the marketplace backend.
5. Keep storage simple and local.
6. Keep output deterministic and suitable for future MCP integration.
7. Preserve compatibility with future specs:
   - `marketplace/003-auth-and-monetization`
   - `mcp-server/004-marketplace`

## Non-Goals

Do not implement:

- authentication
- authorization
- user accounts
- paid packs
- billing
- licensing
- web UI
- pack reviews
- pack ratings
- search ranking
- dependency solving
- remote registry sync
- CDN integration
- external package hosting
- background jobs
- event bus integration
- write/upload API for third-party users
- MCP tools
- marketplace installation commands

## Storage Contract

Use repository-local file-backed storage for this spec.

The canonical storage root is:

```text
.foundry/marketplace/
```

Required structure:

```text
.foundry/marketplace/
  packs.json
  artifacts/
    <safe-pack-key>/
      <version>/
        pack.zip
```

`packs.json` is the canonical metadata index.

### packs.json Shape

`packs.json` must be deterministic JSON with this shape:

```json
{
  "packs": [
    {
      "name": "vendor/example-pack",
      "display_name": "Example Pack",
      "description": "A short pack description.",
      "vendor": "vendor",
      "latest_version": "1.0.0",
      "versions": [
        {
          "version": "1.0.0",
          "requires_foundry": ">=0.1.0",
          "artifact": "artifacts/vendor__example-pack/1.0.0/pack.zip",
          "sha256": "<artifact sha256>",
          "published_at": "2026-01-01T00:00:00Z",
          "metadata": {
            "homepage": null,
            "license": null,
            "tags": []
          }
        }
      ],
      "metadata": {
        "homepage": null,
        "license": null,
        "tags": []
      }
    }
  ]
}
```

Rules:

- `packs` must be an array.
- Pack names must be unique.
- Version strings must be unique within each pack.
- `latest_version` must match one of the declared versions.
- `artifact` must be a relative path under `.foundry/marketplace/`.
- `artifact` must not contain `..`, absolute paths, or path traversal.
- `sha256` must match the artifact file when the artifact exists.
- `published_at` must be an ISO-8601 UTC string.
- Output ordering must be deterministic:
  - packs sorted by `name` ascending
  - versions sorted by semantic version descending when possible, otherwise lexicographic descending
  - tags sorted ascending
  - object keys emitted in a stable order where Foundry controls encoding

### Safe Pack Key

Pack artifact directories must use a filesystem-safe key derived from the pack name:

```text
vendor/example-pack -> vendor__example-pack
example-pack        -> example-pack
```

Allowed pack-name characters:

```text
a-z 0-9 . _ - /
```

Pack names must not:

- begin with `/`
- end with `/`
- contain `//`
- contain `..`
- contain uppercase characters
- contain spaces

Invalid pack names must fail deterministically.

## Runtime Design

Add a marketplace module runtime under canonical framework namespaces, not under `Modules/Marketplace/src/`.

Suggested namespace:

```text
src/Marketplace/
```

Suggested classes:

```text
src/Marketplace/MarketplaceRepository.php
src/Marketplace/MarketplacePack.php
src/Marketplace/MarketplacePackVersion.php
src/Marketplace/MarketplaceIndex.php
src/Marketplace/MarketplaceVerifier.php
src/Marketplace/MarketplaceHttpController.php
```

Exact class names may differ if the existing codebase has a better convention, but the implementation must keep marketplace logic separated from CLI command classes.

## Endpoint Contract

This spec must add an internal HTTP-style backend surface. Use the existing Foundry routing/server abstractions if they exist. If no HTTP router exists yet, implement deterministic controller/handler classes and tests for request-path behavior without introducing a full web server.

The following endpoints are required as backend contracts:

```text
GET /packs
GET /packs/{name}
GET /packs/{name}/{version}/download
```

### GET /packs

Returns the pack index.

Response status:

```text
200
```

Response JSON:

```json
{
  "status": "ok",
  "packs": [
    {
      "name": "vendor/example-pack",
      "display_name": "Example Pack",
      "description": "A short pack description.",
      "vendor": "vendor",
      "latest_version": "1.0.0",
      "versions": ["1.0.0"],
      "metadata": {
        "homepage": null,
        "license": null,
        "tags": []
      }
    }
  ]
}
```

Rules:

- Do not include artifact filesystem paths in the index response.
- Do not include local absolute paths.
- Do not include timestamps generated at request time.
- Empty marketplace returns `status: ok` and `packs: []`.

### GET /packs/{name}

Returns metadata for one pack.

Response status when found:

```text
200
```

Response JSON:

```json
{
  "status": "ok",
  "pack": {
    "name": "vendor/example-pack",
    "display_name": "Example Pack",
    "description": "A short pack description.",
    "vendor": "vendor",
    "latest_version": "1.0.0",
    "versions": [
      {
        "version": "1.0.0",
        "requires_foundry": ">=0.1.0",
        "sha256": "<artifact sha256>",
        "published_at": "2026-01-01T00:00:00Z",
        "download_url": "/packs/vendor/example-pack/1.0.0/download",
        "metadata": {
          "homepage": null,
          "license": null,
          "tags": []
        }
      }
    ],
    "metadata": {
      "homepage": null,
      "license": null,
      "tags": []
    }
  }
}
```

Response status when not found:

```text
404
```

Response JSON:

```json
{
  "status": "error",
  "error": {
    "code": "PACK_NOT_FOUND",
    "message": "Pack not found.",
    "details": {
      "name": "vendor/missing-pack"
    }
  }
}
```

Rules:

- Pack names containing `/` must be handled without ambiguity.
- Do not expose local absolute paths.
- `download_url` must be deterministic.

### GET /packs/{name}/{version}/download

Returns the artifact for a pack version.

Response status when found and valid:

```text
200
```

Response body:

```text
binary artifact bytes
```

Required headers:

```text
Content-Type: application/zip
Content-Disposition: attachment; filename="<safe-pack-key>-<version>.zip"
X-Foundry-Pack-Name: <pack name>
X-Foundry-Pack-Version: <version>
X-Foundry-Pack-Sha256: <sha256>
```

Response status when pack is missing:

```text
404
```

Error code:

```text
PACK_NOT_FOUND
```

Response status when version is missing:

```text
404
```

Error code:

```text
PACK_VERSION_NOT_FOUND
```

Response status when artifact file is missing:

```text
410
```

Error code:

```text
PACK_ARTIFACT_MISSING
```

Response status when artifact checksum does not match:

```text
500
```

Error code:

```text
PACK_ARTIFACT_CHECKSUM_MISMATCH
```

Rules:

- Do not stream or read files outside `.foundry/marketplace/`.
- Do not follow path traversal.
- Do not allow arbitrary file downloads.
- Verify checksum before returning the artifact.
- The implementation may return an internal response object rather than a real HTTP response if the framework does not yet expose HTTP serving.

## CLI Surface

Add deterministic CLI commands:

```bash
php bin/foundry inspect marketplace --json
php bin/foundry verify marketplace --json
```

Plain-text output may also exist, but JSON is the contract.

### inspect marketplace --json

Success output:

```json
{
  "status": "ok",
  "storage": {
    "root": ".foundry/marketplace",
    "index": ".foundry/marketplace/packs.json"
  },
  "packs": [
    {
      "name": "vendor/example-pack",
      "latest_version": "1.0.0",
      "version_count": 1,
      "artifact_count": 1
    }
  ],
  "totals": {
    "packs": 1,
    "versions": 1,
    "artifacts": 1
  }
}
```

Empty marketplace output:

```json
{
  "status": "ok",
  "storage": {
    "root": ".foundry/marketplace",
    "index": ".foundry/marketplace/packs.json"
  },
  "packs": [],
  "totals": {
    "packs": 0,
    "versions": 0,
    "artifacts": 0
  }
}
```

### verify marketplace --json

Passing output:

```json
{
  "status": "pass",
  "checked": {
    "packs": 1,
    "versions": 1,
    "artifacts": 1
  },
  "errors": []
}
```

Failing output:

```json
{
  "status": "fail",
  "checked": {
    "packs": 1,
    "versions": 1,
    "artifacts": 0
  },
  "errors": [
    {
      "code": "PACK_ARTIFACT_MISSING",
      "message": "Pack artifact is missing.",
      "details": {
        "name": "vendor/example-pack",
        "version": "1.0.0",
        "artifact": ".foundry/marketplace/artifacts/vendor__example-pack/1.0.0/pack.zip"
      }
    }
  ]
}
```

Rules:

- `verify marketplace --json` must exit `0` on pass.
- `verify marketplace --json` must exit non-zero on fail.
- Error ordering must be deterministic.
- Error codes must be stable.
- Do not include absolute paths in JSON output.

## Command Registration

Wire the new commands into all relevant command surfaces:

- CLI application registration
- command context/factory if applicable
- command catalog/docs registry
- API surface registry/contract verifier if applicable
- CLI matches tests if applicable

The following should be discoverable consistently:

```text
inspect marketplace
verify marketplace
```

## Scaffold / Init Behavior

Application scaffolding must not create marketplace sample data by default.

It may create or ignore the local marketplace directory only when required by existing storage conventions.

If `.foundry/marketplace/` is generated during tests or local runs, ensure local artifacts are ignored by Git unless there is an explicit fixture path.

Update `.gitignore` only if necessary.

## Compatibility With Modules vs Features

Marketplace is a framework module.

Its governance files must live under:

```text
Modules/Marketplace/
```

Do not create:

```text
Features/Marketplace/
```

Do not treat Marketplace as an application feature.

Application feature layout enforcement from FeatureSystem must remain unchanged.

## Integration With Extension System

This spec must not implement pack installation, but it should keep the metadata shape compatible with extension packs.

At minimum, pack metadata must be able to represent:

- pack name
- display name
- description
- vendor
- version
- required Foundry version constraint
- artifact checksum
- tags
- license
- homepage

If extension-system already defines a pack metadata shape, reuse or map to it rather than creating an incompatible duplicate model.

## Security Requirements

- Reject invalid pack names deterministically.
- Reject invalid version path segments deterministically.
- Prevent path traversal in metadata and request paths.
- Never expose absolute filesystem paths in JSON responses.
- Never serve artifacts outside `.foundry/marketplace/`.
- Verify checksums before returning downloads.
- Use stable error codes for all failures.

## Determinism Requirements

- JSON object shapes must be stable.
- Array ordering must be stable.
- Error ordering must be stable.
- No timestamps generated during inspect/verify output.
- No environment-specific absolute paths in contract outputs.
- No random IDs.
- No network calls.

## Tests Required

Add focused unit and integration tests covering:

### Repository / Storage

- missing `packs.json` produces empty marketplace or deterministic not-initialized result
- empty `packs.json` loads as empty marketplace
- malformed JSON fails deterministically
- duplicate pack names fail deterministically
- duplicate versions fail deterministically
- invalid pack names fail deterministically
- invalid artifact paths fail deterministically
- artifact path traversal rejected
- missing artifact detected
- checksum mismatch detected
- valid artifact checksum passes
- deterministic sorting of packs and versions

### Endpoint Handlers

- `GET /packs` returns deterministic index
- `GET /packs` returns empty list when no packs exist
- `GET /packs/{name}` returns one pack
- `GET /packs/{name}` returns `PACK_NOT_FOUND`
- `GET /packs/{name}/{version}/download` returns artifact response and required headers
- download missing pack returns `PACK_NOT_FOUND`
- download missing version returns `PACK_VERSION_NOT_FOUND`
- download missing artifact returns `PACK_ARTIFACT_MISSING`
- download checksum mismatch returns `PACK_ARTIFACT_CHECKSUM_MISMATCH`
- path traversal request fails deterministically

### CLI

- `inspect marketplace --json` success
- `inspect marketplace --json` empty marketplace
- `verify marketplace --json` pass
- `verify marketplace --json` fail with stable error codes
- command registration/catalog includes `inspect marketplace` and `verify marketplace`
- API surface/CLI contract tests updated if applicable

### Module Context

- `Modules/Marketplace` context validates
- `spec:validate --json` passes after promotion
- implementation log entry is appended to `Modules/implementation.log`

## Acceptance Criteria

The implementation is complete only when all of the following are true:

1. `Modules/Marketplace/` exists with canonical module context files.
2. `Modules/Marketplace/specs/001-marketplace-backend-minimal-viable.md` is promoted out of drafts.
3. Marketplace storage can load deterministic pack metadata from `.foundry/marketplace/packs.json`.
4. Marketplace can verify metadata and artifact integrity.
5. The three endpoint contracts are implemented through the existing HTTP layer or deterministic handler classes.
6. `inspect marketplace --json` exists and returns stable JSON.
7. `verify marketplace --json` exists and returns stable JSON with correct exit codes.
8. Invalid names, versions, artifact paths, missing artifacts, and checksum mismatches fail deterministically.
9. No absolute local paths leak into JSON contract outputs.
10. Command catalog/API surface tests are updated.
11. Feature/application layout rules remain intact.
12. `Modules/implementation.log` contains an entry for this spec.
13. All required verification commands exit `0`.

## Required Verification Commands

Run and require exit `0`:

```bash
php bin/foundry compile graph --json
php bin/foundry inspect graph --json
php bin/foundry inspect pipeline --json
php bin/foundry verify graph --json
php bin/foundry verify graph-integrity --json
php bin/foundry verify pipeline --json
php bin/foundry verify contracts --json
php bin/foundry verify features --json
php bin/foundry verify context --json
php bin/foundry spec:validate --json
php bin/foundry inspect marketplace --json
php bin/foundry verify marketplace --json
php vendor/bin/phpunit
XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-clover build/coverage/clover.xml
php bin/foundry verify coverage --min=90 --clover=build/coverage/clover.xml --json
```

If `inspect marketplace --json` or `verify marketplace --json` fails because no marketplace storage exists, Codex must either:

- make missing storage a deterministic empty marketplace state, or
- create test/bootstrap fixture storage as required by the spec.

Prefer missing storage as an empty marketplace state for minimal viable behavior.

## Implementation Log Entry

After successful implementation, append an entry to:

```text
Modules/implementation.log
```

The entry must include:

- spec path
- implementation date
- summary of changes
- verification commands run
- final status

## Guidance For Codex

Work in this order:

1. Inspect existing extension-system pack metadata conventions.
2. Inspect existing CLI command patterns for `inspect *` and `verify *`.
3. Create Marketplace module context files if missing.
4. Implement storage/repository classes.
5. Implement verifier.
6. Implement endpoint handlers or controller response objects.
7. Implement CLI commands.
8. Wire command catalog/API surfaces.
9. Add tests.
10. Promote the spec out of drafts.
11. Update module context and decision ledger.
12. Append implementation log.
13. Run all required gates.

Do not broaden scope into auth, monetization, MCP, pack installation, or remote publishing.
