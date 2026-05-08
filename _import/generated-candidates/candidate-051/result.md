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
