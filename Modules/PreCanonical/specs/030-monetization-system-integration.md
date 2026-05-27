# Execution Spec: 030-monetization-system-integration

## Historical Import Note

This spec was imported from the explicitly marked pre-canonical archive.

- Legacy name: `30 — Monetization System Integration`
- Legacy id: `30`
- Canonical pre-canonical id: `030`
- Imported module: `PreCanonical`
- Source archive: `_import/pre-canonical-specs.md`

## Original Pre-Canonical Spec

## Purpose
Integrate monetization into the Foundry ecosystem in a way that is invisible by default, respectful to developers, and aligned with the framework’s philosophy of trust, clarity, and control.

## Goals
- Provide optional monetization hooks without polluting core logic
- Enable subscription-based features (Pro tier, hosted services)
- Support future marketplace (packs, extensions, AI features)
- Keep core framework open and fully usable

## Non-Goals
- Do not introduce paywalls into core developer workflows
- Do not require external services to run Foundry locally
- Do not degrade DX for free users

## Architecture

### 1. Monetization Layer
Introduce a `MonetizationService` responsible for:
- License validation
- Feature flagging (free vs paid)
- Usage tracking (if enabled)

### 2. Feature Flags
All monetized features must be guarded by explicit flags:
- `feature.pro.explain_plus`
- `feature.pro.generate`
- `feature.hosted.sync`
- etc.

### 3. License Model
Support:
- local license file
- environment-based key
- optional remote validation

### 4. Privacy First
- No telemetry without explicit opt-in
- No hidden network calls

## CLI Integration
Add:
- `foundry license:status`
- `foundry license:activate`
- `foundry license:deactivate`

## Acceptance Criteria
- Monetization layer exists but is optional
- All paid features are feature-flagged
- No core functionality depends on monetization
- Clear upgrade path for users

## Done Means
Monetization is present, clean, optional, and future-ready without compromising trust.
