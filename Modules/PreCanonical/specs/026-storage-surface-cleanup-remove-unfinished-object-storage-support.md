# Execution Spec: 026-storage-surface-cleanup-remove-unfinished-object-storage-support

## Historical Import Note

This spec was imported from the explicitly marked pre-canonical archive.

- Legacy name: `26 - Storage Surface Cleanup — Remove Unfinished Object Storage Support`
- Legacy id: `26`
- Canonical pre-canonical id: `026`
- Imported module: `PreCanonical`
- Source archive: `_import/pre-canonical-specs.md`

## Original Pre-Canonical Spec

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
