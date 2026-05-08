# Spec 30 — Monetization System Integration

## Purpose
Integrate monetization into the Foundry ecosystem in a way that is invisible by default, respectful to developers, and aligned with the framework’s philosophy of trust, clarity, and control.

## Goals
- Provide optional monetization hooks without polluting core logic
- Enable subscription-based features (Pro tier, hosted services)
- Support future marketplace (packs, extensions, AI features)
- Keep core framework open and fully usable

## Non-Goals
- Do not introduce paywalls into core developer workflows
- Do not require external services to run Foundry locally
- Do not degrade DX for free users

## Architecture

### 1. Monetization Layer
Introduce a `MonetizationService` responsible for:
- License validation
- Feature flagging (free vs paid)
- Usage tracking (if enabled)

### 2. Feature Flags
All monetized features must be guarded by explicit flags:
- `feature.pro.explain_plus`
- `feature.pro.generate`
- `feature.hosted.sync`
- etc.

### 3. License Model
Support:
- local license file
- environment-based key
- optional remote validation

### 4. Privacy First
- No telemetry without explicit opt-in
- No hidden network calls

## CLI Integration
Add:
- `foundry license:status`
- `foundry license:activate`
- `foundry license:deactivate`

## Acceptance Criteria
- Monetization layer exists but is optional
- All paid features are feature-flagged
- No core functionality depends on monetization
- Clear upgrade path for users

## Done Means
Monetization is present, clean, optional, and future-ready without compromising trust.


------------------------------------------------------------------------------------------

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

------------------------------------------------------------------------------------------



------------------------------------------------------------------------------------------
