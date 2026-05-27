# Execution Spec: 007-stable-public-api-definition

## Historical Import Note

This spec was imported from the explicitly marked pre-canonical archive.

- Legacy name: `7 - Stable Public API Definition`
- Legacy id: `7`
- Canonical pre-canonical id: `007`
- Imported module: `PreCanonical`
- Source archive: `_import/pre-canonical-specs.md`

## Original Pre-Canonical Spec

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
