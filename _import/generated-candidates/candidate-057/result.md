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
