# Spec: Normalize New-Project Bootstrap and App Layout

## Goal

Make the Foundry framework support a clean, conventional, project-local bootstrap flow for new applications, and remove the accidental `app/platform/...` layout that came from website-hosting constraints rather than framework design.

The framework should treat a normal application as:

```text
website/
  app/
  bootstrap/
  config/
  database/
    migrations/
  lang/
  public/
  storage/
  vendor/
```

## Important constraint

No legacy compatibility layer is required for `app/platform/...`.

This framework is not in use yet, so the implementation should remove the old path assumptions outright instead of carrying fallback logic for old apps, old examples, or old generated output.

## Non-goals

- Do not preserve `app/platform/...` as a supported app layout.
- Do not keep examples or tests on the old layout.
- Do not keep website-hosting quirks embedded in framework defaults.
- Do not defer the package-name fix.

## Current problems to fix

### 1. Wrong package name in scaffolded apps

The scaffold currently writes `lofye/foundry` into generated `composer.json` and payload metadata, but the real package is `lofye/foundry-framework`.

This must be fixed everywhere in the framework repo where the scaffold or docs generate or describe package requirements.

### 2. `foundry new` in the current directory is not a clean first-class flow

Desired behavior:

- `foundry new`
  - initializes the current directory
- `foundry new .`
  - does the same thing explicitly
- `foundry new website`
  - creates `website/` and initializes the app there

The current implementation treats the target path as required and does not cleanly support the intended current-directory experience.

### 3. Composer-first bootstrap flow is awkward / misleading

The framework should support this as the normal scratch flow:

```bash
mkdir website
cd website
composer require lofye/foundry-framework
foundry new --starter=standard --json
composer install
```

Or, from a Foundry-enabled parent directory:

```bash
foundry new website --starter=standard --json
cd website
composer install
```

This means the scaffold must not leave the project in a state where the next Composer command immediately conflicts with an out-of-date lock file or a wrong package name.

### 4. `app/platform/...` is baked into scaffold, runtime, generators, diagnostics, docs, and tests

This layout needs to be replaced across the framework project with normal top-level paths.

## Desired framework behavior

### New app bootstrap

#### `foundry new`

Running `foundry new` inside the current directory should:

- scaffold the app into `.` without requiring a path argument
- generate the starter files
- compile graph
- generate starter docs / inspect UI if that is part of current scaffold behavior
- return JSON/human output that points to the actual next steps

#### `foundry new website`

Running `foundry new website` should:

- create `website/`
- scaffold the app there
- preserve the same starter behavior as `foundry new`

### Package name

Generated `composer.json` must require:

```json
"lofye/foundry-framework": "<version>"
```

The scaffold payload should also report `framework_package: lofye/foundry-framework`.

### Command style in app-facing docs

The framework should prefer `foundry ...` in app-facing docs and scaffolded README/AGENTS guidance, while still acknowledging that the literal executable remains the project-local Composer binary.

The framework repo should not keep teaching `php vendor/bin/foundry ...` as the main app-level command style if the intended experience is a project-local `foundry` command.

### Conventional app layout

Newly generated apps should use:

- `app/features/`
- `app/generated/`
- `app/.foundry/build/`
- `bootstrap/app.php`
- `bootstrap/providers.php`
- `config/app.php`
- `config/auth.php`
- `config/database.php`
- `config/cache.php`
- `config/queue.php`
- `config/storage.php`
- `config/ai.php`
- `database/migrations/`
- `lang/<locale>/...`
- `public/index.php`
- `storage/files/`
- `storage/logs/`
- `storage/tmp/`

## Concrete implementation requirements

### 1. Scaffold generator

Update the scaffold so that it:

- defaults `new` to `.` when no path argument is given
- writes the correct package name
- writes `public/index.php`
- writes top-level `bootstrap/`, `config/`, `database/`, `lang/`, and `storage/` directories
- updates generated `.gitignore` accordingly
- updates scaffolded README and AGENTS content accordingly
- updates next-step payload output accordingly

### 2. Runtime path assumptions

Remove `app/platform/...` assumptions from runtime and related helpers:

- config loading
- bootstrap loading
- SQLite default location
- storage root default
- trace log path
- migration path
- locale path
- app-local extension registration path
- doctor directory checks
- source scanning / compile cache grouping

These should all use the new top-level conventional layout.

### 3. Generators and verifiers

Update generators and verifiers so they emit and read from:

- `database/migrations/`
- `lang/`
- `storage/`
- `config/foundry/extensions.php` if an app-local extension registration file is still desired

### 4. Tests

Update the framework test suite to expect the new layout everywhere.

That includes:

- integration tests for `new`
- path helper tests
- source scanner tests
- runtime defaults tests
- config validation tests
- generator tests
- docs / API surface tests
- any tests that still hardcode `app/platform/...`

Because no compatibility layer is required, tests should be rewritten to the new layout rather than duplicated for old and new layouts.

### 5. Documentation inside the framework repo

Update framework docs so they no longer claim or imply:

- `app/platform/public/index.php`
- `app/platform/config/...`
- `app/platform/storage/...`
- `app/platform/lang/...`
- `app/platform/migrations/...`
- `lofye/foundry`

The framework README should clearly present the intended bootstrap flow from scratch.

## Recommended command UX

### Preferred scratch flow

```bash
mkdir website
cd website
composer require lofye/foundry-framework
foundry new --starter=standard --json
composer install
foundry compile graph --json
foundry inspect graph --json
foundry inspect pipeline --json
foundry doctor --json
foundry verify graph --json
foundry verify pipeline --json
foundry verify contracts --json
php -S 127.0.0.1:8000 public/index.php
```

### Preferred subdirectory flow

```bash
composer require lofye/foundry-framework
foundry new website --starter=standard --json
cd website
composer install
```

If the framework wants to support a helperless path too, it should be explicit and consistent, but the project-local `foundry` UX is the intended one.

## Acceptance criteria

This work is complete when:

- `foundry new` scaffolds into the current directory without requiring a path argument
- `foundry new website` scaffolds into `website/`
- generated apps require `lofye/foundry-framework`, not `lofye/foundry`
- scaffolded apps use `public/index.php`
- scaffolded apps use top-level `bootstrap/`, `config/`, `database/`, `lang/`, and `storage/`
- runtime, generators, diagnostics, and tests no longer depend on `app/platform/...`
- framework docs no longer teach the old layout
- the framework test suite passes on the new layout
- >= 90% test coverage is achieved

==========================================================================================

That will work technically, but I would not do a blind “everything not ignored” commit here.

From the current repo state, I’d separate the changes like this:

Include:
- the `framework` submodule pointer to `v0.5.1`
- the authored site updates like [public/index.html](/Users/lo_fye/code/foundry-website/public/index.html), [public/build.html](/Users/lo_fye/code/foundry-website/public/build.html), and [content/docs/authored/getting-started.md](/Users/lo_fye/code/foundry-website/content/docs/authored/getting-started.md)
- the docs pipeline/source updates like [content/docs/navigation.json](/Users/lo_fye/code/foundry-website/content/docs/navigation.json), [scripts/lib/DocsPipeline.php](/Users/lo_fye/code/foundry-website/scripts/lib/DocsPipeline.php), and [README.md](/Users/lo_fye/code/foundry-website/README.md)
- the regenerated current docs and the new `v0.5.1` snapshot files under `content/docs/generated/versions/v0.5.1/`, `public/docs/generated/versions/v0.5.1/`, and [public/docs/versions/v0.5.1](/Users/lo_fye/code/foundry-website/public/docs/versions/v0.5.1)

Probably leave out:
- [FRAMEWORK-BOOTSTRAP-AND-LAYOUT-SPEC.md](/Users/lo_fye/code/foundry-website/FRAMEWORK-BOOTSTRAP-AND-LAYOUT-SPEC.md)
- the modified older snapshot pages:
  - [public/docs/versions/v0.4.0/getting-started/index.html](/Users/lo_fye/code/foundry-website/public/docs/versions/v0.4.0/getting-started/index.html)
  - [public/docs/versions/v0.4.1/getting-started/index.html](/Users/lo_fye/code/foundry-website/public/docs/versions/v0.4.1/getting-started/index.html)
  - [public/docs/versions/v0.4.2/getting-started/index.html](/Users/lo_fye/code/foundry-website/public/docs/versions/v0.4.2/getting-started/index.html)
  - [public/docs/versions/v0.5.0/getting-started/index.html](/Users/lo_fye/code/foundry-website/public/docs/versions/v0.5.0/getting-started/index.html)

Reason:
- the spec file was just an internal handoff artifact
- the older versioned pages are supposed to behave like historical snapshots, and right now they were only touched because earlier site edits changed command wording there too

If you want the safest commit, I’d do this:

```bash
cd /Users/lo_fye/code/foundry-website

git restore --staged FRAMEWORK-BOOTSTRAP-AND-LAYOUT-SPEC.md
git restore FRAMEWORK-BOOTSTRAP-AND-LAYOUT-SPEC.md

git restore public/docs/versions/v0.4.0/getting-started/index.html
git restore public/docs/versions/v0.4.1/getting-started/index.html
git restore public/docs/versions/v0.4.2/getting-started/index.html
git restore public/docs/versions/v0.5.0/getting-started/index.html

git add -A
git status --short
```

Then if the status looks right, commit.

If your intention really is “capture absolutely everything currently changed in this repo,” you can do that too, and the detached `HEAD` in the submodule still is not a problem. I just wouldn’t recommend including the spec file and the rewritten old snapshots unless you want them on purpose.
