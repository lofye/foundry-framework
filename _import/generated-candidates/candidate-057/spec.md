# Spec 30B — Monetization Simplification & Terminology Standardization

Preface

Spec 30 introduced a shared monetization system with licensing, feature flags, and CLI commands.

However, since Foundry has not yet launched publicly:
	•	There are no existing users
	•	There is no need for backward compatibility
	•	The current presence of pro enable / pro status introduces unnecessary conceptual debt

This spec simplifies the system before 1.0 by:
	•	removing all legacy “Pro” command UX
	•	standardizing terminology across the framework
	•	aligning CLI, docs, and architecture with a clean mental model

⸻

Goals

1. Remove legacy “Pro” command surface
	•	Delete all CLI commands and aliases using:
	•	pro enable
	•	pro status
	•	foundry pro:*

2. Standardize terminology

Adopt the following definitions everywhere:

Term	Meaning
Monetization	Internal system controlling feature access
License	Mechanism for activating entitlements
Tier	Product level (e.g. free, pro, future tiers)

🚫 “Pro” must not be used as a system concept
✅ It may exist only as a tier name

⸻

3. Make CLI license-first (not Pro-first)

All user interaction moves to:

foundry license activate
foundry license status
foundry license deactivate

No “pro” commands remain.

⸻

4. Keep feature flags as the single source of truth

Feature access must always resolve through:

MonetizationService::isEnabled('feature.flag')

Never:
	•	direct license checks
	•	“pro mode” conditionals
	•	tier string comparisons

⸻

Required Changes

A. Remove Pro CLI surface

Delete:
	•	src/Pro/CLI/ProCommand.php
	•	Any CLI command registered as pro:*
	•	Any aliases pointing to pro

Remove from:
	•	src/CLI/Application.php
	•	ApiSurfaceRegistry
	•	documentation / README

⸻

B. Refactor CLI commands to license system

Ensure only these commands exist:

foundry license activate [--key=...]
foundry license status
foundry license deactivate

Optional:

foundry license validate

⸻

C. Terminology normalization

Replace across codebase:

Old	New
Pro feature	monetized feature
Pro mode	licensed state
Pro license	license
enable pro	activate license

⸻

D. Refactor FeatureGate and related classes

FeatureGate must:
	•	delegate exclusively to MonetizationService
	•	contain no tier-specific logic

Remove:
	•	isPro() style helpers
	•	any boolean “pro enabled” state

⸻

E. Update MonetizationService

Ensure it is explicitly tier-aware but not “Pro-centric”:

public function getTier(): string // e.g. 'free', 'pro'

public function isEnabled(string $feature): bool

Internally:
	•	map features → required tier(s)
	•	resolve against current license

⸻

F. Clean up src/Pro/ namespace

Two acceptable outcomes (choose one):

Option 1 (preferred for 1.0 clarity):
	•	Rename:

src/Pro/ → src/Monetization/Features/

Option 2 (minimal change):
	•	Keep directory but:
	•	remove CLI commands
	•	remove UX references to “Pro”
	•	treat it as internal implementation only

⸻

G. Update CLI help & discovery

Ensure:

foundry help

and:

foundry explain

DO NOT mention:
	•	“Pro mode”
	•	“enable pro”

DO mention:
	•	“Some features require a license”
	•	“Use foundry license activate”

⸻

H. README updates

Replace all sections referring to:

❌ “Pro features”
with:

✅ “Licensed features”

Add a short section:

Licensing
	•	Foundry is fully usable without a license
	•	Some advanced features require a license
	•	Activate with:

foundry license activate --key=YOUR_KEY

	•	No background network calls are performed

⸻

I. Tests

Update or remove:
	•	CLIProCommandsTest
	•	any test asserting pro enable

Add/ensure:
	•	CLILicenseCommandsTest
	•	feature flag gating tests independent of “pro”

⸻

Acceptance Criteria
	•	No CLI command contains pro
	•	No help output contains pro enable or pro status
	•	Feature access works only via MonetizationService
	•	Terminology across codebase is consistent:
	•	monetization / license / tier
	•	All tests pass
	•	CLI discovery (verify cli-surface) passes
	•	README reflects new terminology

⸻

Non-Goals

This spec does NOT:
	•	introduce pricing
	•	implement billing
	•	introduce remote license servers beyond optional validation
	•	change feature availability

⸻

Resulting Mental Model

Before:

“Enable Pro to unlock features”

After:

“Activate a license to enable certain features”

And internally:

“Features are controlled by the monetization system via flags”

⸻

Why This Matters

This change:
	•	removes pre-launch legacy baggage
	•	simplifies the developer mental model
	•	aligns Foundry with clean system boundaries
	•	prevents future confusion between:
	•	product tiers
	•	architecture
	•	CLI UX

------------------------------------------------------------------------------------------

RESULT

Implemented the spec end to end. The CLI is now license-first: `foundry license activate|status|deactivate` is the only licensing surface, the legacy `pro` command was removed from [src/CLI/Application.php](/Users/lo_fye/code/foundry-framework/src/CLI/Application.php), and help/discovery now marks gated commands as `Licensed` instead of `Pro`. The active licensing logic lives under [src/CLI/Commands/LicenseCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/LicenseCommand.php) and [src/Monetization/MonetizationService.php](/Users/lo_fye/code/foundry-framework/src/Monetization/MonetizationService.php), where feature access now resolves through tier-aware `isEnabled(...)` checks and standardized `license / tier / monetization` terminology.

I also moved the licensing primitives into `src/Monetization`, moved the live CLI commands out of `src/Pro/CLI`, deleted `ProCommand`, updated command metadata/docs in [src/Support/ApiSurfaceRegistry.php](/Users/lo_fye/code/foundry-framework/src/Support/ApiSurfaceRegistry.php), and rewrote the public docs in [README.md](/Users/lo_fye/code/foundry-framework/README.md) and [docs/architecture-tools.md](/Users/lo_fye/code/foundry-framework/docs/architecture-tools.md). The old `CLIProCommandsTest` and `ProLicenseTest` coverage was renamed and updated in [tests/Integration/CLILicensedCommandsTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLILicensedCommandsTest.php) and [tests/Unit/MonetizationLicenseTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/MonetizationLicenseTest.php).

Verification passed:
- `php bin/foundry verify cli-surface --json` -> valid `122/122`, invalid `0`, ambiguous `0`, coverage `1`
- `vendor/bin/phpunit tests/Integration/CLILicenseCommandsTest.php tests/Integration/CLILicensedCommandsTest.php tests/Integration/CLIApplicationTest.php tests/Unit/ApiSurfaceRegistryTest.php tests/Unit/CLICommandMatchesTest.php tests/Unit/MonetizationLicenseTest.php` -> `31` tests passed
- `vendor/bin/phpunit` -> `366` tests passed, `7` skipped

27 files changed
+366
-973
Undo
README.md
docs/architecture-tools.md
src/CLI/Application.php
src/CLI/Commands/Concerns/InteractsWithLicensing.php
src/CLI/Commands/DeepDoctorCommand.php
src/CLI/Commands/DiffCommand.php
src/CLI/Commands/DoctorCommand.php
src/CLI/Commands/ExplainCommand.php
src/CLI/Commands/GenerateCommand.php
src/CLI/Commands/LicenseCommand.php
src/CLI/Commands/TraceCommand.php
src/Documentation/CommandCatalog.php
src/Documentation/CommandPlaygroundPage.php
src/Monetization/FeatureFlags.php
src/Monetization/FeatureGate.php
src/Monetization/LicenseStore.php
src/Monetization/LicenseValidator.php
src/Monetization/MonetizationService.php
src/Pro/Generation/AIGenerationService.php
src/Support/ApiSurfaceRegistry.php
tests/Integration/CLIApplicationTest.php
tests/Integration/CLILicenseCommandsTest.php
tests/Integration/CLILicensedCommandsTest.php
tests/Integration/CLIProCommandsTest.php
tests/Unit/ApiSurfaceRegistryTest.php
tests/Unit/CLICommandMatchesTest.php
tests/Unit/ProLicenseTest.php

------------------------------------------------------------------------------------------

------------------------------------------------------------------------------------------

------------------------------------------------------------------------------------------
