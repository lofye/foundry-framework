# Implementation Plan: 034-deterministic-pack-system-local-remote-marketplace-integration

## Historical Provenance

- Imported spec path: `Modules/PreCanonical/specs/034-deterministic-pack-system-local-remote-marketplace-integration.md`
- Source archive: `_import/pre-canonical-specs.md`
- Legacy name: `34 — Deterministic Pack System + Local/Remote Marketplace Integration`
- Legacy id: `34`
- Canonical pre-canonical id: `034`

## Historical Specification Summary

The original pre-canonical specification body is preserved in the imported execution spec. This reconstruction note records adjacent marked context and result evidence without inferring modern module ownership.

## Historical Preamble Context

### Preamble Block 1

- Name: `34 — Deterministic Pack System + Local/Remote Marketplace Integration`

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

## Historical Implementation Evidence

### Result Block 1

- Name: `34 — Deterministic Pack System + Local/Remote Marketplace Integration`

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



=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-

BUILD ORDER:
35, 30D, 30E, 30F, 30G, 36, 37, 38, 39, 40, 41, 42, 43, 44, 44B, 45A, 45B, 45C, 46, 47

## Historical Verification Evidence

Historical verification details, when present, are preserved verbatim inside the paired result blocks above.

## Historical Stabilization Notes

Historical stabilization details, when present, are preserved verbatim inside the paired result blocks above.

## Current Repository Alignment

The imported artifact is intentionally retained under `Modules/PreCanonical` as archive-lineage context. Modern module ownership remains deferred until a separate explicit alignment spec maps the pre-canonical intent into current modules.

## Uncertainty And Reconstruction Notes

No modern module inference was performed. The generated note preserves only the marked archive relationships available through `S`, `R`, and `P` blocks.
