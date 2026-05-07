BUILD ORDER:
30B → 30C → (31–35) → 30D → 30E → 30F → 30G → (36–43)

Spec 30C — Monetization UX & Product Layer (Framework)

Preface

Spec 30 introduced the monetization system.
Spec 30B simplified and normalized it.

This spec defines how monetization is experienced by developers inside the framework, without:
	•	polluting core architecture
	•	introducing confusion
	•	or degrading the free experience

⸻

Goals
	1.	Make monetization clear, minimal, and non-intrusive
	2.	Ensure zero friction for free users
	3.	Provide discoverable upgrade paths
	4.	Keep system aligned with:
	•	MonetizationService
	•	feature flags
	•	license-first UX

⸻

Core Principles

1. No “mode switching”

There is no:
	•	“Pro mode”
	•	“enable pro”

Only:

“Some features require a license”

⸻

2. No interruptions during normal use

Never:
	•	block commands mid-execution without context
	•	show aggressive upgrade messaging

Always:
	•	fail gracefully
	•	explain why

⸻

3. Monetization is contextual

Only surface licensing when:
	•	user invokes a gated feature
	•	user asks for status
	•	user explores CLI help

⸻

Feature Gate UX Contract

When a gated feature is accessed without license:

CLI Output (Standardized)

This feature requires a license.

To activate:
  foundry license activate --key=YOUR_KEY

Learn more:
  https://foundryframework.org/pricing

Requirements
	•	Must be consistent across all commands
	•	Must not mention “Pro”
	•	Must not expose internal feature flag names

⸻

Implementation

A. Standardize gated command behavior

Create:

src/Monetization/Exceptions/FeatureNotLicensed.php

Usage:

if (! $monetization->isEnabled('generate.full')) {
    throw new FeatureNotLicensed('generate.full');
}


⸻

B. Centralize CLI error rendering

In CLI layer:

src/CLI/ExceptionRenderer.php

Handle:
	•	FeatureNotLicensed

Render standardized message.

⸻

C. Enhance foundry license status

Output should include:

License: Active
Tier: pro

Enabled features:
- explain.advanced
- generate.full
- doctor.deep

Disabled features:
- (list a few key ones, not exhaustive)

If no license:

License: Not active
Tier: free

Some advanced features are unavailable.

Activate with:
  foundry license activate --key=YOUR_KEY


⸻

D. CLI discovery integration

Update:

foundry help

Add footer:

Some advanced features require a license.
Run: foundry license status


⸻

E. Feature flag visibility (optional but recommended)

Add:

foundry features

Output:

Feature                Status
------------------------------
explain.basic          enabled
explain.advanced       disabled (license required)
generate.full          disabled (license required)


⸻

F. Remove all “Pro” language

Across:
	•	CLI output
	•	exceptions
	•	messages
	•	docs in repo

⸻

G. README section

Add:

Licensing

Foundry is fully usable without a license.

Some advanced features require a license.

Activate:

foundry license activate --key=YOUR_KEY

No background network calls are performed.

⸻

Acceptance Criteria
	•	No CLI output references “Pro”
	•	All gated features use FeatureNotLicensed
	•	Messaging is consistent across commands
	•	foundry license status is informative
	•	CLI discovery includes licensing hint
	•	README updated

⸻

Non-Goals
	•	Pricing implementation
	•	Payment flows
	•	Remote billing systems

⸻

Result

Monetization becomes:
	•	invisible when not needed
	•	clear when encountered
	•	consistent everywhere



------------------------------------------------------------------------------------------

RESULT

Spec 30C is implemented. Gated commands now raise a dedicated [FeatureNotLicensed.php](/Users/lo_fye/code/foundry-framework/src/Monetization/Exceptions/FeatureNotLicensed.php) exception, and the CLI renders it centrally through [ExceptionRenderer.php](/Users/lo_fye/code/foundry-framework/src/CLI/ExceptionRenderer.php), so every unlicensed feature now shows the same license-first message with the activation command and pricing link. [LicenseCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/LicenseCommand.php) now reports `License`, `Tier`, and product-facing enabled/disabled features, [FeaturesCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/FeaturesCommand.php) adds `foundry features`, and [Application.php](/Users/lo_fye/code/foundry-framework/src/CLI/Application.php) plus [ApiSurfaceRegistry.php](/Users/lo_fye/code/foundry-framework/src/Support/ApiSurfaceRegistry.php) now surface the new help/discovery hint: “Some advanced features require a license. Run: `foundry license status`”.

I also updated the public docs in [README.md](/Users/lo_fye/code/foundry-framework/README.md) and [docs/architecture-tools.md](/Users/lo_fye/code/foundry-framework/docs/architecture-tools.md), and refreshed the CLI/monetization coverage in [CLILicenseCommandsTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLILicenseCommandsTest.php), [CLILicensedCommandsTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLILicensedCommandsTest.php), and [MonetizationLicenseTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/MonetizationLicenseTest.php). User-facing CLI/docs no longer use “Pro”; the remaining `src/Pro` references are internal implementation only.

Verification:
- `php bin/foundry verify cli-surface --json` -> `123` valid, `0` invalid, `0` ambiguous, `0` orphan handlers, coverage `1`
- `php vendor/bin/phpunit` -> `366` tests passed, `2947` assertions

17 files changed
+500
-73
Undo
README.md
docs/architecture-tools.md
src/CLI/Application.php
src/CLI/Commands/FeaturesCommand.php
src/CLI/Commands/LicenseCommand.php
src/CLI/ExceptionRenderer.php
src/Documentation/CommandCatalog.php
src/Monetization/Exceptions/FeatureNotLicensed.php
src/Monetization/FeatureFlags.php
src/Monetization/MonetizationService.php
src/Support/ApiSurfaceRegistry.php
tests/Integration/CLIApplicationTest.php
tests/Integration/CLILicenseCommandsTest.php
tests/Integration/CLILicensedCommandsTest.php
tests/Unit/ApiSurfaceRegistryTest.php
tests/Unit/CLICommandMatchesTest.php
tests/Unit/MonetizationLicenseTest.php


------------------------------------------------------------------------------------------



------------------------------------------------------------------------------------------



------------------------------------------------------------------------------------------