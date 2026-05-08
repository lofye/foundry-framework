# Spec 27 - Examples Taxonomy Cleanup

Purpose

Make example terminology consistent across the framework repo.

Required terms:
	•	canonical
	•	reference
	•	framework

Remove outdated terminology and remove Thresholds references from the framework examples docs/catalog.

⸻

Required Changes
	1.	Update examples/README.md
	2.	Update examples/catalog.php
	3.	Update docs/example-applications.md
	4.	Update any related README/docs references that still use the old example taxonomy

⸻

Required Taxonomy

Use exactly these three categories:
	•	canonical
	•	reference
	•	framework

Do not use:
	•	official
	•	supplemental
	•	framework-example
	•	reference-kit
	•	thresholds

⸻

Category Intent

canonical
= primary copyable example applications showing how to build with Foundry today

reference
= richer kits or larger build references that are still inside the repo

framework
= examples that explain framework/compiler/tooling surfaces

⸻

Thresholds

Remove Thresholds from:
	•	examples/README.md
	•	examples/catalog.php
	•	docs/example-applications.md

Thresholds is no longer an in-repo example and must not appear as part of the framework example taxonomy.

⸻

Consistency Rules

The same taxonomy must be used consistently in:
	•	prose
	•	array keys
	•	labels
	•	docs headings
	•	comments if relevant

⸻

Acceptance Criteria
	•	the code and docs use only: canonical, reference, framework
	•	no Thresholds references remain in framework examples docs/catalog
	•	no stale references to Dashboard or AI Pipeline remain if those examples were removed
	•	docs/example-applications.md matches the actual current example set
	
------------------------------------------------------------------------------------------

RESULT

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
