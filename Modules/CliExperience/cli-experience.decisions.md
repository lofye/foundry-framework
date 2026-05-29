### Decision: create cli-experience as a standalone feature
Timestamp: 2026-04-16T15:00:00-04:00

**Context**
- Foundry’s CLI surface has grown into a meaningful subsystem with its own ergonomics, verification requirements, and developer workflows.
- Shell autocomplete and related usability improvements do not fit cleanly under `context-persistence`, `canonical-identifiers`, or `execution-spec-system`.

**Decision**
- Create a standalone feature named `cli-experience`.
- Track CLI usability and discoverability work under this feature, starting with shell autocomplete.

**Reasoning**
- CLI ergonomics are a real product surface and deserve their own canonical context.
- A dedicated feature keeps usability work organized without diluting other feature boundaries.
- This makes future CLI improvements easier to reason about and sequence.

**Alternatives Considered**
- Keep autocomplete under `context-persistence`.
- Fold CLI usability work into `execution-spec-system`.
- Track CLI ergonomics only through ad hoc execution specs without canonical feature context.

**Impact**
- CLI usability improvements now have a dedicated canonical spec, state document, and decision ledger.
- Future CLI-focused specs can be organized coherently under one feature.

**Spec Reference**
- Purpose
- Goals
- Constraints

### Decision: implement CLI autocomplete as emitted shell scripts backed by CLI-owned completion logic
Timestamp: 2026-04-17T09:49:12-04:00

**Context**
- `cli-experience` needed first-class shell autocomplete without weakening deterministic CLI contracts or introducing a second command registry.
- Dynamic completion for execution-spec workflows needed to respect active versus draft lifecycle rules that already govern `implement spec`.

**Decision**
- Add a stable `completion` CLI command that emits bash and zsh completion scripts.
- Derive static command and subcommand candidates from `Foundry\Support\ApiSurfaceRegistry`.
- Complete `implement spec <feature> <id>` dynamically from `docs/features/`, using active execution-spec ids only by default and excluding drafts.

**Reasoning**
- Emitted scripts keep shell integration explicit, lightweight, and dependency-free.
- Registry-backed static completion keeps help output, CLI verification, and autocomplete aligned with one canonical command surface.
- Active-only spec-id completion preserves execution-spec lifecycle boundaries while still improving CLI ergonomics materially.

**Alternatives Considered**
- Hardcode command lists separately inside shell scripts.
- Include draft execution specs in default completion output.
- Defer autocomplete until a broader shell-integration framework exists.

**Impact**
- Bash and zsh users can enable deterministic Foundry CLI completion immediately.
- CLI help, registry metadata, and surface verification now share the same command source for autocomplete-relevant behavior.
- `implement spec` completion is faster without blurring active and draft execution-spec state.

**Spec Reference**
- Goals
- Constraints
- Expected Behavior
- Acceptance Criteria

### Decision: add first-class batch workflow commands for common CLI execution loops
Timestamp: 2026-05-28T16:46:56-04:00

**Context**
- The live workflow script and routine framework development loops repeatedly executed the same command groups, creating high typing overhead and avoidable drift between documented and practiced command sequences.
- Existing commands already enforced the required checks, but multi-step workflows were fragmented across many manual invocations.

**Decision**
- Introduce CLI-owned batch workflow commands/options for common grouped flows:
  - `doctor --ready`
  - `context bootstrap <feature>`
  - `context recover <feature>`
  - `spec:promote`
  - `verify architecture`
  - `verify feature-work <feature>`
  - `verify done --feature=<feature>`
  - `test feature <feature>`
  - `generate docs --all`
  - `explain feature <feature> --full`
- Back these flows with a shared batch runner that executes deterministic ordered steps and emits an aggregate workflow payload.

**Reasoning**
- First-class commands keep workflows explicit, testable, and discoverable in the framework command surface.
- Shared orchestration logic avoids one-off command chaining implementations and keeps failure handling consistent.
- Users get lower typing burden without weakening existing safety, context, boundary, and quality gates.

**Alternatives Considered**
- Recommend user-local shell aliases only.
- Add an external shell script wrapper for demos.
- Keep manual multi-command instructions and shorten docs only.

**Impact**
- Common developer workflows are now invokable through shorter, deterministic commands.
- CLI surface metadata, verification probes, and command matching tests now include batch workflow surfaces.
- Demo and onboarding workflows can be significantly shorter while preserving the same enforcement semantics.

**Spec Reference**
- Purpose
- Scope
- Constraints
- Requested Changes

### Decision: align cli-experience current state with implemented batch workflow command surface
Timestamp: 2026-05-28T16:46:56-04:00

**Context**
- The module now ships deterministic batch workflow command surfaces and grouped command options for readiness, context bootstrap/recovery, promotion, architecture checks, feature-work checks, done-gate checks, feature-focused testing, full docs generation, and full feature explain dossiers.
- The state document records these implemented CLI capabilities and updated demo usage.
- Context verification requires current-state claims that extend prior spec language to be explicitly decision-backed.

**Decision**
- Treat the current-state claims for the implemented batch workflow command surfaces as canonical module reality after `003-common-workflow-batch-commands`.
- Keep the CLI experience state focused on deterministic command-surface behavior, registry/surface verification alignment, and explicit failure reporting for grouped workflows.

**Reasoning**
- The shipped behavior is concrete, test-covered, and integrated into command registration and API surface verification.
- Recording the state claims explicitly in the decision ledger preserves context resumability and prevents accidental drift classification.
- This keeps the module context truthful while preserving stable deterministic CLI contracts.

**Alternatives Considered**
- Remove new state claims and keep only pre-batch command wording.
- Keep the claims but rely only on execution spec text without a decision entry.
- Delay state updates until a broader follow-up CLI spec.

**Impact**
- Doctor/alignment checks can treat the expanded current-state command-surface claims as intentional and decision-backed.
- The module context now captures that grouped workflows are first-class command surfaces rather than shell-level shortcuts.
- Future CLI ergonomics specs can build on an explicit baseline for batch workflow behavior.

**Spec Reference**
- Purpose
- Goals
- Constraints
- Expected Behavior
- Acceptance Criteria

### Decision: standardize framework-root command usage on ./foundry launcher
Timestamp: 2026-05-29T00:00:00-04:00

**Context**
- Framework contributors were repeatedly typing `php bin/foundry ...`, while generated apps already had shorter project-local launchers.
- The framework repository had migrated toward Valet/Homebrew PHP usage, but launcher entrypoints still risked drifting toward Herd-first resolution in local environments.

**Decision**
- Add a framework-root `foundry` launcher and standardize framework docs and guidance on `./foundry ...`.
- Keep generated-app guidance as `foundry ...` and preserve explicit framework fallback behavior through `php bin/foundry` only when the root launcher is unavailable.
- Update framework command-prefix detection to prefer `./foundry` for framework-root output once the launcher exists.

**Reasoning**
- `./foundry` is explicit, local, and avoids accidental global binary resolution while reducing typing overhead.
- Aligning launcher PHP candidate ordering with existing coverage-wrapper conventions keeps local CLI behavior consistent in Valet/Homebrew environments.
- Keeping app docs on `foundry ...` preserves existing generated-app ergonomics.

**Alternatives Considered**
- Keep `php bin/foundry ...` as the primary framework guidance.
- Use bare `foundry ...` in the framework repository and rely on PATH setup.
- Add only documentation aliases without creating a repository-root launcher.

**Impact**
- Framework-root workflows are shorter and explicit with a local launcher.
- Command-prefix hints and doctor output now reflect `./foundry` for framework repository usage.
- Framework docs and demo setup snippets are aligned with the new launcher convention without changing generated-app command semantics.

**Spec Reference**
- Purpose
- Requested Changes
- Acceptance Criteria
