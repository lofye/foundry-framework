# Implementation Plan: 004-roadmap-phase-4-documentation-system-and-website-integration-wr

## Historical Provenance

- Imported spec path: `Modules/PreCanonical/specs/004-roadmap-phase-4-documentation-system-and-website-integration-wr.md`
- Source archive: `_import/pre-canonical-specs.md`
- Legacy name: `4 - Roadmap Phase 4 - Documentation System and Website Integration (WR)`
- Legacy id: `4`
- Canonical pre-canonical id: `004`

## Historical Specification Summary

The original pre-canonical specification body is preserved in the imported execution spec. This reconstruction note records adjacent marked context and result evidence without inferring modern module ownership.

## Historical Preamble Context

### Preamble Block 1

- Name: `4 - Roadmap Phase 4 - Documentation System and Website Integration (WR)`

Foundry Phase 4 Master Spec

Documentation System and Website Integration

The website repo is separate from the framework repo.

This website repo now includes the Foundry framework repo as a git submodule mounted at:

framework/

The submodule is the canonical source that the website must inspect in order to generate accurate framework documentation.

Important rules for this phase:

• Do not duplicate the framework source into the website repo.
• Treat framework/ as a pinned, versioned source of truth.
• All generated documentation must be derived from the framework submodule at framework/.
• The website must be able to explain the framework to humans while also exposing structured reference material that AI tools can use.
• Prefer dual-layer documentation:
  - authored conceptual docs for humans
  - generated reference docs from framework metadata for humans and LLMs
• The documentation build system must be deterministic.
• The generated docs must correspond to the exact checked-out framework submodule commit or tag.
• The site should be designed so that updating the framework submodule and rerunning the docs build will regenerate the docs for that framework version.

The website architecture for this phase should assume three content sources:

1. authored website content
   - homepage narrative
   - how-foundry-works narrative
   - contribution and philosophy content

2. generated framework reference content
   - CLI commands
   - graph/compiler concepts
   - pipeline stages
   - diagnostics
   - extension APIs
   - version metadata
   - other structured framework reference material

3. rendered site pages
   - final HTML pages built from authored + generated content

Recommended structure:

- framework/                       ← git submodule checkout of Foundry
- content/docs/authored/           ← hand-authored docs for humans
- content/docs/generated/          ← generated docs from the framework submodule
- scripts/                         ← build/generation scripts
- templates/                       ← site templates/layouts
- public/ or build/                ← final rendered website

The documentation generator must read from framework/ rather than assuming documentation is hand-maintained.

The docs build system should extract or derive information from the framework submodule such as:

• framework version
• CLI command metadata
• compiler/graph concepts
• pipeline/guard/interceptor definitions
• extension and pack definitions
• diagnostics catalogs
• machine-readable reference files if present
• architecture-relevant source metadata where appropriate

If the framework repo already contains machine-readable exports or generators, reuse them.
If not, implement website-side extraction in a disciplined, deterministic way.

The homepage and how-foundry-works page should remain authored narrative pages, but they should reflect the current architecture of the framework as implemented through Phases 0A–3.

The docs area should explain:

• the original goals of Foundry
• what was added in Phases 0A, 0B, and 0C
• what was added in Phase 0D and Phases 1, 2, and 3
• how the compiler, graph, execution pipeline, extensions, packs, migrations, doctor tooling, visualization, prompt loop, and higher-level framework features all fit together

The docs should be understandable to humans.
LLMs should primarily rely on the code, graph structures, machine-readable exports, and generated reference docs.

Do not assume that “the code alone is enough” for all use cases.
Foundry should expose structured explanations and structured reference material so that both humans and AI tools can understand the system more reliably.

Whenever a new framework version is adopted in the website repo by updating the framework/ submodule, the docs build process should be able to regenerate documentation for that exact version.

In short:
The website is not just marketing.
It is a documentation and explanation layer built on top of a pinned checkout of the actual framework.


Foundry Phases 0A through 3 have implemented the framework’s core architecture, including the semantic compiler, canonical application graph, extension system, architecture diagnostics, visualization tools, AI development loop, execution pipeline, guards and interceptors, and the higher-level framework capabilities.

Phase 4 focuses on making the system understandable and usable by developers by implementing:

• a redesigned homepage
• a complete documentation system
• automated documentation generation from the framework codebase
• documentation updates tied to framework releases
• a clear architectural narrative explaining how Foundry works

The documentation must serve two audiences:

Human developers who need conceptual understanding.

AI tools that need structured information about the framework.

Documentation must therefore include both narrative explanations and structured reference material.

Phase 4 must not change core framework architecture but may add tooling necessary to expose and explain that architecture.

Test coverage across the repository must remain ≥ 90%.

⸻

Phase 4 Goals

Phase 4 introduces four major deliverables.

A redesigned marketing homepage.

A detailed architecture explanation page.

A full documentation section.

An automated documentation generation system tied to framework versions.

⸻

Website Structure

The website should have the following top-level sections:

Home
How Foundry Works
Documentation
Contributing
GitHub

Existing sections such as the conversation transcript and contribution guidelines should remain but may be repositioned.

⸻

Homepage Rewrite

The homepage should clearly communicate:

What Foundry is.

Why it exists.

How it differs from traditional frameworks.

How humans and LLMs collaborate when building software with Foundry.

The homepage should include the following sections.

Hero Section

Explain Foundry as:

A compiler-based, LLM-first web framework.

Example messaging concept:

Foundry turns web applications into structured systems that AI and humans can build together safely.

The hero section should include:

Framework tagline.

Brief description.

Quickstart commands.

GitHub and Packagist links.

⸻

Problem Section

Explain problems with current frameworks:

Large unstructured codebases.

LLM hallucinations.

Hidden runtime behavior.

Difficulty understanding architecture.

Explain how Foundry addresses these issues with:

Explicit contracts.

Compiler validation.

Application graphs.

Deterministic runtime pipelines.

⸻

Human vs LLM Roles

Explain what humans should focus on.

Humans excel at:

System design.

Domain modeling.

Product thinking.

Negotiating requirements.

Debugging unexpected behavior.

LLMs excel at:

Writing boilerplate.

Implementing feature logic.

Generating tests.

Maintaining consistency with framework conventions.

⸻

Framework Architecture

Explain Foundry’s architecture visually:

Source specifications.

Compiler.

Application graph.

Execution pipeline.

Runtime.

⸻

Conversation Section

Preserve the conversation transcript between Derek and the assistant.

This acts as a narrative origin story.

⸻

Contribution Section

Explain how contributors should submit PRs.

Focus on:

Prompts plus verification.

Tests.

Deterministic behavior.

⸻

How Foundry Works Page

Rewrite the architecture explanation page.

This page must explain:

What the compiler does.

What the application graph represents.

How the execution pipeline works.

How extensions integrate.

How guards and interceptors work.

How AI development loops operate.

How verification works.

The page should walk through the lifecycle of a request:

Source files.

Compiler.

Graph.

Pipeline.

Feature execution.

Response generation.

Include diagrams where useful.

⸻

Documentation Section

Add a new documentation area accessible at:

/docs

The documentation should be organized into the following categories.

Getting Started

Installing Foundry.

Creating an application.

Understanding the project structure.

Running compiler commands.

⸻

Core Concepts

Compiler architecture.

Application graph.

Features.

Schemas.

Routes.

Queries.

Events.

Jobs.

Caches.

⸻

Execution Model

Pipeline stages.

Guards.

Interceptors.

Execution plans.

Request lifecycle.

⸻

Framework Capabilities

Explain features introduced in phases:

Phase 0D

Phase 1

Phase 2

Phase 3

Each should have its own documentation.

⸻

CLI Reference

Document every CLI command.

Include:

inspect

verify

compile

doctor

graph

prompt

⸻

Extension Development

Explain how to create extensions.

Include:

packs

interceptors

guards

compiler passes

graph analyzers

⸻

AI Development

Explain the prompt workflow.

How Foundry extracts context.

How diagnostics guide the AI correction loop.

⸻

Architecture Reference

Provide a technical reference for:

compiler passes

graph node types

pipeline stages

execution plans

diagnostics

⸻

Auto-Generated Documentation

Documentation must update automatically when the framework version changes.

To implement this:

The website must include a local checkout of the Foundry repository.

Documentation should be generated from:

framework source files.

CLI command metadata.

graph node definitions.

schema definitions.

extension registrations.

Whenever a new version is released:

Docs should regenerate automatically.

⸻

Documentation Generation Tool

Implement a documentation generator that extracts:

CLI command descriptions.

Graph node types.

Compiler passes.

Pipeline stages.

Extension APIs.

Diagnostics.

This generator should output Markdown files consumed by the website.

## Historical Implementation Evidence

### Result Block 1

- Name: `4 - Roadmap Phase 4 - Documentation System and Website Integration (WR)`

RESULT

Implemented Phase 4 end-to-end in this repo: deterministic docs generation from the pinned `framework/` submodule, authored+generated merge, rendered site pages, version-aware outputs, machine-readable exports, CI automation, and automated tests.

**Key Changes**
- Added a full docs pipeline library: [DocsPipeline.php](/Users/lo_fye/code/foundryframework/scripts/lib/DocsPipeline.php)
- Implemented required scripts:
  - [build-docs.php](/Users/lo_fye/code/foundryframework/scripts/build-docs.php)
  - [extract-framework-version.php](/Users/lo_fye/code/foundryframework/scripts/extract-framework-version.php)
  - [extract-cli.php](/Users/lo_fye/code/foundryframework/scripts/extract-cli.php)
  - [extract-graph-metadata.php](/Users/lo_fye/code/foundryframework/scripts/extract-graph-metadata.php)
  - [extract-pipeline-metadata.php](/Users/lo_fye/code/foundryframework/scripts/extract-pipeline-metadata.php)
  - [extract-diagnostics.php](/Users/lo_fye/code/foundryframework/scripts/extract-diagnostics.php)
  - [extract-extensions.php](/Users/lo_fye/code/foundryframework/scripts/extract-extensions.php)
  - [render-pages.php](/Users/lo_fye/code/foundryframework/scripts/render-pages.php)
- Added authored docs/homepage content and docs category pages:
  - [content/docs/authored](/Users/lo_fye/code/foundryframework/content/docs/authored)
  - [content/docs/homepage](/Users/lo_fye/code/foundryframework/content/docs/homepage)
- Added generated reference outputs (Markdown + JSON), including versioned artifacts:
  - [content/docs/generated](/Users/lo_fye/code/foundryframework/content/docs/generated)
- Added rendered site outputs including `/docs` and versioned docs pages:
  - [public](/Users/lo_fye/code/foundryframework/public)
- Added templates and styling updates:
  - [templates/layout.html](/Users/lo_fye/code/foundryframework/templates/layout.html)
  - [public/assets/styles.css](/Users/lo_fye/code/foundryframework/public/assets/styles.css)
- Added build manifest output:
  - [docs-build/build-manifest.json](/Users/lo_fye/code/foundryframework/docs-build/build-manifest.json)
- Added CI workflow for continuous docs sync checks:
  - [docs-pipeline.yml](/Users/lo_fye/code/foundryframework/.github/workflows/docs-pipeline.yml)
- Added automated docs pipeline tests:
  - [tests/run.php](/Users/lo_fye/code/foundryframework/tests/run.php)

**Validation Run**
- `php scripts/build-docs.php` passed.
- `php tests/run.php` passed all tests:
  - extraction
  - generated docs creation
  - authored+generated merge/render
  - manifest output
  - missing-submodule failure behavior
  - deterministic rebuild check

Coverage is strongly test-targeted for this pipeline, but I did not run a formal percentage coverage report tool in this repo.

4 files changed
+33
-14
Undo
README.md
content/docs/homepage/conversation.md
scripts/lib/DocsPipeline.php
tests/run.php

==========================================================================================
==========================================================================================

## Historical Verification Evidence

Historical verification details, when present, are preserved verbatim inside the paired result blocks above.

## Historical Stabilization Notes

Historical stabilization details, when present, are preserved verbatim inside the paired result blocks above.

## Current Repository Alignment

The imported artifact is intentionally retained under `Modules/PreCanonical` as archive-lineage context. Modern module ownership remains deferred until a separate explicit alignment spec maps the pre-canonical intent into current modules.

## Uncertainty And Reconstruction Notes

No modern module inference was performed. The generated note preserves only the marked archive relationships available through `S`, `R`, and `P` blocks.
