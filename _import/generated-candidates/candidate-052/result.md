Updated the example taxonomy to use only `canonical`, `reference`, and `framework` across the example system. The main changes are in [examples/catalog.php](/Users/lo_fye/code/foundry-framework/examples/catalog.php), [examples/README.md](/Users/lo_fye/code/foundry-framework/examples/README.md), [docs/example-applications.md](/Users/lo_fye/code/foundry-framework/docs/example-applications.md), and [README.md](/Users/lo_fye/code/foundry-framework/README.md). I renamed the catalog key from `official` to `canonical`, collapsed all example `kind` values to the same three taxonomy words, and removed the `thresholds` catalog entry entirely.

I also cleaned the related wording in [examples/extensions-migrations/README.md](/Users/lo_fye/code/foundry-framework/examples/extensions-migrations/README.md), [examples/reference-blog/README.md](/Users/lo_fye/code/foundry-framework/examples/reference-blog/README.md), and [src/Documentation/LearningPathsPage.php](/Users/lo_fye/code/foundry-framework/src/Documentation/LearningPathsPage.php), then updated [tests/Integration/ExamplesStructureTest.php](/Users/lo_fye/code/foundry-framework/tests/Integration/ExamplesStructureTest.php) so it enforces the new taxonomy and the absence of Thresholds in the framework examples docs/index.

Verified with `vendor/bin/phpunit tests/Integration/ExamplesStructureTest.php`. I did not run the full test suite.

8 files changed
+39
-54
Undo
README.md
docs/example-applications.md
examples/README.md
examples/catalog.php
examples/extensions-migrations/README.md
examples/reference-blog/README.md
src/Documentation/LearningPathsPage.php
tests/Integration/ExamplesStructureTest.php
