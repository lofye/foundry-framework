# Execution Spec: 006-add-tag-triggered-github-actions-deployment-to-cloudways

## Historical Import Note

This spec was imported from the explicitly marked pre-canonical archive.

- Legacy name: `6 - Add tag-triggered GitHub Actions deployment to Cloudways`
- Legacy id: `6`
- Canonical pre-canonical id: `006`
- Imported module: `PreCanonical`
- Source archive: `_import/pre-canonical-specs.md`

## Original Pre-Canonical Spec

# Spec: Add tag-triggered GitHub Actions deployment to Cloudways

## Goal

Keep the existing local `composer deploy` release process as the single human-facing command, but move the actual production deployment to GitHub Actions so that:

* Derek still prepares/releases locally
* GitHub deploys the exact pushed tag to Cloudways
* Cloudways post-deploy commands run automatically
* Cloudways cache is purged automatically after a successful deploy

This fits GitHub Actions well because workflows can trigger on pushed tags, and the runner can then SSH into Cloudways and execute deployment steps remotely. GitHub also exposes the pushed ref/tag to the workflow via `GITHUB_REF`. ([GitHub Docs][1])

## Non-goals

* Do not replace the existing local release script.
* Do not move doc generation, tag naming policy, or release prep logic into GitHub as the primary source of truth.
* Do not require Derek’s machine to directly deploy to production.
* Do not implement full zero-downtime symlink releases in the first pass, though Cloudways does document that as a more advanced future direction. ([Cloudways][2])

## Desired workflow

### Current local flow remains

The existing local `composer deploy` script continues to:

* ensure tests are passing
* ensure branch is `main`
* refuse tags containing `-local`, `-test`, or `-dev`
* regenerate documentation
* remove test-generated docs / stale generated docs as needed
* tag the release locally
* pin the framework submodule to that tagged version
* push commits and tags to GitHub

### New automated flow

When a valid release tag is pushed to GitHub:

1. GitHub Actions triggers on the tag push. GitHub supports `push` filters for tags. ([GitHub Docs][1])
2. The workflow checks out the exact tagged revision.
3. The workflow re-validates critical deploy guardrails.
4. The workflow connects to Cloudways over SSH from the GitHub-hosted runner.
5. The workflow deploys the tagged code to the Cloudways app.
6. The workflow runs post-deploy commands on the Cloudways server.
7. The workflow purges Cloudways cache.
8. The workflow fails loudly if any step fails.

## Design decision

Treat the local script as the **release authoring tool** and GitHub Actions as the **production deploy executor**.

That means:

* local script decides what is releasable
* GitHub Actions decides what is deployable
* production only changes through the GitHub workflow

## Trigger

Use a dedicated workflow file, for example:

* `.github/workflows/deploy-cloudways.yml`

Trigger on pushed version tags, for example:

* `v*`

Also support optional manual execution with `workflow_dispatch` for emergencies or redeploys. GitHub supports manual workflow runs through `workflow_dispatch`. ([GitHub Docs][3])

## Required GitHub secrets

Add these repository secrets:

* `CLOUDWAYS_HOST`
* `CLOUDWAYS_PORT` (default `22` if omitted)
* `CLOUDWAYS_USER`
* `CLOUDWAYS_SSH_KEY`
* `CLOUDWAYS_APP_PATH`
* `CLOUDWAYS_API_EMAIL`
* `CLOUDWAYS_API_KEY`
* `CLOUDWAYS_SERVER_ID`

## Workflow requirements

### 1. Trigger rules

Workflow must run on:

* push of tags matching release pattern, such as `v*`
* optional manual dispatch

Workflow must not run on:

* normal branch pushes
* pull requests

### 2. Guardrails duplicated in CI

Even though the local script already enforces release policy, the workflow must repeat the important safety checks so production is protected even if the local process is bypassed.

The workflow must fail if:

* the pushed ref is not a tag
* the tag name contains `-local`, `-test`, or `-dev`
* the tagged commit is not reachable from `main` (recommended)
* required secrets are missing

Use the pushed tag ref from GitHub Actions context rather than inferring it. GitHub documents `GITHUB_REF` as the full branch or tag ref that triggered the workflow. ([GitHub Docs][4])

### 3. Checkout behavior

The workflow must check out the exact tag/commit that triggered it, not merely the current tip of `main`.

It must also fetch enough Git history to validate whether the tagged commit belongs to `main`.

### 4. Deployment method

Use plain SSH + `rsync` from the GitHub runner to the Cloudways host.

Reason:

* simplest to understand
* easy to debug
* does not depend on undocumented Cloudways deployment behavior
* good first pass before considering atomic/symlink releases

### 5. File sync rules

The deploy step must upload the application code to `CLOUDWAYS_APP_PATH`.

It must exclude at least:

* `.git/`
* `.github/`
* `.env`
* `node_modules/`
* any other purely local/dev artifacts
* any directories that are persistent runtime data and should not be overwritten

Exact excludes should be configurable near the top of the workflow.

### 6. Remote post-deploy commands

After upload, the workflow must SSH into Cloudways and run remote commands in the app directory.

These commands should be stack-specific and configurable.

Initial implementation should support a generic remote command block, with placeholders for app-specific commands such as:

* `composer install --no-dev --prefer-dist --optimize-autoloader`
* any framework-specific cache clear/build commands
* any migration commands if appropriate

Do not hardcode risky commands without an explicit section for project-specific customization.

### 7. Cache purge

After a successful remote deploy, purge Cloudways cache.

Supported first-pass implementation:

* purge Cloudways Varnish through the Cloudways API

Cloudways publicly documents Varnish purging through its API and also documents Varnish-related cache behavior in support content. ([Cloudways][5])

Important:

* do not rely on scraping the Cloudways UI
* do not assume the newer UI “Purge Site Cache” button has a stable public API unless separately verified
* first pass should use the documented Varnish purge path

### 8. Concurrency

Prevent overlapping production deploys.

Use GitHub Actions concurrency so a second deploy does not collide with a first one. GitHub workflow syntax supports concurrency controls. ([GitHub Docs][6])

### 9. Failure behavior

Any failing step must stop the workflow.

Deployment is considered successful only if all of the following succeed:

* validation
* checkout
* file sync
* remote commands
* cache purge

### 10. Observability

Workflow logs should make these stages obvious:

* validate tag
* checkout release
* sync files
* run remote commands
* purge cache
* done

Avoid noisy output unless needed for troubleshooting.

## Recommended implementation structure

Use one workflow with one deploy job.

Suggested step order:

1. checkout repository
2. fetch full history/tags as needed
3. validate tag name
4. validate tagged commit is on `main`
5. start SSH agent with Cloudways key
6. add Cloudways host to `known_hosts`
7. `rsync` files to Cloudways
8. run remote deploy commands over SSH
9. authenticate to Cloudways API
10. purge Varnish
11. finish

## Open configuration points Codex should leave clearly marked

Codex should make these easy to edit:

* allowed tag pattern
* excluded `rsync` paths
* remote app path
* remote post-deploy commands
* whether migrations run automatically
* whether asset build happens locally in CI or remotely on Cloudways
* whether manual dispatch is enabled

## Acceptance criteria

This feature is complete when:

* Derek can still run the same local release command as before
* pushing a valid production tag automatically starts a GitHub Actions deploy
* the workflow deploys the exact tag that was pushed
* invalid tags like `*-local*`, `*-test*`, `*-dev*` are rejected by the workflow
* the workflow uploads the code to Cloudways over SSH
* the workflow runs configured remote commands successfully
* the workflow purges Cloudways Varnish successfully
* the workflow prevents overlapping deploys
* failed deploys are clearly visible in GitHub Actions

## Short version for Codex

Implement a new GitHub Actions workflow that triggers on pushed production tags, validates the tag and branch policy, checks out the exact tag, deploys the code to Cloudways over SSH/rsync, runs configurable remote post-deploy commands, and then purges Cloudways Varnish via the Cloudways API. Keep the existing local `composer deploy` script as the release-prep entry point and duplicate critical safety checks in CI so production deploys cannot occur from invalid tags or non-main commits. GitHub supports tag-triggered workflows and manual workflow dispatch; Cloudways documents Varnish purge automation and a more advanced future path using zero-downtime deployments. ([GitHub Docs][1])

[1]: https://docs.github.com/actions/using-workflows/events-that-trigger-workflows?utm_source=chatgpt.com "Events that trigger workflows"
[2]: https://www.cloudways.com/blog/zero-downtime-deployments/?utm_source=chatgpt.com "Implementing Zero Downtime Deployments on Cloudways"
[3]: https://docs.github.com/actions/managing-workflow-runs/manually-running-a-workflow?utm_source=chatgpt.com "Manually running a workflow"
[4]: https://docs.github.com/en/actions/reference/workflows-and-actions/variables?utm_source=chatgpt.com "Variables reference"
[5]: https://www.cloudways.com/blog/automatically-purging-varnish-cache-using-cloudways-api/?utm_source=chatgpt.com "How to Purge Varnish Cache (3 Different Methods)"
[6]: https://docs.github.com/actions/using-workflows/workflow-syntax-for-github-actions?utm_source=chatgpt.com "Workflow syntax for GitHub Actions"


Here’s where each value comes from in Cloudways.

CLOUDWAYS_HOST: your server’s public IP. In Cloudways Flexible, go to Servers → your server → Master Credentials; Cloudways says the public IP is shown there with the credentials. Source
CLOUDWAYS_PORT: usually just 22. SSH on Cloudways uses port 22 unless you’ve changed something. Source
CLOUDWAYS_USER: either the Master username or an Application username. I’d prefer an application-specific user if possible. Application credentials are under Applications → your app → Access Details / Application Credentials. Source
CLOUDWAYS_SSH_KEY: not something Cloudways gives you. You generate an SSH keypair yourself, add the public key in Cloudways, and store the private key in GitHub as the secret. Cloudways documents adding the public key under Servers → your server → SSH Public Keys, or app-level SSH keys if using application credentials. Source
CLOUDWAYS_APP_PATH: Cloudways shows the application folder name in Applications → your app → Application Settings / Application Information. From Cloudways’ docs, the app lives under an applications directory with public_html beneath it, so for this workflow the path should usually be the app root, not just public_html. A typical pattern is /home/master/applications/<app-folder>, but verify by SSH on your server before saving it. Sources, Cloudways example paths
CLOUDWAYS_API_EMAIL: your Cloudways account email address. Source
CLOUDWAYS_API_KEY: generate this in Cloudways under the bottom-left menu → API Integration → Generate Key. Source
CLOUDWAYS_SERVER_ID: Cloudways’ numeric server ID. The most reliable way to get it is from Cloudways API/Playground after you generate the API key, since the API expects server_id as an integer. API docs, API setup
CLOUDWAYS_APP_ID: optional in this workflow. Same idea as SERVER_ID: get the numeric app ID from the API/Playground if your server hosts multiple apps. API docs
Two important practical notes:

CLOUDWAYS_SSH_KEY in GitHub must be the private key text.
Cloudways only stores the matching public key.

GitHub-hosted runners SSH in from changing IP addresses.
Cloudways’ SSH docs say shell access depends on IP whitelisting under server security settings. That may become the real deployment blocker, separate from secrets. Source
