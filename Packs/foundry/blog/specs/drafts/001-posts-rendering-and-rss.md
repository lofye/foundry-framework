# Execution Spec: 001-posts-rendering-and-rss

## Purpose

Build the first production-capable Foundry pack: `foundry/blog`.

The blog pack provides markdown-authored posts, deterministic HTML rendering, public blog routes, RSS output, pack metadata, command surfaces, generator integration, explain integration, and marketplace-safe installation behavior.

This pack is a reference implementation for future packs, but it is not special at runtime. It MUST use the same `vendor/package` identity, install, marketplace, explain, generate, activation, and collision code paths as every other pack.

---

## Pack

`foundry/blog`

---

## Goals

1. Provide a usable blog system with markdown-based post authoring.
2. Exercise the pack architecture through schemas, commands, workflows, routes, rendering, generation, explain output, tests, and marketplace metadata.
3. Demonstrate marketplace-safe namespace ownership without giving Foundry-owned packs privileged runtime behavior.
4. Provide deterministic public rendering and RSS output.
5. Keep implementation simple, inspectable, low-configuration, and friendly to non-technical authors.
6. Establish a pack-local structure that future installed packs can copy.

---

## Non-Goals

- Do not make `foundry/blog` a framework-internal module.
- Do not add special-case install, marketplace, explain, generate, or collision behavior for Foundry-owned packs.
- Do not require authors to write HTML to publish posts.
- Do not implement comments, tags, categories, search, multi-author workflows, moderation queues, or themes beyond the minimal override contract.
- Do not introduce runtime network calls.
- Do not add a framework-level Composer dependency without explicit approval.
- Do not implement pack publishing infrastructure in this spec.
- Do not bypass existing extension-system pack validation and activation rules.

---

## Pack Layout

The pack root MUST be:

```text
Packs/foundry/blog/
```

Required structure after implementation:

```text
Packs/
  foundry/
    blog/
      foundry.json
      src/
      tests/
      docs/
        blog.md
        blog.decisions.md
      specs/
        drafts/
          001-posts-rendering-and-rss.md
      plans/
```

When this draft is promoted for execution, the active spec path MUST become:

```text
Packs/foundry/blog/specs/001-posts-rendering-and-rss.md
```

The implementation MAY add additional pack-owned directories such as `resources/`, `public/`, `templates/`, or `assets/` when they are referenced by `foundry.json`, docs, tests, or pack runtime code.

---

## Pack Identity

The pack manifest MUST live at:

```text
Packs/foundry/blog/foundry.json
```

Canonical manifest fields:

```json
{
  "name": "foundry/blog",
  "version": "1.0.0",
  "description": "Markdown blog publishing pack for Foundry.",
  "entry": "Foundry\\Blog\\BlogServiceProvider",
  "capabilities": [
    "blog.posts",
    "blog.rendering",
    "blog.rss",
    "generate.blog"
  ],
  "checksum": "<sha256>",
  "signature": null
}
```

Derived identifiers:

| Purpose | Value |
| --- | --- |
| Pack ID | `foundry/blog` |
| PHP namespace | `Foundry\Blog` |
| Runtime slug | `foundry-blog` |
| Extension name | `pack.foundry.blog` |
| Install root | `Packs/foundry/blog` |

Rules:

- The manifest `name` MUST match the `Packs/foundry/blog` path.
- The pack MUST use the same `vendor/package` validation rules as every other pack.
- Marketplace installation MUST reject namespace collisions deterministically.
- Foundry-owned namespace ownership is the only reserved behavior; runtime activation MUST remain identical to third-party packs.

---

## Pack Service Provider

The pack MUST provide:

```php
Foundry\Blog\BlogServiceProvider
```

`BlogServiceProvider` MUST implement `Foundry\Packs\PackServiceProvider`.

Registration MUST go through `PackContext` and MUST declare at least:

- commands:
  - `blog:post:create`
  - `blog:post:list`
  - `blog:post:publish`
  - `blog:post:delete`
- schemas:
  - `blog.post`
- workflows:
  - `blog.post.publish`
- generators:
  - `generate blog`
- docs metadata:
  - `blog`

If the implementation needs route registration, rendering assets, or executable workflow behavior beyond the current `PackContext` metadata contract, it MUST add the minimal extension-system integration needed through the same generic pack code path rather than hard-coding `foundry/blog`.

---

## Content Model

The pack MUST define a `Post` model with these fields:

| Field | Type | Required | Notes |
| --- | --- | --- | --- |
| `id` | string | yes | Deterministic stable identifier. |
| `title` | string | yes | Human-readable post title. |
| `slug` | string | yes | Unique public URL slug. |
| `markdown` | string | yes | Raw authoring source. |
| `html` | string | yes | Deterministically rendered HTML. |
| `excerpt` | string | no | Deterministic summary derived from explicit input or markdown. |
| `status` | string | yes | Either `draft` or `published`. |
| `published_at` | string or null | no | ISO-8601 timestamp when published. |
| `created_at` | string | yes | ISO-8601 timestamp. |
| `updated_at` | string | yes | ISO-8601 timestamp. |

Allowed `status` values:

- `draft`
- `published`

Slug rules:

- Slugs MUST be lowercase URL-safe strings.
- Slugs MUST be unique across posts.
- If a slug is not supplied, it MUST be derived deterministically from `title`.
- Slug collisions MUST fail deterministically unless the command explicitly receives a unique slug.

Storage rules:

- Raw markdown MUST be persisted.
- Rendered HTML MUST be persisted.
- Rendering the same markdown with the same renderer configuration MUST produce the same HTML.
- Draft posts MUST never be returned by public routes or RSS output.

---

## Markdown Rendering

Authoring format MUST be Markdown.

Initial markdown support MUST include:

- headings
- paragraphs
- emphasis
- links
- unordered lists
- ordered lists
- blockquotes
- fenced code blocks
- images

Renderer rules:

- Prefer an existing framework-standard deterministic markdown renderer when available.
- `league/commonmark` MAY be used only if dependency policy is explicitly approved and the resulting configuration is deterministic.
- If no approved dependency is available, implement or reuse a minimal deterministic pack-local renderer that covers the required markdown subset.
- Renderer configuration MUST be explicit and inspectable.
- Rendered HTML MUST be escaped safely and MUST not vary by environment.

---

## Commands

The pack MUST expose these commands through the normal pack command path:

```bash
foundry blog:post:create
foundry blog:post:list
foundry blog:post:publish <id>
foundry blog:post:delete <id>
```

Optional future commands are out of scope:

```bash
foundry blog:post:update <id>
foundry blog:post:preview <id>
foundry blog:theme:list
foundry blog:theme:set <theme>
```

### `blog:post:create`

The create command MUST support:

- interactive mode
- non-interactive deterministic mode
- stdin markdown input
- file-based markdown import

Required examples:

```bash
foundry blog:post:create --title="Hello World" --markdown="# Hello"
foundry blog:post:create --title="Hello World" --file=post.md
cat post.md | foundry blog:post:create --title="Hello World" --stdin
```

Output MUST include:

- `id`
- `title`
- `slug`
- `status`
- `created_at`
- `updated_at`
- post storage path or record reference

### `blog:post:list`

The list command MUST:

- list posts in deterministic order
- support filtering by status
- hide implementation-specific storage details unless `--json` includes explicit machine-readable fields

Default ordering MUST be:

1. published posts newest-first by `published_at`
2. drafts newest-first by `updated_at`
3. stable tie-break by `id`

### `blog:post:publish <id>`

The publish command MUST run the publish workflow and fail when:

- the post does not exist
- the slug is missing or invalid
- the slug collides with another post
- markdown cannot render deterministically

### `blog:post:delete <id>`

The delete command MUST delete or tombstone a post deterministically.

The implementation MUST choose and document one behavior:

- hard delete the post record, or
- tombstone the post and exclude it from all public output.

The chosen behavior MUST be covered by tests and reflected in `docs/blog.md`.

---

## Publish Workflow

The workflow id MUST be:

```text
blog.post.publish
```

Workflow steps:

1. Load post by id.
2. Validate post schema.
3. Validate slug format.
4. Validate slug uniqueness.
5. Render markdown to HTML.
6. Persist rendered HTML.
7. Set `status` to `published`.
8. Set `published_at` when publishing for the first time.
9. Update `updated_at`.
10. Invalidate relevant blog caches if caching exists.
11. Regenerate cached RSS output if RSS caching exists.

Timestamp rules:

- Timestamps MUST be explicit ISO-8601 strings.
- Tests MUST freeze or inject time rather than relying on wall-clock nondeterminism.
- Re-publishing an already published post MUST NOT silently change `published_at` unless the command explicitly documents that behavior.

---

## Public Routes

The pack MUST expose these public routes:

```text
GET /blog
GET /blog/{slug}
GET /blog/rss.xml
```

Route registration MUST happen through generic pack/extension integration, not a `foundry/blog` special case.

### `GET /blog`

Behavior:

- renders a paginated list of published posts
- excludes drafts
- sorts newest-first by `published_at`
- uses deterministic tie-breaks
- returns a deterministic empty state when no posts are published

### `GET /blog/{slug}`

Behavior:

- renders exactly one published post by slug
- excludes drafts
- returns a deterministic 404 response for unknown, draft, or deleted posts

### `GET /blog/rss.xml`

Behavior:

- returns valid RSS XML
- includes only published posts
- sorts newest-first by `published_at`
- uses deterministic tie-breaks

---

## Rendering Layer

The initial rendering layer MUST prioritize:

- minimalism
- deterministic output
- low complexity
- application override support
- no required author-written HTML

The pack MUST include:

- default layout template
- default blog index template
- default post template
- minimal default stylesheet

Template rules:

- Use the framework's canonical rendering mechanism if one exists.
- If no canonical rendering mechanism exists, use lightweight PHP templates or a minimal pack-local renderer.
- Do not introduce a heavy templating dependency unless it is already standardized by the framework.
- Templates MUST receive assembled post/view data and MUST NOT reach into raw storage, registry, compiler, or request internals.

Applications MUST be able to override:

- layout template
- blog index template
- post template
- stylesheet

Override behavior MUST be deterministic and documented in `docs/blog.md`.

---

## RSS Feed

RSS route:

```text
GET /blog/rss.xml
```

Each item MUST include:

- `title`
- `link`
- `description`
- `pubDate`
- `guid`

Feed rules:

- only published posts are included
- posts are sorted newest-first by `published_at`
- ties are resolved by stable `id`
- XML output MUST be valid
- XML escaping MUST be deterministic and safe
- feed metadata MUST be configurable through explicit pack or app configuration, not hidden environment state

---

## Generate Integration

The user-facing intent:

```bash
foundry generate "add blog"
```

MUST route through the normal generate and pack requirement system.

When `foundry/blog` is missing, generate MAY recommend or install the pack only through the same pack installation code path used for every other pack.

Generate behavior MUST:

1. Resolve the `foundry/blog` pack requirement.
2. Install or require the pack through the pack registry when needed.
3. Register the pack provider through `PackContext`.
4. Configure routes, schemas, commands, workflows, and rendering assets through generic extension outputs.
5. Publish default templates or assets only when required and only through deterministic writes.
6. Report pack actions in explain and plan output.

Identical generate requests against identical repository state MUST produce identical structures, diagnostics, and JSON output.

---

## Explain Integration

The pack MUST be explainable through the canonical pack explain target:

```bash
foundry explain pack:foundry/blog --json
```

Explain output MUST include:

- pack identity
- version
- install path
- service provider
- schemas
- commands
- workflows
- routes
- rendering assets
- templates
- public endpoints
- generator ids
- dependencies
- capabilities
- diagnostics, when present

Explain output MUST be deterministic and MUST be derived from the same registry, graph, and pack metadata used by install, generate, inspect, and marketplace surfaces.

---

## Marketplace Integration

The blog pack MUST be installable through the marketplace system as `foundry/blog`.

Marketplace behavior MUST:

- treat `foundry/blog` as an ordinary `vendor/package` pack identity
- expose pack metadata
- expose version information
- expose compatibility information
- reject namespace collisions deterministically
- use the same install and activation path as local packs
- preserve local pack context under `Packs/foundry/blog/`

The marketplace MUST NOT:

- use privileged logic because the vendor is `foundry`
- bypass manifest, checksum, signature-shape, provider, or collision validation
- execute remote code during search or metadata resolution

---

## Determinism Requirements

The following MUST be deterministic:

- manifest normalization
- install output
- pack activation
- route registration
- schema registration
- command registration
- workflow registration
- generated structure
- markdown-to-HTML output
- public page output for identical content and configuration
- RSS output ordering
- explain output
- collision diagnostics
- tests with frozen time

No output may depend on filesystem iteration order, ambient wall-clock time, randomness, network state, or hidden global state.

---

## Documentation Requirements

The implementation MUST create:

```text
Packs/foundry/blog/docs/blog.md
Packs/foundry/blog/docs/blog.decisions.md
```

`docs/blog.md` MUST describe:

- current pack behavior
- commands
- content model
- storage behavior
- route behavior
- rendering override contract
- RSS behavior
- generate and explain integration
- known limitations

`docs/blog.decisions.md` MUST be append-only and record implementation decisions including:

- storage model
- markdown renderer choice
- delete versus tombstone behavior
- rendering override mechanism
- RSS caching choice, if any
- framework integration gaps discovered during implementation

---

## Implementation Requirements

Implementation MUST stay inside the pack boundary unless generic framework support is missing.

Pack-owned files belong under:

```text
Packs/foundry/blog/
```

Framework-owned glue MAY be added under `src/*` only when it is generic extension-system support needed by any pack.

Implementation tasks:

1. Create `foundry.json` with deterministic manifest fields.
2. Implement `Foundry\Blog\BlogServiceProvider`.
3. Register pack metadata through `PackContext`.
4. Implement post storage and schema validation.
5. Implement deterministic markdown rendering.
6. Implement post commands.
7. Implement publish workflow behavior.
8. Implement public blog and RSS routes through generic pack route registration.
9. Implement default templates and stylesheet with override support.
10. Implement generate integration for `add blog`.
11. Implement explain and inspect metadata for all declared pack surfaces.
12. Implement marketplace install metadata and collision coverage.
13. Create `docs/blog.md` and `docs/blog.decisions.md`.
14. Add pack-owned tests under `Packs/foundry/blog/tests/`.
15. Add framework tests only for generic pack infrastructure introduced by this implementation.

---

## Tests Required

Pack tests MUST cover:

1. Creating a post non-interactively.
2. Creating a post from stdin.
3. Creating a post from a markdown file.
4. Listing posts in deterministic order.
5. Publishing a post.
6. Deleting or tombstoning a post according to the documented behavior.
7. Slug generation and slug collision failures.
8. Markdown rendering for headings, paragraphs, emphasis, links, lists, blockquotes, fenced code blocks, and images.
9. Persisted HTML matching rendered HTML.
10. `/blog` rendering published posts only.
11. `/blog/{slug}` rendering published posts only.
12. Draft posts returning deterministic 404 responses publicly.
13. `/blog/rss.xml` returning valid XML.
14. RSS including published posts only.
15. RSS deterministic ordering.
16. Template and stylesheet override behavior.
17. `foundry explain pack:foundry/blog --json` output correctness.
18. Explain output determinism.
19. Marketplace installation success.
20. Duplicate namespace collision rejection.
21. Pack registration determinism.
22. Generate integration for `foundry generate "add blog"`.

Tests MUST assert observable behavior and deterministic outputs. Do not add tautological existence-only tests.

---

## Verification Commands

Focused verification while iterating:

```bash
php bin/foundry verify context --feature=extension-system --json
php bin/foundry verify features --json
php vendor/bin/phpunit Packs/foundry/blog/tests
php vendor/bin/phpunit tests/Unit/LocalPackLoaderTest.php
php vendor/bin/phpunit tests/Unit/PackManagerTest.php
php vendor/bin/phpunit tests/Integration/CLIPackCommandsTest.php
php bin/foundry verify extensions --json
```

Completion quality gate:

```bash
php vendor/bin/phpunit
XDEBUG_MODE=coverage php vendor/bin/phpunit --coverage-clover build/coverage/clover.xml
php bin/foundry verify coverage --min=90 --clover=build/coverage/clover.xml --json
```

---

## Acceptance Criteria

- `foundry/blog` installs cleanly as an ordinary pack.
- The pack root lives at `Packs/foundry/blog/`.
- Pack identity, manifest, service provider, and extension name are deterministic.
- Post commands work and expose deterministic JSON output.
- Markdown authoring works without requiring author-written HTML.
- Markdown renders to deterministic persisted HTML.
- Posts can be published through the publish workflow.
- Draft posts are excluded from public routes and RSS.
- Public `/blog`, `/blog/{slug}`, and `/blog/rss.xml` routes render correctly.
- RSS output is valid, escaped, and deterministic.
- Default templates and stylesheet exist and can be overridden by applications.
- Generate integration can satisfy `foundry generate "add blog"` through the normal pack code path.
- Explain output fully describes the pack.
- Marketplace installation works through the normal pack code path.
- Namespace collision detection works.
- `foundry/blog` receives no privileged runtime behavior.
- Pack docs, decisions, specs, source, tests, and plans are locally inspectable.
- Focused tests and the full quality gate pass.

---

## Done Means

Foundry has a real end-to-end blog pack that proves installable packs can carry production behavior, local LLM-readable context, deterministic rendering, markdown publishing, RSS, generate integration, explain integration, marketplace-safe identity, and collision handling without treating Foundry-owned packs as special framework internals.
