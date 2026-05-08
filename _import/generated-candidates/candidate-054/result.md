Release-readiness reconciliation is in place.

The biggest changes are architectural truthfulness and first-run clarity. [ARCHITECTURE.md](/Users/lo_fye/code/foundry-framework/ARCHITECTURE.md) is now a root pointer, with the canonical overview moved to [docs/architecture/architecture-overview.md](/Users/lo_fye/code/foundry-framework/docs/architecture/architecture-overview.md) and the execution model reconciled in [docs/architecture/execution-model.md](/Users/lo_fye/code/foundry-framework/docs/architecture/execution-model.md). I also tightened the new-user and contributor paths in [README.md](/Users/lo_fye/code/foundry-framework/README.md), [docs/intro.md](/Users/lo_fye/code/foundry-framework/docs/intro.md), [docs/quick-tour.md](/Users/lo_fye/code/foundry-framework/docs/quick-tour.md), [docs/how-it-works.md](/Users/lo_fye/code/foundry-framework/docs/how-it-works.md), [docs/contributor-portal.md](/Users/lo_fye/code/foundry-framework/docs/contributor-portal.md), and [docs/example-applications.md](/Users/lo_fye/code/foundry-framework/docs/example-applications.md). The Packagist scaffold instructions now use the real `new <target>` flow, and the docs are explicit about framework-authored docs vs website rendering/publishing.

On the CLI side, [src/CLI/Application.php](/Users/lo_fye/code/foundry-framework/src/CLI/Application.php) now supports grouped discovery help, so `php bin/foundry help inspect`, `help verify`, and `help generate` work instead of failing. I aligned first-run scaffold output too: [src/CLI/Commands/InitAppCommand.php](/Users/lo_fye/code/foundry-framework/src/CLI/Commands/InitAppCommand.php) and [APP-README.md](/Users/lo_fye/code/foundry-framework/APP-README.md) now surface that same help-first flow, with coverage updated in [tests/Integration/CLIApplicationTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIApplicationTest.php) and [tests/Integration/CLIInitAppCommandTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/CLIInitAppCommandTest.php). The release report is in [docs-build/release-readiness-report.md](/Users/lo_fye/code/foundry-framework/docs-build/release-readiness-report.md) with a JSON companion at [docs-build/release-readiness-report.json](/Users/lo_fye/code/foundry-framework/docs-build/release-readiness-report.json); it records no blockers in the scope of this pass.

Verified with `php vendor/bin/phpunit tests/Unit/ApiSurfaceRegistryTest.php tests/Integration/CLIApplicationTest.php tests/Integration/CLIInitAppCommandTest.php`, plus manual checks of `php bin/foundry help inspect`, `php bin/foundry help verify`, and `php bin/foundry help generate`. I did not run the full repository test suite.

16 files changed
+509
-375
Undo
APP-README.md
ARCHITECTURE.md
README.md
docs-build/release-readiness-report.json
docs-build/release-readiness-report.md
docs/architecture/architecture-overview.md
docs/architecture/execution-model.md
docs/contributor-portal.md
docs/example-applications.md
docs/how-it-works.md
docs/intro.md
docs/quick-tour.md
src/CLI/Application.php
src/CLI/Commands/InitAppCommand.php
tests/Integration/CLIApplicationTest.php
tests/Integration/CLIInitAppCommandTest.php
