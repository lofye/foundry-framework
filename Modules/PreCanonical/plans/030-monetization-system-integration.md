# Implementation Plan: 030-monetization-system-integration

## Historical Provenance

- Imported spec path: `Modules/PreCanonical/specs/030-monetization-system-integration.md`
- Source archive: `_import/pre-canonical-specs.md`
- Legacy name: `30 — Monetization System Integration`
- Legacy id: `30`
- Canonical pre-canonical id: `030`

## Historical Specification Summary

The original pre-canonical specification body is preserved in the imported execution spec. This reconstruction note records adjacent marked context and result evidence without inferring modern module ownership.

## Historical Preamble Context

No marked preamble block was associated with this spec.

## Historical Implementation Evidence

### Result Block 1

- Name: `30 — Monetization System Integration`

RESULT

Implemented Spec 30 with a shared monetization layer and kept the existing Pro UX working as a compatibility path. The new core is [`src/Monetization/MonetizationService.php`](/Users/lo_fye/code/foundry-framework/src/Monetization/MonetizationService.php#L12), backed by explicit namespaced feature flags in [`src/Monetization/FeatureFlags.php`](/Users/lo_fye/code/foundry-framework/src/Monetization/FeatureFlags.php#L7) and opt-in local usage tracking in [`src/Monetization/UsageTracker.php`](/Users/lo_fye/code/foundry-framework/src/Monetization/UsageTracker.php#L9). It supports local license files, `FOUNDRY_LICENSE_KEY`/`FOUNDRY_PRO_LICENSE_KEY`, activation-time optional remote validation, and no hidden network calls.

I added the new CLI surface in [`src/CLI/Commands/LicenseCommand.php`](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/LicenseCommand.php#L12), registered it in [`src/CLI/Application.php`](/Users/lo_fye/code/foundry-framework/src/CLI/Application.php), and updated the CLI registry/help metadata in [`src/Support/ApiSurfaceRegistry.php`](/Users/lo_fye/code/foundry-framework/src/Support/ApiSurfaceRegistry.php#L343). Existing Pro-gated commands now use explicit monetized flags through the shared service, and `pro enable` / `pro status` remain available as legacy aliases for a clear upgrade path. README guidance was updated in [`README.md`](/Users/lo_fye/code/foundry-framework/README.md).

Coverage is in [`tests/Unit/ProLicenseTest.php`](/Users/lo_fye/code/foundry-framework/tests/Unit/ProLicenseTest.php#L15) and [`tests/Integration/CLILicenseCommandsTest.php`](/Users/lo_fye/code/foundry-framework/tests/Integration/CLILicenseCommandsTest.php#L11). Verified with:
- `vendor/bin/phpunit tests/Unit/ProLicenseTest.php`
- `vendor/bin/phpunit tests/Integration/CLILicenseCommandsTest.php tests/Integration/CLIProCommandsTest.php tests/Integration/CLIApplicationTest.php`
- `php bin/foundry verify cli-surface --json`

21 files changed
+961
-71
Undo
README.md
src/CLI/Application.php
src/CLI/Commands/DoctorCommand.php
src/CLI/Commands/LicenseCommand.php
src/Documentation/CommandCatalog.php
src/Monetization/FeatureFlags.php
src/Monetization/MonetizationService.php
src/Monetization/UsageTracker.php
src/Pro/CLI/Concerns/InteractsWithPro.php
src/Pro/CLI/DeepDoctorCommand.php
src/Pro/CLI/DiffCommand.php
src/Pro/CLI/ExplainCommand.php
src/Pro/CLI/GenerateCommand.php
src/Pro/CLI/ProCommand.php
src/Pro/CLI/TraceCommand.php
src/Pro/FeatureGate.php
src/Pro/LicenseStore.php
src/Pro/LicenseValidator.php
src/Support/ApiSurfaceRegistry.php
tests/Integration/CLILicenseCommandsTest.php
tests/Unit/ProLicenseTest.php


------------------------------------------------------------------------------------------

This looks like a good implementation of Spec 30.

What I like most:
	•	you introduced a real shared monetization layer instead of scattering “Pro” checks everywhere
	•	feature access is now expressed through explicit feature flags, which is much cleaner than ad hoc gating
	•	you kept the old pro enable / pro status flow alive as a compatibility bridge instead of forcing a sharp break
	•	you preserved the important constraint of no hidden network calls
	•	you added tests in the right places:
	•	unit tests for license behavior
	•	integration tests for CLI surface / compatibility
	•	you verified verify cli-surface --json, which is exactly the kind of check I’d want after adding license commands

Architecturally, this feels right:
	•	src/Monetization/* becomes the neutral substrate
	•	src/Pro/* becomes one consumer/presentation layer
	•	the CLI now has a clearer future path for monetized capabilities without contaminating core behavior

A few things I’d keep an eye on:
	1.	Terminology drift
Make sure “Pro,” “license,” and “monetization” each have distinct roles in the docs and code:
	•	Monetization = internal capability system
	•	License = activation mechanism
	•	Pro = current product tier / UX layer
	2.	Feature flag naming discipline
Since you now have namespaced flags, keep them extremely stable. These can become quasi-public contracts faster than expected.
	3.	Usage tracking scope
Since you added UsageTracker, I’d make sure the README and any future docs stay crystal clear that tracking is:
	•	opt-in
	•	local unless explicitly configured otherwise
	•	not required for normal use
	4.	Keep the core usable without Pro
This is the big philosophical line. Based on your summary, it sounds like you respected it. Keep guarding that line.

My verdict:
	•	Spec 30 landed well
	•	I would count this as a success
	•	I would not revisit it unless you later decide to:
	•	split foundry-pro into a separate package
	•	add paid cloud/services
	•	formalize product tiers on the docs site

One follow-up I’d eventually recommend is a small doc/spec pass just for:
	•	Pro / License / Monetization vocabulary and UX consistency
across README, help output, and website copy. That is not urgent, but it will matter before public launch.


=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-

BUILD ORDER:
30B → 30C → (31–35) → 30D → 30E → 30F → 30G → (36–43)

## Historical Verification Evidence

Historical verification details, when present, are preserved verbatim inside the paired result blocks above.

## Historical Stabilization Notes

Historical stabilization details, when present, are preserved verbatim inside the paired result blocks above.

## Current Repository Alignment

The imported artifact is intentionally retained under `Modules/PreCanonical` as archive-lineage context. Modern module ownership remains deferred until a separate explicit alignment spec maps the pre-canonical intent into current modules.

## Uncertainty And Reconstruction Notes

No modern module inference was performed. The generated note preserves only the marked archive relationships available through `S`, `R`, and `P` blocks.
