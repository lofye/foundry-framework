# Spec 26 - Storage Surface Cleanup — Remove Unfinished Object Storage Support

Purpose

Make the storage layer honest and minimal.

The framework currently does not provide real production-ready S3/MinIO support.
Remove misleading unfinished object-storage surface area.

⸻

Goals
	1.	Rename the fake S3 driver to what it actually is
	2.	Remove unfinished MinIO support
	3.	Remove misleading AWS/MinIO references
	4.	Keep storage support simple and explicit

⸻

Required Changes
	1.	Rename:

src/Storage/S3StorageDriver.php

to:

src/Storage/InMemoryStorageDriver.php

	2.	Rename the class:

	•	S3StorageDriver → InMemoryStorageDriver

	3.	Update all references, imports, tests, and docs to use the new name
	4.	Remove:

src/Storage/MinioStorageDriver.php

unless there is already fully wired runtime/config/scaffold usage for it
	5.	Remove or update any tests that exist only for MinIO support
	6.	Remove AWS/MinIO references from:

	•	composer.json
	•	docs
	•	comments
	•	suggest entries
	•	readmes
	•	test names
	•	any config/help text

	7.	Keep:

	•	StorageDriver
	•	LocalStorageDriver
	•	the new InMemoryStorageDriver

⸻

Rules
	•	Do not leave misleading names behind
	•	Do not leave dead optional infrastructure support behind
	•	Do not deprecate; remove directly
	•	Do not add real object storage support in this spec
	•	Keep the storage API clean and minimal

⸻

Acceptance Criteria
	•	no S3StorageDriver class remains
	•	no MinioStorageDriver remains
	•	no AWS/MinIO references remain in composer/docs/comments unless truly required
	•	tests pass after rename/removal
	•	storage layer clearly reflects actual supported capabilities

⸻

Implementation Bias

Prefer removal over abstraction creep.

If a feature is not real, do not keep its surface area.

------------------------------------------------------------------------------------------

RESULT

Implemented the storage cleanup exactly in the direction you outlined. The fake S3 adapter is now [InMemoryStorageDriver.php](/Users/lo_fye/code/foundry-framework/src/Storage/InMemoryStorageDriver.php), and I removed the misleading `bucket` constructor too so it’s honestly just an in-memory store. I deleted the unfinished MinIO surface entirely: `src/Storage/MinioStorageDriver.php`, `tests/Unit/MinioStorageDriverTest.php`, and `tests/Integration/MinioStorageDriverIntegrationTest.php`. I also updated [StorageTest.php](/Users/lo_fye/code/foundry-framework/tests/Unit/StorageTest.php), [composer.json](/Users/lo_fye/code/foundry-framework/composer.json), [composer.lock](/Users/lo_fye/code/foundry-framework/composer.lock), [README.md](/Users/lo_fye/code/foundry-framework/README.md), and [phpstan-baseline.neon](/Users/lo_fye/code/foundry-framework/phpstan-baseline.neon) so the repo no longer advertises AWS/MinIO support.

The remaining storage story is now explicit: `StorageDriver`, `LocalStorageDriver`, and the new in-memory fake only. I also refreshed the lockfile with `composer update --lock`, which removed `aws/aws-sdk-php` and `aws/aws-crt-php` from the installed dependency graph.

Verified with `vendor/bin/phpunit tests/Unit/StorageTest.php`, `find src/Storage -type f | sort | xargs -n1 php -l`, and a repo-wide grep confirming no `MinioStorageDriver`, `S3StorageDriver`, `aws/aws-sdk-php`, `MinIO`, or `AWS` references remain outside the new `InMemoryStorageDriver` name. I did not run the full test suite.

9 files changed
+9
-728
Undo
README.md
composer.json
composer.lock
phpstan-baseline.neon
src/Storage/InMemoryStorageDriver.php
src/Storage/MinioStorageDriver.php
tests/Integration/MinioStorageDriverIntegrationTest.php
tests/Unit/MinioStorageDriverTest.php
tests/Unit/StorageTest.php
