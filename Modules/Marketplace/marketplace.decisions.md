### Decision: establish deterministic local marketplace backend baseline

Timestamp: 2026-05-06T19:01:10Z

**Context**

- Marketplace module execution spec `001-marketplace-backend-minimal-viable` requires a minimal deterministic backend for pack listing, pack detail inspection, and artifact download flows.
- The module had no runtime implementation, no inspect/verify command surfaces, and no local storage contract enforcement.

**Decision**

- Implement local marketplace runtime under `src/Marketplace/*` with deterministic repository, verifier, and controller classes.
- Use `.foundry/marketplace/packs.json` as canonical metadata plus relative artifact paths rooted under `.foundry/marketplace/artifacts/*`.
- Add CLI surfaces `inspect marketplace --json` and `verify marketplace --json` with deterministic pass/fail payload contracts and stable error codes.
- Treat missing marketplace storage/index as a deterministic empty marketplace state.

**Reasoning**

- A minimal local deterministic baseline unlocks extension-pack distribution contracts without prematurely introducing identity, billing, entitlement, or MCP coupling.
- Explicit read-only inspect/verify surfaces make marketplace behavior testable and machine-consumable for future integration layers.

**Alternatives Considered**

- Integrate marketplace flows directly into existing pack install/search services.
- Delay marketplace backend until identity/entitlement specs are ready.
- Add networked hosted-registry behavior in the first backend iteration.

**Impact**

- Marketplace module now has a clear local backend contract and deterministic CLI/handler surfaces.
- Future marketplace specs can extend authentication, monetization, and integration behavior without replacing the base storage and verification model.

**Spec Reference**

- Goals
- Storage Contract
- Endpoint Contract
- CLI Surface
- Acceptance Criteria

### Decision: retain implementation-level command and storage details in marketplace current state

Timestamp: 2026-05-06T19:23:00Z

**Context**

- The canonical marketplace spec captures intent/constraints, while `marketplace.md` records concrete implementation details such as runtime class surfaces, storage paths, and command wiring.
- Context verification reported state divergence without a supporting decision entry.

**Decision**

- Keep the implementation-level details in `Modules/Marketplace/marketplace.md` and treat them as intentional elaboration of the canonical spec.

**Reasoning**

- These details improve resumability for future agents and make verification/debugging paths explicit without changing contractual feature intent.
- The elaboration is additive and consistent with the acceptance criteria.

**Alternatives Considered**

- Reduce current state to a minimal summary and remove implementation details.
- Move all implementation details into the spec, reducing the spec/state separation.

**Impact**

- Context/alignment checks can treat current-state implementation detail as decision-backed rather than untracked divergence.
- Future marketplace module work has stable references for command and storage behavior.

**Spec Reference**

- Expected Behavior
- Acceptance Criteria

### Decision: harden marketplace auth runtime contracts with fail-closed credential lifecycle semantics

Timestamp: 2026-05-06T20:44:00Z

**Context**

- Marketplace execution spec `002.001-marketplace-auth-runtime-contracts` requires deterministic credential-store semantics, expired/malformed fail-closed behavior, deterministic whoami/login/logout contracts, and safe auth integration into inspect/verify marketplace surfaces.
- Existing Marketplace identity runtime handled first-pass auth state but did not fully encode canonical credential shape/lifecycle verification contracts.

**Decision**

- Harden `MarketplaceIdentityStore` as the credential-store abstraction with canonical credential normalization (`token_type`, `access_token`, `expires_at`, `user`), root-aware atomic file persistence, deterministic inspect/verify helpers, and explicit fail-closed invalid/expired states.
- Harden `MarketplaceAuthService` to emit deterministic authenticated/unauthenticated/expired/malformed whoami payloads, deterministic login validation/persistence, idempotent logout semantics, and expired/malformed auth rejection for authenticated request construction.
- Integrate safe auth inspection into `inspect marketplace --json` and deterministic auth verification into `verify marketplace --json` without leaking raw credential secrets.

**Reasoning**

- Later entitlement, purchase, and MCP integrations require stable auth-state contracts that are deterministic, inspectable, and safe-by-default.
- Fail-closed malformed/expired handling prevents accidental credential misuse and aligns CLI/runtime behavior with strict verification expectations.

**Alternatives Considered**

- Keep the initial identity store format and defer lifecycle hardening until entitlement specs.
- Add a separate credential-store class while leaving existing identity store behavior unchanged.
- Perform auth verification only in identity commands and keep inspect/verify marketplace auth-agnostic.

**Impact**

- Marketplace auth runtime now exposes deterministic lifecycle and verification contracts suitable for downstream integrations.
- Inspect/verify marketplace payloads include safe auth visibility while preserving secret redaction guarantees.

**Spec Reference**

- Required Runtime Concepts
- Credential Store Contract
- Token Lifecycle
- Verify / Inspect Integration
- Acceptance Criteria

### Decision: add deterministic marketplace identity and authentication baseline

Timestamp: 2026-05-06T20:05:00Z

**Context**

- Marketplace execution spec `002-marketplace-identity-and-authentication` requires deterministic identity/authentication primitives (`login`, `logout`, `whoami`), token storage, and authenticated Marketplace API request construction.
- The existing Marketplace baseline covered only local pack metadata/artifact inspection and verification.

**Decision**

- Introduce Marketplace identity runtime under `src/Marketplace/*` with deterministic local credential storage at `.foundry/marketplace/identity.json`.
- Add CLI commands `login`, `logout`, and `whoami` with deterministic JSON + text outputs and explicit validation errors for invalid/missing identity input.
- Add deterministic authenticated Marketplace request construction in runtime service behavior for downstream Marketplace API integration.

**Reasoning**

- A local deterministic identity baseline enables authenticated Marketplace workflows without prematurely coupling billing, entitlements, or hosted sync behavior.
- Explicit `whoami` and `logout` surfaces make authentication state inspectable and reversible for agents and users.

**Alternatives Considered**

- Reuse license key state as Marketplace identity credentials.
- Defer identity/authentication until entitlement and purchase specs.
- Add only CLI command shells without runtime identity storage contracts.

**Impact**

- Marketplace module now has deterministic auth primitives required for future entitlement/purchase integration.
- API-surface and command-catalog contracts now include Marketplace identity command flows.

**Spec Reference**

- Purpose
- CLI Commands
- Acceptance Criteria

### Decision: preserve explicit current-state implementation claims for marketplace command and storage wiring

Timestamp: 2026-05-06T19:28:00Z

**Context**

- Current State currently records these concrete implementation claims:
- Framework-owned marketplace runtime now exists under `src/Marketplace/*` with deterministic repository, verifier, index, model, and HTTP-style controller classes.
- Marketplace storage contract uses `.foundry/marketplace/packs.json` plus relative artifact paths under `.foundry/marketplace/artifacts/*`.
- `inspect marketplace --json` returns deterministic storage metadata, pack summaries, and aggregate totals.
- `verify marketplace --json` returns deterministic pass/fail status with stable error codes, checked counts, and non-zero exit status on failures.
- Backend handlers provide deterministic `GET /packs`, `GET /packs/{name}`, and `GET /packs/{name}/{version}/download` semantics through controller methods.
- CLI/API surface and command-catalog contracts include `inspect marketplace` and `verify marketplace`.

**Decision**

- Keep these explicit claims in Current State as intentional implementation detail tied to the canonical marketplace contract.

**Reasoning**

- The claims are deterministic, already implemented, and directly support Marketplace acceptance criteria and expected behavior.
- Capturing this detail in Current State improves resumability and avoids ambiguity during future module specs.

**Alternatives Considered**

- Remove explicit implementation claims from Current State and keep only high-level summaries.
- Move implementation-detail claims into the canonical spec instead of Current State.

**Impact**

- Spec-to-state divergence checks treat these claims as decision-backed documentation.
- Future Marketplace work can extend behavior without losing command/storage baseline context.

**Spec Reference**

- Expected Behavior
- Acceptance Criteria
