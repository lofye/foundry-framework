# Implementation Plan: 026-storage-surface-cleanup-remove-unfinished-object-storage-support

## Historical Provenance

- Imported spec path: `Modules/PreCanonical/specs/026-storage-surface-cleanup-remove-unfinished-object-storage-support.md`
- Source archive: `_import/pre-canonical-specs.md`
- Legacy name: `26 - Storage Surface Cleanup — Remove Unfinished Object Storage Support`
- Legacy id: `26`
- Canonical pre-canonical id: `026`

## Historical Specification Summary

The original pre-canonical specification body is preserved in the imported execution spec. This reconstruction note records adjacent marked context and result evidence without inferring modern module ownership.

## Historical Preamble Context

No marked preamble block was associated with this spec.

## Historical Implementation Evidence

### Result Block 1

- Name: `26 - Storage Surface Cleanup — Remove Unfinished Object Storage Support`

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

=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-
=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-

## Historical Verification Evidence

Historical verification details, when present, are preserved verbatim inside the paired result blocks above.

## Historical Stabilization Notes

Historical stabilization details, when present, are preserved verbatim inside the paired result blocks above.

## Current Repository Alignment

The imported artifact is intentionally retained under `Modules/PreCanonical` as archive-lineage context. Modern module ownership remains deferred until a separate explicit alignment spec maps the pre-canonical intent into current modules.

## Uncertainty And Reconstruction Notes

No modern module inference was performed. The generated note preserves only the marked archive relationships available through `S`, `R`, and `P` blocks.
