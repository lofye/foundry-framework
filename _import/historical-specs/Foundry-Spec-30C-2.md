SPEC 30C-2 — Monetization Realignment (Framework)

Purpose

Reframe the existing monetization system (30–30C) so that:
	•	No core capabilities are license-gated
	•	Monetization is service-oriented, not feature-restrictive
	•	Feature flags become capability descriptors, not locks

⸻

Goals
	1.	Remove all gating on:
	•	explain
	•	generate
	•	diagnostics
	•	confidence
	•	automation
	2.	Preserve:
	•	MonetizationService
	•	FeatureFlags
	•	LicenseCommand
	3.	Redefine licenses as:
identity + service access, not feature unlocks

⸻

Required Changes

1. Disable Feature Gating (Critical)

Refactor:

MonetizationService::isEnabled('feature.flag')

New behavior:
	•	ALWAYS return true for local capabilities
	•	Log usage optionally (for future insight)

public function isEnabled(string $feature): bool
{
    return true;
}

👉 Do NOT delete flags yet—repurpose them.

⸻

2. Redefine FeatureFlags

Instead of:

'generate.full' => 'requires_license'

Use:

'generate.full' => [
  'type' => 'capability',
  'monetization' => 'none'
]

Prepare for future:

'marketplace.access' => [
  'type' => 'service',
  'monetization' => 'licensed'
]


⸻

3. Update CLI Messaging

Remove all:
	•	“requires license”
	•	“upgrade to unlock”

Replace with:
	•	neutral messaging
	•	or nothing at all

⸻

4. Redefine LicenseCommand

License now represents:
	•	identity (future)
	•	marketplace participation (future)

NOT:
	•	unlocking core features

⸻

5. Keep UsageTracker

But:
	•	make it explicitly opt-in
	•	position as future analytics / insights

⸻

Acceptance Criteria
	•	No CLI command is blocked by licensing
	•	All features run without a license
	•	MonetizationService remains intact but non-restrictive
	•	Codebase compiles cleanly with no dead logic
	
------------------------------------------------------------------------------------------

RESULT

Spec 30C-2 is in place. [MonetizationService.php](/Users/lo_fye/code/foundry-framework/src/Monetization/MonetizationService.php) is now non-restrictive: core capability flags always resolve as available, command usage is only tracked through the opt-in tracker, and licenses are treated as future identity/service access rather than feature unlocks. [FeatureFlags.php](/Users/lo_fye/code/foundry-framework/src/Monetization/FeatureFlags.php) now describes each flag with `type` and `monetization`, and [LicenseValidator.php](/Users/lo_fye/code/foundry-framework/src/Monetization/LicenseValidator.php) now models license entitlements around service access instead of core tooling.

The CLI surface was realigned too. [LicenseCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/LicenseCommand.php) now reports core capabilities as always available and shows service access separately, [FeaturesCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/FeaturesCommand.php) now renders descriptor-style rows (`feature / type / monetization / status`), and [Application.php](/Users/lo_fye/code/foundry-framework/src/CLI/Application.php) plus [ApiSurfaceRegistry.php](/Users/lo_fye/code/foundry-framework/src/Support/ApiSurfaceRegistry.php) no longer advertise `explain`, `generate`, `trace`, `diff`, or `doctor --deep` as licensed/blocked. I also removed the dead gating path entirely by deleting `FeatureGate` and `FeatureNotLicensed`, and updated the public docs in [README.md](/Users/lo_fye/code/foundry-framework/README.md) and [docs/architecture-tools.md](/Users/lo_fye/code/foundry-framework/docs/architecture-tools.md).

Verification:
- `php bin/foundry verify cli-surface --json` -> `123` valid, `0` invalid, `0` ambiguous, `0` orphan handlers, coverage `1`
- `php vendor/bin/phpunit` -> `366` tests passed, `2944` assertions

25 files changed
+262
-326
Undo
README.md
docs/architecture-tools.md
src/CLI/Application.php
src/CLI/Commands/Concerns/InteractsWithLicensing.php
src/CLI/Commands/DeepDoctorCommand.php
src/CLI/Commands/DiffCommand.php
src/CLI/Commands/DoctorCommand.php
src/CLI/Commands/ExplainCommand.php
src/CLI/Commands/FeaturesCommand.php
src/CLI/Commands/GenerateCommand.php
src/CLI/Commands/LicenseCommand.php
src/CLI/Commands/TraceCommand.php
src/CLI/ExceptionRenderer.php
src/Documentation/CommandPlaygroundPage.php
src/Monetization/Exceptions/FeatureNotLicensed.php
src/Monetization/FeatureFlags.php
src/Monetization/FeatureGate.php
src/Monetization/LicenseValidator.php
src/Monetization/MonetizationService.php
src/Support/ApiSurfaceRegistry.php
tests/Integration/CLIApplicationTest.php
tests/Integration/CLILicenseCommandsTest.php
tests/Integration/CLILicensedCommandsTest.php
tests/Unit/ApiSurfaceRegistryTest.php
tests/Unit/MonetizationLicenseTest.php

------------------------------------------------------------------------------------------



------------------------------------------------------------------------------------------