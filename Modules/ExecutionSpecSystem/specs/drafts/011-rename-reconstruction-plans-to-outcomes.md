# Execution Spec: 011-rename-reconstruction-plans-to-outcomes

## Feature
- execution-spec-system

## Purpose
- Rename post-implementation reconstruction-note directories from `plans/` to `outcomes/`.
- Remove the misleading implication that reconstruction notes are speculative plans.
- Preserve the durable before/after vocabulary:
  - `specs/` = what should be built
  - `outcomes/` = what actually happened

## Scope
- Rename framework module reconstruction-note paths:
  - from `Modules/<Module>/plans/<id>-<slug>.md`
  - to `Modules/<Module>/outcomes/<id>-<slug>.md`
- Rename application feature reconstruction-note paths:
  - from `Features/<Feature>/plans/<id>-<slug>.md`
  - to `Features/<Feature>/outcomes/<id>-<slug>.md`
- Rename legacy docs-feature reconstruction-note paths where still supported:
  - from `docs/features/<feature>/plans/<id>-<slug>.md`
  - to `docs/features/<feature>/outcomes/<id>-<slug>.md`
- Update framework, app scaffold, and contributor documentation to describe `outcomes/` as post-implementation reconstruction notes.
- Update validation, boundary inspection, feature workspace mapping, historical import/reconstruction generators, tests, fixtures, and examples that currently treat `plans/` as reconstruction-note placement.
- Keep active execution specs and drafts under `specs/` and `specs/drafts/` unchanged.

## Non-Goals
- Do not rename `.foundry/plans/`; those are persisted generate-plan records, not feature reconstruction notes.
- Do not rename runtime/compiler "execution plan" concepts, pipeline execution plans, or migration planning terminology when they are not reconstruction-note directories.
- Do not change execution-spec IDs, filenames, or heading rules.
- Do not change implementation-log path rules except where implementation-log validation points to matching `outcomes/` files.
- Do not delete historical files without an explicit migration path.
- Do not make `plans/` and `outcomes/` both authoritative indefinitely.

## Constraints
- Preserve deterministic validation output.
- Preserve existing execution-spec and implementation-log semantics.
- Existing repositories that still have `plans/` should fail with clear migration guidance or be supported by an explicit transitional compatibility rule.
- Completed active specs must still require a matching reconstruction note.
- Reconstruction-note filenames remain `<id>-<slug>.md` and continue to match the implemented spec filename stem.
- Reconstruction-note content requirements remain the same unless this spec explicitly changes terminology.
- Avoid touching unrelated `plans` strings when they mean generation plans, execution plans, migration plans, or ordinary planning prose outside the reconstruction-note contract.

## Requested Changes

### 1. Rename The Reconstruction-Note Directory Contract

Update canonical path contracts from:

```text
Modules/<Module>/plans/
Features/<Feature>/plans/
docs/features/<feature>/plans/
```

to:

```text
Modules/<Module>/outcomes/
Features/<Feature>/outcomes/
docs/features/<feature>/outcomes/
```

The new vocabulary must be explained consistently:

```text
specs/     = execution specs, written before implementation
outcomes/  = reconstruction notes, written after implementation
```

### 2. Update Validation Rules

Update `spec:validate` and the underlying validation service so that:
- reconstruction notes are discovered from `outcomes/`
- active completed framework specs require matching `Modules/<Module>/outcomes/<id>-<slug>.md`
- active application specs require matching `Features/<Feature>/outcomes/<id>-<slug>.md` where application reconstruction-note enforcement applies
- legacy `plans/` reconstruction-note paths are no longer emitted as expected canonical paths
- invalid-path diagnostics mention `outcomes/` as the canonical destination
- duplicate or misplaced reconstruction notes under `plans/` produce deterministic migration diagnostics

Transitional behavior must be explicit. Choose one of these approaches during implementation and document the decision:
- **Strict cutover:** `plans/` is invalid once this spec lands, and diagnostics tell the user to move files to `outcomes/`.
- **Compatibility window:** `plans/` remains readable only as a legacy alias, but all generated paths, validation details, docs, and new files use `outcomes/`.

The implementation must not silently accept both paths when that could hide duplicate or divergent reconstruction notes for the same spec.

### 3. Update Framework Workspace And Boundary Surfaces

Update feature/module workspace mapping and boundary verification so directory summaries and allowed-localized paths use `outcomes/` instead of `plans/`.

Expected surfaces include, but are not limited to:
- feature workspace maps
- `verify features`
- `feature:map`
- `feature:inspect`
- any feature boundary allowlists or summaries that currently include `plans/`

### 4. Update Historical Reconstruction Tooling

Update historical import/reconstruction generators so generated reconstruction notes are written to `outcomes/` rather than `plans/`.

This includes references in:
- historical reconstruction output payloads
- generated path metadata such as `plan_path` if it actually means a reconstruction-note path
- historical module context generation text
- import reports and uncertainty notes

Rename payload keys only when doing so will not break a documented stable contract. If a stable payload currently exposes `plan_path`, keep it temporarily and add `outcome_path`, or document the breaking change explicitly in a decision entry.

### 5. Update CLI And Command Vocabulary

Audit CLI surfaces that use "plan" for reconstruction notes.

Required outcomes:
- Commands that create or validate reconstruction notes must refer to outcomes/reconstruction notes in help text and JSON labels.
- Any command that still truly creates a pre-implementation plan must not be renamed by accident.
- `spec:validate --require-plans` must be handled deliberately:
  - either introduce `--require-outcomes` and keep `--require-plans` as a deprecated alias for one compatibility window
  - or rename the option outright and update every caller and test

The spec implementation must document which compatibility strategy was chosen.

### 6. Update Documentation And Scaffold Templates

Update all active guidance that currently tells users or agents to create/read reconstruction notes under `plans/`.

At minimum, update:
- `AGENTS.md`
- `README.md`
- `APP-AGENTS.md`
- `APP-README.md`
- `docs/features/README.md`
- demo scripts under `docs/demos/`
- repository-local skills that reference reconstruction-note locations
- any module/feature canonical context that uses `plans/` as current active vocabulary

Documentation should say:
- `outcomes/` files are post-implementation reconstruction notes
- they are not speculative plans
- they record what changed, tests, verification, decisions, tradeoffs, deterministic outputs, and follow-up dependencies
- matching IDs connect an execution spec to its outcome note

### 7. Migrate Repository Artifacts

Move existing reconstruction-note directories in the repository:

```text
Modules/*/plans/     -> Modules/*/outcomes/
Features/*/plans/    -> Features/*/outcomes/
docs/features/*/plans/ -> docs/features/*/outcomes/
```

Only move directories that are reconstruction-note directories under the canonical feature/module layout.

Do not move:
- `.foundry/plans/`
- `docs/plans/` if it is migration planning documentation rather than feature reconstruction notes
- source-code variables named `$plans` when they represent runtime execution plans or generate plans
- package or migration planning docs outside the reconstruction-note contract

### 8. Update Tests And Fixtures

Update tests to assert `outcomes/` paths and terminology.

Coverage must include:
- valid module reconstruction notes under `Modules/<Module>/outcomes/`
- valid feature reconstruction notes under `Features/<Feature>/outcomes/`
- legacy or misplaced `plans/` reconstruction-note diagnostics
- missing reconstruction-note expected paths using `outcomes/`
- duplicate active reconstruction notes when both `plans/` and `outcomes/` exist for the same spec, if compatibility mode is chosen
- docs/scaffold assertions for `outcomes/`
- boundary/workspace map output containing `outcomes/`

### 9. Update Demo Language

Update the blog demo script so it describes the before/after vocabulary plainly:

```text
Features/Blog/specs/*.md = executable implementation specs
Features/Blog/outcomes/*.md = post-implementation reconstruction notes
```

Replace examples such as:

```text
Features/Blog/plans/001-posts-markdown-admin-and-rss.md
```

with:

```text
Features/Blog/outcomes/001-posts-markdown-admin-and-rss.md
```

Keep story banter in the demo script unless a future spec asks to split it out.

## Expected Behavior
- New reconstruction notes are created, validated, discovered, and documented under `outcomes/`.
- `plans/` is no longer presented as the canonical reconstruction-note directory.
- Spec-to-outcome matching remains deterministic by ID and slug stem.
- Validation failures tell users the exact `outcomes/` path expected for missing reconstruction notes.
- Existing active specs remain executable without changing IDs or spec filenames.
- Implementation logs still answer whether a spec was implemented; matching `outcomes/` files answer what actually happened.
- Unrelated plan concepts continue to use their existing names and paths.

## Acceptance Criteria
- `php bin/foundry spec:validate --json` passes after the repository migration.
- `php bin/foundry spec:validate --require-outcomes --json` passes if `--require-outcomes` is introduced.
- If `--require-plans` remains temporarily, it behaves as a documented deprecated alias and emits deterministic metadata indicating the canonical name is `--require-outcomes`.
- `php bin/foundry verify context --feature=execution-spec-system --json` passes.
- `php bin/foundry verify features --json` passes.
- Focused validation, workspace, boundary, historical reconstruction, and documentation tests pass.
- Full PHPUnit suite passes.
- Coverage gate passes at the configured threshold.
- No active documentation tells users to put reconstruction notes under `plans/`.
- Repository-local reconstruction-note files live under `outcomes/`.
- `.foundry/plans/` and unrelated execution-plan/generate-plan behavior remain unchanged.

## Verification Commands

```bash
php bin/foundry spec:validate --json
php bin/foundry spec:validate --require-outcomes --json
php bin/foundry verify context --feature=execution-spec-system --json
php bin/foundry verify features --json
php vendor/bin/phpunit
XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-clover build/coverage/clover.xml
php bin/foundry verify coverage --min=90 --clover=build/coverage/clover.xml --json
```

If the implementation chooses a compatibility window for `--require-plans`, also verify:

```bash
php bin/foundry spec:validate --require-plans --json
```

## Documentation Notes
- Prefer "outcome note" or "reconstruction note" in prose.
- Avoid "implementation plan" when referring to post-implementation artifacts.
- Keep "plan" terminology only for genuinely pre-implementation planning or generate-plan/runtime-plan concepts.

## Completion Signals
- The repository has no canonical reconstruction-note path references to `plans/`.
- New specs and completed specs have clear before/after artifact locations.
- Developers can answer the directory question simply:
  - `specs/` contains the work order
  - `outcomes/` contains the result of executing that work order
