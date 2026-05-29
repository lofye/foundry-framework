# Implementation Plan: 004-framework-root-foundry-launcher

## Implemented Spec Path
- `Modules/CliExperience/specs/004-framework-root-foundry-launcher.md`

## Implementation Summary
- Added a framework-root `foundry` launcher that delegates to `bin/foundry`, forwards arguments unchanged, and fails clearly when prerequisites are missing.
- Implemented launcher PHP resolution order consistent with the repository coverage wrapper: `PHP_BIN`, `/opt/homebrew/bin/php`, `/usr/local/bin/php`, then PATH `php`.
- Updated framework command-prefix detection to prefer `./foundry` for framework-root guidance when the launcher exists.
- Updated framework-facing documentation and the live demo script to use the new framework-root launcher convention.

## Files Introduced
- `foundry`
- `tests/Integration/FrameworkRootFoundryLauncherTest.php`

## Files Modified
- `src/Support/CliCommandPrefix.php`
- `tests/Unit/UpgradeAnalyzerTest.php`
- `AGENTS.md`
- `README.md`
- `docs/architecture-tools.md`
- `docs/architecture/architecture-overview.md`
- `docs/architecture/graph-spec.md`
- `docs/contributor-portal.md`
- `docs/example-applications.md`
- `docs/intro.md`
- `docs/quick-tour.md`
- `docs/reference.md`
- `docs/demos/foundry-blog-live-demo-script.md`
- `Modules/CliExperience/cli-experience.md`
- `Modules/CliExperience/cli-experience.decisions.md`

## Runtime Contracts
- Framework repository users can invoke Foundry with `./foundry ...` from repository root.
- Launcher argument forwarding preserves command and option ordering exactly.
- Framework command-prefix output surfaces `./foundry` when repository-root launcher exists; fallback remains deterministic.

## Deterministic Outputs
- Launcher candidate ordering is fixed and test-covered.
- Command-prefix output for framework-root mode is stable when launcher presence is unchanged.

## Tests Added Or Updated
- Added `tests/Integration/FrameworkRootFoundryLauncherTest.php` for launcher presence, executable contract, argument forwarding via `PHP_BIN`, and candidate-order verification.
- Updated `tests/Unit/UpgradeAnalyzerTest.php` framework-root command-prefix expectation to `./foundry`.

## Verification Commands
```bash
./foundry verify context --feature=cli-experience --json
./foundry doctor --json
php vendor/bin/phpunit tests/Integration/FrameworkRootFoundryLauncherTest.php tests/Unit/UpgradeAnalyzerTest.php
```

## Decisions And Tradeoffs
- Kept `php bin/foundry` as fallback behavior in command-prefix logic to avoid introducing hard failures when launcher file is unavailable in edge contexts.
- Chose a shell launcher to control PHP binary selection order explicitly rather than relying on shebang resolution.

## Reconstruction Notes
- `./foundry --version` is not currently a supported harmless check in this repository; `./foundry doctor --json` and `./foundry verify context ...` were used as launcher success validations.

## Follow-up Dependencies
- None required for this spec.
