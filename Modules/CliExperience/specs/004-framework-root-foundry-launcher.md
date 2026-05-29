# Execution Spec: 004-framework-root-foundry-launcher

## Feature
- cli-experience

## Purpose
- Reduce typing for framework-repository workflows by making `./foundry ...` the canonical local command form instead of `php bin/foundry ...`.
- Preserve deterministic command resolution by avoiding bare `foundry` in the framework repository unless it is explicitly known to resolve to the project-local launcher.

## Context
- Generated Foundry apps already use a project-local `foundry` launcher and app-facing guidance tells users to run `foundry ...`.
- The framework repository currently documents `php bin/foundry ...` to avoid accidentally invoking a stale global `foundry` binary.
- The shorter and safer framework-repository equivalent is `./foundry ...`, because it is explicit, local, and shell-independent.
- This spec is a draft only. Do not implement it until it is promoted out of `specs/drafts/`.

## Requested Changes

### A. Add A Framework-Root Launcher
- Add an executable root-level `foundry` launcher in the framework repository.
- The launcher must delegate to `bin/foundry` in the same repository.
- The launcher must fail clearly when `bin/foundry` is missing or unreadable.
- The launcher must preserve all arguments exactly.
- The launcher must not depend on global Composer binaries or a globally installed `foundry`.

### B. Prefer Valet/Homebrew PHP For The Launcher
- The framework-root launcher must avoid defaulting to Herd-owned `php` when a Valet/Homebrew PHP binary is available.
- Use the same PHP-selection principle as the coverage wrapper:
  - explicit `PHP_BIN` wins
  - then `/opt/homebrew/bin/php`
  - then `/usr/local/bin/php`
  - then PATH `php` as fallback
- The launcher does not need Xdebug, but it should still prefer the Valet/Homebrew PHP binary so CLI behavior is consistent with the migrated local environment.

### C. Update Framework-Facing Documentation
- Update framework-repository guidance from `php bin/foundry ...` to `./foundry ...` where the command is intended to be run from the framework root.
- Keep generated-app guidance as `foundry ...`.
- Do not replace app-facing `foundry ...` examples with `./foundry ...` unless those examples explicitly refer to running a project-local launcher from the app root.
- Where ambiguity matters, explain:
  - framework repo: `./foundry ...`
  - generated apps: `foundry ...`
  - avoid bare `foundry` in the framework repo unless PATH is intentionally configured to the local launcher

### D. Update Scaffold And Demo Surfaces Only Where Appropriate
- Keep scaffolded apps ending with a local `foundry` launcher as they do today.
- Do not change app scaffold commands that intentionally document `foundry ...`.
- Update framework demo scripts, contributor docs, and agent instructions when they refer to framework-root commands.
- Preserve demo readability by using `./foundry ...` in framework-repo setup or validation snippets only when those snippets are meant to run in this repository.

### E. Update Command Prefix Detection If Needed
- Review `CliCommandPrefix` and related doctor/help output.
- Framework-repository command-prefix output should prefer `./foundry` once the root launcher exists.
- Generated-app command-prefix output should remain `foundry` or `./foundry` according to existing app-local rules.
- JSON output shape must remain deterministic.

## Acceptance Criteria
- A framework-root executable `foundry` exists and delegates to `bin/foundry`.
- `./foundry --version` or the nearest existing harmless command succeeds from the framework repository root.
- `./foundry verify context --feature=cli-experience --json` succeeds from the framework repository root.
- Framework-facing docs and AGENTS guidance use `./foundry ...` instead of `php bin/foundry ...` for framework-root commands.
- Generated-app docs still use `foundry ...` where appropriate.
- Help/doctor command-prefix output is deterministic and reflects the intended local command form.
- Tests cover:
  - root launcher existence and executable bit
  - argument forwarding to `bin/foundry`
  - PHP binary candidate ordering when the launcher chooses a PHP executable
  - framework command-prefix output
  - scaffold docs remaining app-facing
- Existing CLI surface verification remains green.
- `php vendor/bin/phpunit` passes.
- `bin/phpunit-coverage --coverage-clover build/coverage/clover.xml` exits 0.
- `php bin/foundry verify coverage --min=90 --clover=build/coverage/clover.xml --json` is run if the repository is expected to claim full implementation completion.

## Non-Goals
- Do not require users to modify global shell PATH.
- Do not install or overwrite a global `foundry` executable.
- Do not remove `php bin/foundry`; it may remain a supported fallback.
- Do not change generated app command semantics beyond documentation alignment if needed.
- Do not weaken command-surface verification or quality gates.

## Implementation Notes
- Prefer a small POSIX shell launcher similar in spirit to `bin/phpunit-coverage`.
- Keep the launcher simple enough that it can be audited at a glance.
- If there is already shared PHP-binary selection logic after quality-enforcement updates, reuse the same ordering rather than creating a divergent convention.
- Be careful not to edit generated output under `app/generated/*`.

## Verification Commands

```bash
./foundry verify context --feature=cli-experience --json
./foundry doctor --json
./foundry verify features --json
./foundry spec:validate --json
php vendor/bin/phpunit
bin/phpunit-coverage --coverage-clover build/coverage/clover.xml
php bin/foundry verify coverage --min=90 --clover=build/coverage/clover.xml --json
```

## Completion Signals
- The framework repository can be worked from the root with `./foundry ...`.
- Framework docs no longer train agents and humans to type `php bin/foundry ...` for ordinary local commands.
- Generated app docs still teach the app-local `foundry ...` workflow.
- The new launcher does not reintroduce Herd-first PHP behavior.
