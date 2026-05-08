# Spec 7 — Stable Public API Definition

Preface

Foundry has reached the point where it needs a clearly defined distinction between:
	•	public, supported framework surface
	•	internal, changeable implementation details

Without this distinction, every release risks accidental breakage, documentation ambiguity, and extension instability.

Spec 7 defines Foundry’s stable public API surface so that users, extension authors, and future maintainers know what is safe to depend on.

All new code must maintain ≥ 90% automated test coverage.

Goals

Spec 7 must:
	•	define what parts of Foundry are public
	•	define what parts are internal
	•	establish naming/documentation rules for public APIs
	•	establish semantic-versioning expectations for public APIs
	•	expose this information in docs and CLI/help where appropriate

Requirements

1. Public surface classification

Codex must classify framework components into categories such as:
	•	Public API
	•	Extension API
	•	Internal API
	•	Experimental API

This classification should apply to at least:
	•	PHP namespaces/classes/interfaces
	•	CLI commands
	•	configuration formats
	•	manifests/schemas
	•	extension hooks
	•	generated metadata formats

2. Public namespace rules

Codex must establish conventions so that public APIs are easily identifiable.

Example approaches:
	•	dedicated public namespaces
	•	PHPDoc annotations
	•	internal namespace markers
	•	explicit metadata registry of public APIs

3. Semantic-versioning rules

Define rules such as:
	•	public API changes must be semver-respecting
	•	internal APIs may change without semver guarantees
	•	experimental APIs may change with warnings
	•	extension APIs must be versioned and documented carefully

4. CLI stability classification

CLI commands should be classified similarly:
	•	stable
	•	experimental
	•	internal/developer-only

This should affect:
	•	help text
	•	generated CLI reference docs
	•	extension author expectations

5. Documentation

Add framework docs explaining:
	•	what is safe to depend on
	•	what is internal
	•	what extension authors should use
	•	what may change before/after 1.0

Deliverables
	•	public/internal API classification system
	•	documented namespace or annotation strategy
	•	semver policy for public APIs
	•	CLI command stability classification
	•	updated docs/reference output

Testing Requirements

Tests must cover:
	•	classification metadata generation
	•	public/internal API detection
	•	CLI stability exposure
	•	docs generation for API classifications

Coverage must remain ≥ 90%.

==========================================================================================

RESULT

Implemented Spec 7 around a single registry in [ApiSurfaceRegistry.php](/Users/lo_fye/code/foundry-framework/src/Support/ApiSurfaceRegistry.php#L1). It now classifies PHP namespace rules, exact extension hooks, CLI commands, config/manifests, generated metadata, and the semver policy attached to each surface. That registry is wired into CLI help in [Application.php](/Users/lo_fye/code/foundry-framework/src/CLI/Application.php#L43), exposed via `inspect api-surface` in [InspectGraphCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/InspectGraphCommand.php#L21), and reused by docs generation in [GraphDocsGenerator.php](/Users/lo_fye/code/foundry-framework/src/Documentation/GraphDocsGenerator.php#L11) so `generate docs` now emits `api-surface` and `cli-reference` pages from the same source of truth.

The human-facing policy is documented in [docs/public-api-policy.md](/Users/lo_fye/code/foundry-framework/docs/public-api-policy.md#L1), and the CLI/readme entry points were updated in [README.md](/Users/lo_fye/code/foundry-framework/README.md#L176). Coverage for symbol detection, CLI stability/help exposure, and generated docs was added in [ApiSurfaceRegistryTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/ApiSurfaceRegistryTest.php#L1), [CLIApplicationTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIApplicationTest.php#L104), and the existing docs/CLI tests.

Verification: `php vendor/bin/phpunit` passed (`225` tests, `1315` assertions, `3` skipped). I also ran `php bin/foundry help --json`, `php bin/foundry help graph visualize --json`, `php bin/foundry inspect api-surface --command='compile graph' --json`, and `php bin/foundry generate docs --format=markdown --json`.

12 files changed
+1096
-3
Undo
README.md
docs/public-api-policy.md
src/CLI/Application.php
src/CLI/CommandContext.php
src/CLI/Commands/InspectGraphCommand.php
src/Documentation/GraphDocsGenerator.php
src/Support/ApiSurfaceRegistry.php
tests/Integration/CLIApplicationTest.php
tests/Integration/CLIIntegrationCommandsTest.php
tests/Unit/ApiSurfaceRegistryTest.php
tests/Unit/CLICommandMatchesTest.php
tests/Unit/GraphDocsGeneratorTest.php
