Master Spec 5A

Testing System Modernization for the Foundry Website Repository

Preface

The Foundry website/docs repository currently contains a custom test runner at:

tests/run.php

This runner performs useful assertions against the documentation build pipeline and rendered site output, but it is not a full testing framework setup and does not provide trustworthy, repository-native code coverage reporting.

The repository currently does not clearly support:
	•	standard test discovery
	•	test suites
	•	coverage instrumentation
	•	coverage thresholds
	•	developer-friendly test execution
	•	CI-friendly coverage enforcement

As a result, prior claims about maintaining ≥ 90% test coverage in the website repo are not sufficiently grounded in repo-local tooling.

This phase modernizes the website repo testing system so that:
	•	tests run under a standard PHP testing framework
	•	coverage is measured explicitly
	•	coverage thresholds can be enforced
	•	the current custom integration assertions are preserved and migrated cleanly
	•	future tests such as ForbiddenInternalTerminologyTest.php have a proper home

This phase applies only to the Foundry website/docs repository.

All work must preserve deterministic docs builds and establish a credible, enforceable testing system for the repo.

⸻

Goals

This phase must:
	1.	introduce a standard PHP testing framework to the website repo
	2.	establish real code coverage measurement
	3.	preserve and migrate the current tests/run.php assertions
	4.	organize tests into clear suites
	5.	make coverage enforcement real and repeatable
	6.	provide a clean foundation for future tests
	7.	maintain or improve test usefulness while reducing ad hoc testing structure

⸻

Core Principle

The website repo must have a real test system, not just a custom script.

The current custom test logic is valuable and should be preserved, but it should be moved into a standard testing framework structure where coverage can be measured and enforced.

⸻

1. Add a Standard Test Framework

Goal

Install and configure a proper PHP testing framework for the website repo.

Requirement

Codex must add one of the following:
	•	PHPUnit (preferred, for consistency with the framework repo)
	•	or Pest built on PHPUnit

Preferred choice:

PHPUnit

unless Codex finds a compelling repo-specific reason to choose Pest.

Required changes

Update composer.json to include:
	•	phpunit/phpunit as a dev dependency
	•	any required coverage tooling support configuration

If necessary, also document required local support for:
	•	Xdebug
	•	or PCOV

for coverage collection.

⸻

2. Add Standard Test Configuration

Goal

Provide a standard project-local test configuration.

Required files

Codex must add/configure:

phpunit.xml

or equivalent.

Configuration requirements

The config must support:
	•	standard unit/integration test discovery
	•	a clear tests directory structure
	•	coverage output
	•	coverage include/exclude rules
	•	bootstrap/autoload configuration

⸻

3. Establish Test Directory Structure

Goal

Organize tests in a maintainable way.

Required structure

Codex should introduce a structure such as:

tests/
  Unit/
  Integration/
  Phrasing/
  Support/

At minimum:
	•	Unit/
	•	Integration/
	•	Phrasing/

Notes
	•	Phrasing/ is where ForbiddenInternalTerminologyTest.php can live later
	•	current tests/run.php logic should be migrated into Unit/ and/or Integration/ tests
	•	helper functions may move into tests/Support/

⸻

4. Migrate the Current Custom Test Runner

Goal

Preserve the behavior of the existing tests/run.php checks, but migrate them into proper PHPUnit test classes.

Current runner responsibilities

The current tests/run.php performs valuable checks including:
	•	framework version extraction
	•	version resolution behavior
	•	CLI metadata extraction
	•	graph/pipeline/diagnostics/extensions extraction
	•	generated docs artifact checks
	•	architecture explorer metadata checks
	•	page rendering checks
	•	manifest generation checks
	•	current/versioned docs publishing checks
	•	immutable version snapshot checks
	•	deterministic output checks
	•	framework-missing failure behavior

These checks must not be lost.

Requirement

Codex must convert these into standard PHPUnit tests, grouped logically.

Suggested grouping:

tests/Unit/
	•	version resolution
	•	metadata extraction helpers
	•	path/manifest helpers
	•	JSON generation helpers

tests/Integration/
	•	full docs build pipeline
	•	rendered page checks
	•	current/versioned publishing
	•	immutable snapshot behavior
	•	deterministic build behavior
	•	architecture explorer output
	•	Ask the Docs output
	•	search index generation

⸻

5. Keep or Retire tests/run.php

Goal

Avoid confusion.

Requirement

After migration, Codex must choose one of these approaches:

Preferred
Retire tests/run.php entirely.

Acceptable fallback
Keep tests/run.php only as a small compatibility wrapper that invokes PHPUnit.

If retained, it must not remain the primary test implementation.

The primary source of truth must be the PHPUnit test suite.

⸻

6. Add Real Coverage Instrumentation

Goal

Make coverage claims real.

Requirement

Codex must configure the repo so that coverage can be measured via:
	•	Xdebug
	•	or PCOV

Preferred approach:
	•	support either if practical
	•	document at least one working local/CI path

Required behavior

The repo must support commands such as:

composer test
composer test:coverage

or equivalents.

Coverage output

Generate at least one of:
	•	terminal summary
	•	Clover XML
	•	HTML coverage report

Codex may choose the exact format(s), but coverage reporting must be explicit and usable.

⸻

7. Coverage Scope Definition

Goal

Ensure coverage reflects the code that matters.

Include in coverage

At minimum, include:
	•	scripts/
	•	scripts/lib/
	•	other first-party PHP logic involved in docs generation and rendering

Exclude from coverage

Exclude things like:
	•	third-party code
	•	generated docs outputs
	•	public/
	•	content/docs/generated/
	•	framework/ submodule
	•	static assets
	•	templates that are not meaningful for PHP line coverage unless directly executable

Codex should set reasonable include/exclude rules.

⸻

8. Enforce Coverage Thresholds

Goal

Make the ≥ 90% rule real.

Requirement

Codex must configure test execution so that the website repo can enforce an overall coverage threshold of:

≥ 90%

This may be implemented via:
	•	PHPUnit configuration
	•	CI enforcement
	•	a small custom coverage-threshold check script
	•	or equivalent

Important note

Coverage must be based on actual instrumentation, not on hand-wavy test count optimism.

⸻

9. Create Shared Test Helpers

Goal

Preserve the useful helper logic from tests/run.php without repeating it across multiple tests.

Candidate helpers to extract

The following kinds of logic should move into reusable test support utilities where appropriate:
	•	temp directory setup/cleanup
	•	file collection
	•	file hashing
	•	docs build invocation
	•	manifest loading
	•	JSON decoding helpers
	•	path removal helpers

These may live under:

tests/Support/


⸻

10. Add the Repository Vocabulary Guardrail After Modernization

Goal

Prepare the repo for ForbiddenInternalTerminologyTest.php.

Requirement

This phase should explicitly establish the testing foundation first.

After this phase is complete, it should be straightforward to add:

tests/Phrasing/ForbiddenInternalTerminologyTest.php

under PHPUnit.

Codex does not need to add that test in this phase unless asked, but the structure must make it natural.

⸻

11. Developer Experience

Goal

Make tests easy to run.

Required commands

Codex must add clear Composer scripts such as:

{
  "scripts": {
    "test": "...",
    "test:coverage": "..."
  }
}

Exact commands may vary, but developers should be able to run tests without memorizing a bespoke shell incantation.

Documentation

Update the website repo README with a short section explaining:
	•	how to run tests
	•	how to run coverage
	•	whether Xdebug or PCOV is required
	•	what the coverage threshold is

⸻

12. CI-Friendliness

Goal

Ensure the test setup can be used in automation.

Requirement

The new test system must be deterministic and suitable for CI.

Codex should ensure:
	•	tests do not depend on interactive input
	•	coverage can be collected in a repeatable way
	•	temp files/directories are cleaned up
	•	version overrides and timestamps remain controllable in tests

⸻

13. Preserve Existing Behavioral Coverage

Goal

Do not regress the quality of the current checks while modernizing.

Requirement

The migrated test suite must still verify the important behaviors currently covered by tests/run.php, including:
	•	framework version resolution priority
	•	CLI metadata extraction
	•	graph/pipeline/diagnostics/extensions extraction
	•	generated docs writing
	•	architecture explorer output
	•	page rendering and docs structure
	•	current/versioned docs publishing
	•	manifest generation
	•	immutable version snapshot behavior
	•	framework-missing failure behavior
	•	deterministic builds
	•	legacy/internal naming suppression checks currently in the script

Codex should treat the current tests/run.php as a functional spec to preserve, not as something disposable.

⸻

14. Testing Requirements

After this phase:
	•	the website repo must have a real test framework
	•	coverage must be measurable
	•	coverage threshold must be enforceable
	•	overall coverage target must be ≥ 90%

This phase itself must not reduce confidence in the docs platform.

⸻

15. Deliverables

Codex must implement:
	•	PHPUnit (or Pest, if justified) in the website repo
	•	composer.json dev test dependency updates
	•	phpunit.xml or equivalent configuration
	•	migrated tests from tests/run.php
	•	clear test directory structure
	•	coverage instrumentation and reporting
	•	coverage threshold enforcement
	•	test helper extraction
	•	README test instructions
	•	optional retirement or wrapping of tests/run.php

⸻

Final Instruction

This phase gives the Foundry website repo a real, credible testing system.

The website/docs platform is now complex enough that custom-script-only testing is no longer sufficient.

After this phase, the website repo should support:
	•	standard test discovery
	•	maintainable test organization
	•	explicit coverage measurement
	•	enforceable coverage thresholds
	•	future guardrail tests such as ForbiddenInternalTerminologyTest.php

Optimize for:
	•	correctness
	•	maintainability
	•	deterministic behavior
	•	honest coverage reporting
	•	developer ergonomics
	•	automated test coverage ≥ 90%
