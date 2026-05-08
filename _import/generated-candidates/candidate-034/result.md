{
  "signature": "inspect node",
  "group": "inspect",
  "description": "...",
  "arguments": [],
  "options": []
}


⸻

2. Deterministic Signature Resolution

For every signature:
	•	It must resolve to exactly one of:
	•	a handler
	•	a defined special-case (e.g., help)

⸻

3. Handler Capability Declaration

Each handler must explicitly declare supported signatures.

Add interface:

interface SupportsSignatures
{
    /**
     * @return list<string>
     */
    public function supportedSignatures(): array;
}

OR

public function supportsSignature(string $signature): bool;


⸻

Requirement

All command handlers must implement one of:
	•	supportedSignatures() (preferred)
	•	or supportsSignature()

⸻

4. Dispatch Verification

The system must verify:

A. Coverage

Every signature maps to a handler or special-case.

⸻

B. Valid Execution Path

The handler:
	•	accepts the signature
	•	does not throw unsupported-command errors

⸻

C. No Ambiguity

No signature may match more than one handler.

⸻

D. No Orphan Handlers

Handlers must support at least one signature.

⸻

5. New Command: CLI Surface Verification

Command

foundry verify cli-surface --json


⸻

Output

{
  "total_signatures": 109,
  "valid": 107,
  "invalid": 1,
  "ambiguous": 1,
  "orphan_handlers": 2,
  "coverage": 0.9816,
  "details": {
    "invalid": ["inspect foo"],
    "ambiguous": ["verify something"],
    "orphan_handlers": ["LegacyCommand"]
  }
}


⸻

6. New Command: CLI Surface Inspection

foundry inspect cli-surface --json


⸻

Output

{
  "signatures": [
    {
      "signature": "inspect node",
      "handler": "InspectGraphCommand",
      "group": "inspect"
    }
  ]
}


⸻

7. Doctor Integration

Extend:

foundry doctor --cli

Must include:
	•	coverage %
	•	invalid signatures
	•	ambiguous mappings
	•	orphan handlers

⸻

8. CI Enforcement

CI must fail if:
	•	any signature is unmapped
	•	any signature is ambiguous
	•	any handler is orphaned

⸻

9. Documentation Integrity

README and docs must:
	•	derive CLI lists from ApiSurfaceRegistry
	•	never manually duplicate command lists

⸻

10. Performance Constraints
	•	Verification runs only when explicitly invoked or in CI
	•	No runtime penalty for normal CLI usage

⸻

Acceptance Criteria
	1.	foundry verify cli-surface exists and passes
	2.	100% signature coverage
	3.	zero ambiguity
	4.	zero orphan handlers
	5.	README command list matches registry
	6.	JSON output deterministic

⸻

Impact

Human
	•	increased trust
	•	easier debugging
	•	predictable CLI behavior

LLM
	•	no hallucinated commands
	•	safe command enumeration
	•	reliable execution planning

==========================================================================================

RESULT

Implemented the CLI surface integrity layer end to end. Handler classes now declare their supported signatures through src/CLI/SupportsSignatures.php and src/CLI/Command.php, the shared analysis lives in src/CLI/CliSurfaceVerifier.php, and the application command set is now centralized in src/CLI/Application.php. I wired that into inspect cli-surface, verify cli-surface, and doctor --cli through src/CLI/Commands/InspectGraphCommand.php, src/CLI/Commands/VerifyContractsCommand.php, and src/CLI/Commands/DoctorCommand.php.

I also fixed the existing drift points the verifier exposed: inspect dependencies is now owned by a single handler while still supporting feature targets, and doctor --deep is handled through the main doctor command so the registered handler surface stays unambiguous. The registry and docs were updated in src/Support/ApiSurfaceRegistry.php and README.md, and the new behavior is covered by updated unit/integration tests.

Verification:

php vendor/bin/phpunit passed: 357 tests, 2786 assertions, 3 skipped
php bin/foundry verify cli-surface --json passed with 111/111 valid signatures, 0 invalid, 0 ambiguous, 0 orphan handlers
php bin/foundry doctor --feature=publish_post --cli --json passed and included the CLI surface summary

55 files changed
+923
-54
Undo
README.md
src/CLI/Application.php
src/CLI/CliSurfaceVerifier.php
src/CLI/Command.php
src/CLI/Commands/CacheClearCommand.php
src/CLI/Commands/CacheInspectCommand.php
src/CLI/Commands/CodemodRunCommand.php
src/CLI/Commands/CompileGraphCommand.php
src/CLI/Commands/DoctorCommand.php
src/CLI/Commands/ExportGraphCommand.php
src/CLI/Commands/ExportOpenApiCommand.php
src/CLI/Commands/GenerateFeatureCommand.php
src/CLI/Commands/GenerateIndexesCommand.php
src/CLI/Commands/GenerateIntegrationCommand.php
src/CLI/Commands/GeneratePlatformCommand.php
src/CLI/Commands/GenerateScaffoldCommand.php
src/CLI/Commands/GraphVisualizeCommand.php
src/CLI/Commands/ImpactCommand.php
src/CLI/Commands/InitAppCommand.php
src/CLI/Commands/InspectApiCommand.php
src/CLI/Commands/InspectFeatureCommand.php
src/CLI/Commands/InspectGraphCommand.php
src/CLI/Commands/InspectNotificationCommand.php
src/CLI/Commands/InspectPlatformCommand.php
src/CLI/Commands/InspectResourceCommand.php
src/CLI/Commands/InspectRouteCommand.php
src/CLI/Commands/MigrateDefinitionsCommand.php
src/CLI/Commands/PreviewNotificationCommand.php
src/CLI/Commands/PromptCommand.php
src/CLI/Commands/QueueWorkCommand.php
src/CLI/Commands/ScheduleRunCommand.php
src/CLI/Commands/ServeCommand.php
src/CLI/Commands/UpgradeCheckCommand.php
src/CLI/Commands/VerifyCompatibilityCommand.php
src/CLI/Commands/VerifyContractsCommand.php
src/CLI/Commands/VerifyFeatureCommand.php
src/CLI/Commands/VerifyGraphCommand.php
src/CLI/Commands/VerifyIntegrationCommand.php
src/CLI/Commands/VerifyPipelineCommand.php
src/CLI/Commands/VerifyPlatformCommand.php
src/CLI/Commands/VerifyResourceCommand.php
src/CLI/SupportsSignatures.php
src/Pro/CLI/DeepDoctorCommand.php
src/Pro/CLI/DiffCommand.php
src/Pro/CLI/ExplainCommand.php
src/Pro/CLI/GenerateCommand.php
src/Pro/CLI/ProCommand.php
src/Pro/CLI/TraceCommand.php
src/Support/ApiSurfaceRegistry.php
tests/Integration/CLIApplicationTest.php
tests/Integration/CLIArchitectureToolsCommandsTest.php
tests/Unit/ApiSurfaceRegistryTest.php
tests/Unit/CLIApplicationEdgeCasesTest.php
tests/Unit/CLICommandMatchesTest.php
tests/Unit/GraphDocsGeneratorTest.php

==========================================================================================

Why were 3 tests skipped?

==========================================================================================

You’re right: I should have run coverage in the previous turn. That was my miss. I verified it now, and the repo is currently above the floor: `357 tests`, `2786 assertions`, `3 skipped`, `90.35%` line coverage as of March 27, 2026.

**Why 3 tests were skipped**
- [tests/Unit/MinioStorageDriverTest.php:106](/Users/lo_fye/code/foundry-framework/tests/Unit/MinioStorageDriverTest.php#L106) is written to skip when `aws/aws-sdk-php` is installed. In this repo that package is present, so that test is effectively always skipped.
- [tests/Integration/MinioStorageDriverIntegrationTest.php:62](/Users/lo_fye/code/foundry-framework/tests/Integration/MinioStorageDriverIntegrationTest.php#L62) contains the other two skips. It defaults to MinIO at `http://127.0.0.1:9100` with bucket `foundry-dev`, access key `foundry`, secret `foundry-dev-secret`, and it skips if that service is unreachable or the credentials are wrong.

**Why coverage looked unavailable**
- Plain `php` in this thread resolves to Herd PHP `8.4.18`, and `php -m` shows no `xdebug` or `pcov`, so plain `php vendor/bin/phpunit --coverage-text` will not collect coverage.
- A login `zsh` resolves `/opt/homebrew/bin/php`, which is PHP `8.5.3` with Xdebug `3.5.1`, so coverage works there.
- The repo’s wrapper at [bin/phpunit-coverage:5](/Users/lo_fye/code/foundry-framework/bin/phpunit-coverage#L5) chooses `command -v php`, and [composer.json:38](/Users/lo_fye/code/foundry-framework/composer.json#L38) points `test:coverage` at that wrapper. So PATH decides whether coverage works cleanly.

**How to fix it**
- Use the working command directly:
```bash
/bin/zsh -lc 'XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-text'
```
- Make it permanent by putting Homebrew PHP before Herd in your `zsh` PATH, then restart the shell/app. After that, `php` should resolve to `/opt/homebrew/bin/php`.
- Or enable `xdebug` or `pcov` for the Herd PHP binary if you want plain `php` to support coverage.
- To eliminate the two MinIO integration skips, run MinIO on `127.0.0.1:9100` with the expected bucket/credentials, or set:
  - `FOUNDRY_TEST_MINIO_ENDPOINT`
  - `FOUNDRY_TEST_MINIO_ACCESS_KEY`
  - `FOUNDRY_TEST_MINIO_SECRET_KEY`
  - `FOUNDRY_TEST_MINIO_BUCKET`
  - `FOUNDRY_TEST_MINIO_REGION`
- To eliminate the remaining unit-test skip entirely, we should rewrite [tests/Unit/MinioStorageDriverTest.php:106](/Users/lo_fye/code/foundry-framework/tests/Unit/MinioStorageDriverTest.php#L106), because in this repo its skip condition is always true.

If you want, I can make the repo-side fixes next so `composer test:coverage` is deterministic here and the permanently-skipped MinIO test stops being a skip.
