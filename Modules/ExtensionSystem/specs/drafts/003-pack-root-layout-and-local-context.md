# Execution Spec: 003-pack-root-layout-and-local-context

## Purpose

Define the canonical filesystem layout, identity model, and local context contract for installable Foundry packs.

This spec makes packs visible, self-describing, and inspectable as first-class repository artifacts while preserving deterministic pack activation through the extension system.

---

## Feature

`extension-system`

---

## Goals

1. Install packs under one canonical repository-visible root.
2. Keep pack identity derived from `foundry.json` and aligned with the filesystem path.
3. Ensure installed packs carry enough local context for humans, agents, explain surfaces, generate surfaces, and inspection surfaces.
4. Treat Foundry-authored, third-party, private, local-development, and marketplace-installed packs identically.
5. Preserve deterministic activation, validation, checksum handling, diagnostics, and JSON output.
6. Keep application features, framework modules, and installable packs physically distinct.

---

## Non-Goals

- Do not implement pack publishing.
- Do not implement package-version range solving.
- Do not add runtime execution semantics for `PackContext` contribution types that are currently metadata-only.
- Do not introduce privileged behavior for the `foundry/*` namespace beyond namespace ownership.
- Do not execute remote code during marketplace discovery or download.
- Do not delete installed pack files as part of `pack remove`.
- Do not manually move framework runtime code into `Packs/`.
- Do not implement a full validator for pack-local execution specs; this spec defines the pack-local naming contract and local-context layout.

---

## Canonical Layout

Installable packs MUST live under the repository-level `Packs/` directory.

Canonical installed pack root:

```text
Packs/{vendor}/{package}/
```

Examples:

```text
Packs/foundry/blog/
Packs/acme/blog/
Packs/lofye/contact-modalities/
```

The path segments MUST be derived from the manifest `name` field:

```json
{
  "name": "vendor/package"
}
```

The active installed pack root MUST contain `foundry.json` at the root:

```text
Packs/{vendor}/{package}/foundry.json
```

The active installed pack root MUST contain runtime source under:

```text
Packs/{vendor}/{package}/src/
```

Optional local-context directories MAY exist under the same root:

```text
Packs/{vendor}/{package}/docs/
Packs/{vendor}/{package}/specs/
Packs/{vendor}/{package}/specs/drafts/
Packs/{vendor}/{package}/plans/
Packs/{vendor}/{package}/tests/
Packs/{vendor}/{package}/resources/
Packs/{vendor}/{package}/public/
```

---

## Identity Rules

Pack identity MUST use the existing manifest name format:

```text
vendor/package
```

Rules:

- `foundry.json` remains the canonical identity source.
- Manifest names MUST continue to pass `PackManifest::isValidName()`.
- Manifest versions MUST continue to pass `PackManifest::isValidVersion()`.
- The install path for `vendor/package` MUST be exactly `Packs/vendor/package`.
- A manifest/path mismatch MUST fail deterministically with a structured extension or pack diagnostic.
- Pack identity comparison remains case-sensitive after validation.
- `foundry/*` is reserved for Foundry-authored packs, but those packs MUST NOT receive special installation, activation, inspect, explain, or generate behavior.

---

## Installation Model

`pack install <path-or-name>` and `extension:install <path-or-name>` MUST install the active pack contents into:

```text
Packs/{vendor}/{package}/
```

Installation rules:

- Local source installation MUST read `foundry.json` from the source root before selecting the target path.
- Hosted marketplace installation MUST extract the archive into a temporary root, validate `foundry.json`, then copy the validated root into `Packs/{vendor}/{package}/`.
- Pack archives MUST continue to contain `foundry.json` and `src/` at the archive root.
- The copied install root MUST preserve `docs/`, `specs/`, `specs/drafts/`, `plans/`, `tests/`, `resources/`, and `public/` when those directories exist.
- Installing a pack whose canonical target already exists MUST fail with a deterministic conflict unless the implementation introduces an explicit replacement command in a future spec.
- The installed-pack activation registry remains `.foundry/packs/installed.json`.
- `.foundry/packs/installed.json` remains the only source of active installed pack activation.
- The registry MUST record the active version and source metadata, but installed active files MUST be loaded from `Packs/{vendor}/{package}/`.
- `pack remove <vendor/package>` MUST continue to deactivate the pack in `.foundry/packs/installed.json` without deleting `Packs/{vendor}/{package}/`.

Backward compatibility rules:

- Existing legacy installed-pack roots under `.foundry/packs/{vendor}/{package}/{version}/` MAY be read during a migration window.
- New installations MUST use `Packs/{vendor}/{package}/`.
- If both the canonical `Packs/{vendor}/{package}/foundry.json` and a legacy `.foundry/packs/{vendor}/{package}/{version}/foundry.json` exist for the same active registry entry, the canonical `Packs/` root MUST win.
- Compatibility support for legacy roots MUST be deterministic and covered by tests.

---

## Local Context Contract

Installed packs SHOULD be self-describing after installation and SHOULD NOT require marketplace access for ordinary inspection.

Pack-local context MAY include:

- `docs/*.md` for current behavior and operational documentation.
- `docs/*.decisions.md` for append-only pack decision history.
- `specs/*.md` for active pack execution specs.
- `specs/drafts/*.md` for non-executable draft pack specs.
- `plans/*.md` for implementation reconstruction notes or bounded coordination artifacts.
- `tests/` for pack-owned test fixtures or standalone tests.

Pack execution specs MUST use the same naming and heading contract as Foundry execution specs:

```text
specs/<id>-<slug>.md
specs/drafts/<id>-<slug>.md
```

First line:

```text
# Execution Spec: <id>-<slug>
```

Pack spec slugs SHOULD describe the change itself and SHOULD NOT repeat the vendor or package identity already provided by the filesystem path.

Preferred:

```text
Packs/foundry/blog/specs/001-posts-rendering-and-rss.md
```

Avoid:

```text
Packs/foundry/blog/specs/001-foundry-blog-posts-rendering-and-rss.md
```

---

## Runtime Boundaries

The layout distinction is:

```text
Features/   application-owned feature code and context
Modules/    framework-owned module specs, state, decisions, specs, and plans
Packs/      installable extension package code and local pack context
```

Rules:

- Framework source remains under `src/*` unless another active spec explicitly changes it.
- Application feature runtime code remains under `Features/<FeatureName>/src/`.
- Pack runtime code lives under `Packs/{vendor}/{package}/src/`.
- Shared framework directories MAY contain only thin registration or loading glue for pack handling.
- Pack-specific behavior MUST live inside the pack root when it belongs to an installed pack.
- Generated, cached, imported, or vendor-owned outputs MUST NOT be used to bypass feature or pack boundaries.

---

## Inspect, Explain, And Generate Integration

The following surfaces MUST recognize canonical pack roots:

- `inspect packs --json`
- `inspect pack <vendor/package> --json`
- `inspect extensions --json`
- `inspect extension pack.<vendor>.<package> --json`
- `verify extensions --json`
- `doctor --json`
- explain origin detection for pack-owned graph nodes
- generate planning and verification snapshots that include pack state

Output requirements:

- Pack rows MUST report `install_path` as `Packs/{vendor}/{package}` for canonical installs.
- Pack rows SHOULD expose local context paths when present.
- Explain origin detection MUST infer `vendor/package` from source paths under `Packs/{vendor}/{package}/`.
- Existing source-path detection for legacy `.foundry/packs/{vendor}/{package}/{version}/` MAY remain for backward compatibility.
- Generate snapshots that watch pack activation state MUST include `.foundry/packs/installed.json` and relevant canonical pack roots when pack files affect generated behavior.
- JSON keys MUST remain stable and deterministic.

---

## Validation Rules

Validation MUST fail deterministically when:

- `Packs/{vendor}/{package}/foundry.json` is missing for an active canonical install.
- `Packs/{vendor}/{package}/src/` is missing for an active canonical install.
- The manifest `name` does not match the canonical path.
- The manifest `version` does not match the active registry entry.
- The manifest checksum does not match the installed canonical root.
- A pack target path already exists during installation.
- Two active packs resolve to the same canonical target path.

Validation MUST remain deterministic for:

- target path resolution
- source metadata recording
- registry normalization
- load order
- collision handling
- diagnostics
- inspect and explain output ordering

---

## Implementation Requirements

Update the extension-system implementation so that:

1. `InstalledPackRegistry` resolves active install roots to `Packs/{vendor}/{package}` for new installations.
2. `PackManager` copies local and hosted pack roots into the canonical `Packs/` target.
3. `LocalPackLoader` loads active canonical pack roots, validates manifest/path alignment, and preserves deterministic diagnostics.
4. Legacy `.foundry/packs/{vendor}/{package}/{version}` loading remains covered during the compatibility window.
5. `PackArchiveExtractor` continues enforcing root-level `foundry.json` and `src/`.
6. `ExplainOrigin` recognizes both canonical `Packs/{vendor}/{package}` paths and legacy `.foundry/packs/{vendor}/{package}/{version}` paths.
7. API surface classification and command documentation describe `Packs/{vendor}/{package}/foundry.json` as the installed pack manifest path.
8. Pack CLI JSON fixtures and expected outputs use canonical `Packs/` paths for new installs.
9. The module spec, state file, and decision ledger are updated to describe the new canonical layout and compatibility behavior.
10. The matching reconstruction note is created under `Modules/ExtensionSystem/plans/003-pack-root-layout-and-local-context.md` after implementation.
11. `Modules/implementation.log` receives the completed spec entry only after implementation and validation pass.

---

## Tests Required

Add or update meaningful tests for:

1. `InstalledPackRegistry::installPath()` returning `Packs/vendor/package`.
2. Local pack installation copying source contents into `Packs/vendor/package`.
3. Hosted pack installation copying extracted archive contents into `Packs/vendor/package`.
4. `pack install --json`, `pack info --json`, and `pack list --json` reporting canonical paths.
5. `pack remove` deactivating the pack without deleting `Packs/vendor/package`.
6. `LocalPackLoader` activating canonical pack roots from `.foundry/packs/installed.json`.
7. Manifest/path identity mismatches failing with deterministic diagnostics.
8. Missing canonical `foundry.json` and missing canonical `src/` failures.
9. Canonical root winning when both canonical and legacy roots exist for the same active registry entry.
10. Legacy `.foundry/packs/{vendor}/{package}/{version}` loading during the compatibility window.
11. Explain origin detection for `Packs/vendor/package/src/...`.
12. Inspect and verify surfaces exposing canonical installed pack rows.
13. Pack local context directories being preserved during installation.
14. API surface classification recognizing `Packs/*/*/foundry.json`.

Tests MUST assert observable behavior and deterministic output. Do not add tautological existence-only tests.

---

## Verification Commands

Focused verification while iterating:

```bash
php bin/foundry verify context --feature=extension-system --json
php vendor/bin/phpunit tests/Unit/InstalledPackRegistryTest.php
php vendor/bin/phpunit tests/Unit/LocalPackLoaderTest.php
php vendor/bin/phpunit tests/Unit/PackManagerTest.php
php vendor/bin/phpunit tests/Unit/ExplainArchitectureCoverageTest.php
php vendor/bin/phpunit tests/Integration/CLIPackCommandsTest.php
php bin/foundry verify extensions --json
```

Feature-boundary verification before completion:

```bash
php bin/foundry verify features --json
```

Completion quality gate:

```bash
php vendor/bin/phpunit
XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-clover build/coverage/clover.xml
php bin/foundry verify coverage --min=90 --clover=build/coverage/clover.xml --json
```

---

## Acceptance Criteria

- New pack installs use `Packs/{vendor}/{package}` as the active installed pack root.
- `.foundry/packs/installed.json` remains the deterministic activation registry.
- Active canonical pack roots validate manifest identity, active version, required files, and checksum.
- Legacy `.foundry/packs/{vendor}/{package}/{version}` roots remain readable during the compatibility window.
- Canonical roots win deterministically when canonical and legacy roots both exist.
- Pack install, list, info, remove, inspect, explain, generate, verify, and doctor surfaces agree on canonical pack paths.
- Installed pack local context directories are preserved and discoverable.
- Foundry-authored packs and third-party packs use identical runtime and lifecycle behavior.
- Context files and implementation logs are updated before the implementation is claimed complete.
- Focused tests, feature-boundary verification, and the full quality gate pass.

---

## Done Means

Foundry has one visible, deterministic installed-pack root layout, pack identity is enforced from `foundry.json` through filesystem paths and activation state, installed packs remain locally self-describing, and framework/application/pack boundaries are clear enough for humans, agents, inspect, explain, and generate workflows to resume without hidden marketplace context.
