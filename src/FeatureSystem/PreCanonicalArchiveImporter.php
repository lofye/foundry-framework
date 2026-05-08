<?php

declare(strict_types=1);

namespace Foundry\FeatureSystem;

use Foundry\Support\FoundryError;
use Foundry\Support\Paths;

final class PreCanonicalArchiveImporter
{
    public function __construct(
        private readonly Paths $paths,
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function import(string $sourcePath, string $targetModule, bool $apply, bool $force): array
    {
        $sourceAbsolute = $this->absolutePath($sourcePath);
        if (!is_file($sourceAbsolute)) {
            throw new FoundryError(
                'PRECANONICAL_ARCHIVE_SOURCE_MISSING',
                'validation',
                ['source' => $this->outputPath($sourceAbsolute)],
                'Pre-canonical archive source file is missing.',
            );
        }

        $module = $this->normalizeTargetModule($targetModule);
        $blocks = $this->parseBlocks((string) file_get_contents($sourceAbsolute));
        $model = $this->buildImportModel($blocks, $module, $this->outputPath($sourceAbsolute));
        $artifacts = $this->buildArtifacts($model, $module);
        $conflicts = $this->detectConflicts($artifacts, $force);
        if ($conflicts !== []) {
            throw new FoundryError(
                'PRECANONICAL_ARCHIVE_OUTPUT_CONFLICT',
                'conflict',
                ['conflicts' => $conflicts],
                'Pre-canonical import output conflicts with existing files.',
            );
        }

        $written = 0;
        $replaced = 0;
        if ($apply) {
            foreach ($artifacts as $artifact) {
                $absolute = $this->paths->join($artifact['path']);
                $alreadyExists = is_file($absolute);
                $same = $alreadyExists && ((string) file_get_contents($absolute)) === $artifact['content'];
                if ($same) {
                    continue;
                }

                $this->writeFile($absolute, $artifact['content']);
                $written++;
                if ($alreadyExists && $artifact['kind'] !== 'implementation_log') {
                    $replaced++;
                }
            }
        }

        return [
            'status' => 'ok',
            'apply' => $apply,
            'dry_run' => !$apply,
            'force' => $force,
            'source_path' => $this->outputPath($sourceAbsolute),
            'target_module' => $module,
            'summary' => [
                'spec_blocks' => count($model['specs']),
                'result_blocks' => $model['result_blocks'],
                'preamble_blocks' => $model['preamble_blocks'],
                'paired_result_blocks' => $model['paired_result_blocks'],
                'associated_preamble_blocks' => $model['associated_preamble_blocks'],
                'global_preamble_blocks' => count($model['global_preambles']),
                'orphan_result_blocks' => 0,
                'duplicate_spec_names' => 0,
                'canonical_id_collisions' => 0,
                'conflicts' => 0,
                'artifacts' => count($artifacts),
                'written' => $written,
                'replaced' => $replaced,
            ],
            'specs' => array_map(
                fn(array $spec): array => [
                    'legacy_name' => $spec['name'],
                    'normalized_name' => $spec['normalized_name'],
                    'legacy_id' => $spec['legacy_id'],
                    'canonical_id' => $spec['canonical_id'],
                    'slug' => $spec['slug'],
                    'spec_path' => $spec['spec_path'],
                    'plan_path' => $spec['plan_path'],
                    'result_blocks' => count($spec['results']),
                    'preamble_blocks' => count($spec['preambles']),
                ],
                $model['specs'],
            ),
            'artifacts' => array_map(
                fn(array $artifact): array => [
                    'path' => $artifact['path'],
                    'kind' => $artifact['kind'],
                    'action' => $this->artifactAction($artifact, $apply, $force),
                ],
                $artifacts,
            ),
        ];
    }

    private function normalizeTargetModule(string $targetModule): string
    {
        $module = trim($targetModule);
        if ($module === '' || preg_match('/^[A-Z][A-Za-z0-9]*$/', $module) !== 1) {
            throw new FoundryError(
                'PRECANONICAL_ARCHIVE_TARGET_MODULE_INVALID',
                'validation',
                ['target_module' => $targetModule],
                'Pre-canonical import target module must be a canonical module name.',
            );
        }

        return $module;
    }

    /**
     * @return list<array{type:string,name:string,normalized_name:string,body:string,order:int}>
     */
    private function parseBlocks(string $contents): array
    {
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $contents));
        $blocks = [];
        $current = null;
        $order = 0;
        $count = count($lines);

        for ($index = 0; $index < $count; $index++) {
            $line = $lines[$index];
            if ($this->isMarkerLine($line)) {
                if ($current !== null) {
                    $current['body'] = $this->normalizeBody(implode("\n", $current['body_lines']));
                    unset($current['body_lines']);
                    $blocks[] = $current;
                }

                $type = $line[0];
                $nameLine = $lines[$index + 1] ?? null;
                if ($nameLine === null || !str_starts_with($nameLine, 'NAME:')) {
                    throw new FoundryError(
                        'PRECANONICAL_ARCHIVE_BLOCK_NAME_MISSING',
                        'validation',
                        ['line' => $index + 1, 'block_type' => $type],
                        'Marked pre-canonical archive block is missing a NAME line.',
                    );
                }

                $name = trim(substr($nameLine, strlen('NAME:')));
                if ($name === '') {
                    throw new FoundryError(
                        'PRECANONICAL_ARCHIVE_BLOCK_NAME_MISSING',
                        'validation',
                        ['line' => $index + 2, 'block_type' => $type],
                        'Marked pre-canonical archive block has an empty NAME line.',
                    );
                }

                $order++;
                $current = [
                    'type' => $type,
                    'name' => $name,
                    'normalized_name' => $this->normalizeName($name),
                    'body_lines' => [],
                    'body' => '',
                    'order' => $order,
                ];
                $index++;
                continue;
            }

            if ($current !== null) {
                $current['body_lines'][] = $line;
            }
        }

        if ($current !== null) {
            $current['body'] = $this->normalizeBody(implode("\n", $current['body_lines']));
            unset($current['body_lines']);
            $blocks[] = $current;
        }

        return $blocks;
    }

    private function isMarkerLine(string $line): bool
    {
        return preg_match('/^[SRP]@+$/', trim($line)) === 1;
    }

    private function normalizeBody(string $body): string
    {
        $normalized = trim($body);

        return $normalized === '' ? '' : rtrim($normalized, "\n") . "\n";
    }

    private function normalizeName(string $name): string
    {
        $normalized = trim($name);
        $normalized = str_replace(["\u{2013}", "\u{2014}", "\u{2212}"], '-', $normalized);
        $normalized = preg_replace('/\s+-\s+/', ' - ', $normalized) ?? $normalized;
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return strtolower(trim($normalized));
    }

    /**
     * @param list<array{type:string,name:string,normalized_name:string,body:string,order:int}> $blocks
     * @return array<string,mixed>
     */
    private function buildImportModel(array $blocks, string $module, string $sourcePath): array
    {
        $specsByName = [];
        $resultsByName = [];
        $pendingPreambles = [];
        $globalPreambles = [];
        $resultBlocks = 0;
        $preambleBlocks = 0;
        $pairedResultBlocks = 0;
        $associatedPreambleBlocks = 0;

        foreach ($blocks as $block) {
            if ($block['type'] === 'P') {
                $preambleBlocks++;
                $pendingPreambles[] = $block;
                continue;
            }

            if ($block['type'] === 'R') {
                $resultBlocks++;
                $resultsByName[$block['normalized_name']][] = $block;
                continue;
            }

            $name = $block['normalized_name'];
            if (isset($specsByName[$name])) {
                if ($specsByName[$name]['body'] !== $block['body']) {
                    throw new FoundryError(
                        'PRECANONICAL_ARCHIVE_DUPLICATE_SPEC_NAME',
                        'validation',
                        ['name' => $block['name']],
                        'Duplicate pre-canonical spec NAME has different content.',
                    );
                }

                continue;
            }

            $metadata = $this->buildSpecMetadata($block['name']);
            $specsByName[$name] = [
                'name' => $block['name'],
                'normalized_name' => $name,
                'body' => $block['body'],
                'order' => $block['order'],
                'legacy_id' => $metadata['legacy_id'],
                'canonical_id' => $metadata['canonical_id'],
                'slug' => $metadata['slug'],
                'id_and_slug' => $metadata['canonical_id'] . '-' . $metadata['slug'],
                'spec_path' => 'Modules/' . $module . '/specs/' . $metadata['canonical_id'] . '-' . $metadata['slug'] . '.md',
                'plan_path' => 'Modules/' . $module . '/plans/' . $metadata['canonical_id'] . '-' . $metadata['slug'] . '.md',
                'preambles' => $pendingPreambles,
                'results' => [],
            ];
            $associatedPreambleBlocks += count($pendingPreambles);
            $pendingPreambles = [];
        }

        if ($specsByName === []) {
            throw new FoundryError(
                'PRECANONICAL_ARCHIVE_NO_SPEC_BLOCKS',
                'validation',
                ['source' => $sourcePath],
                'Pre-canonical archive contains no marked spec blocks.',
            );
        }

        foreach ($resultsByName as $name => $results) {
            if (!isset($specsByName[$name])) {
                throw new FoundryError(
                    'PRECANONICAL_ARCHIVE_ORPHAN_RESULT_BLOCK',
                    'validation',
                    ['name' => $results[0]['name']],
                    'Pre-canonical result block does not match any marked spec block.',
                );
            }

            $specsByName[$name]['results'] = $results;
            $pairedResultBlocks += count($results);
        }

        $globalPreambles = $pendingPreambles;
        $specs = array_values($specsByName);
        usort(
            $specs,
            static fn(array $a, array $b): int => strcmp((string) $a['canonical_id'], (string) $b['canonical_id'])
                ?: ((int) $a['order'] <=> (int) $b['order']),
        );

        $canonicalIds = [];
        foreach ($specs as $spec) {
            $id = (string) $spec['canonical_id'];
            if (isset($canonicalIds[$id]) && $canonicalIds[$id] !== $spec['normalized_name']) {
                throw new FoundryError(
                    'PRECANONICAL_ARCHIVE_CANONICAL_ID_COLLISION',
                    'validation',
                    ['canonical_id' => $id, 'names' => [$canonicalIds[$id], $spec['normalized_name']]],
                    'Pre-canonical legacy IDs map to the same canonical ID.',
                );
            }

            $canonicalIds[$id] = $spec['normalized_name'];
        }

        return [
            'module' => $module,
            'source_path' => $sourcePath,
            'specs' => $specs,
            'global_preambles' => $globalPreambles,
            'result_blocks' => $resultBlocks,
            'preamble_blocks' => $preambleBlocks,
            'paired_result_blocks' => $pairedResultBlocks,
            'associated_preamble_blocks' => $associatedPreambleBlocks,
        ];
    }

    /**
     * @return array{legacy_id:string,canonical_id:string,slug:string}
     */
    private function buildSpecMetadata(string $name): array
    {
        if (preg_match('/^(?<id>[0-9][0-9A-Za-z]*(?:-[0-9]+)?)(?:\s|:|-|\x{2013}|\x{2014}|$)/u', $name, $matches) !== 1) {
            throw new FoundryError(
                'PRECANONICAL_ARCHIVE_LEGACY_ID_INVALID',
                'validation',
                ['name' => $name],
                'Pre-canonical spec NAME does not start with a valid legacy ID.',
            );
        }

        $legacyId = $matches['id'];
        $canonicalId = $this->canonicalId($legacyId);
        $description = trim(substr($name, strlen($legacyId)));
        $description = preg_replace('/^[\s:–—-]+/u', '', $description) ?? $description;
        $slug = $this->slug($description);
        if ($slug === '') {
            $slug = 'spec-' . $this->slug($legacyId);
        }

        return [
            'legacy_id' => $legacyId,
            'canonical_id' => $canonicalId,
            'slug' => $slug,
        ];
    }

    private function canonicalId(string $legacyId): string
    {
        $parts = explode('-', strtoupper($legacyId));
        $base = array_shift($parts) ?? '';
        preg_match_all('/[0-9]+|[A-Z]/', $base, $matches);
        $segments = [];
        foreach ($matches[0] as $segment) {
            if (ctype_digit($segment)) {
                $segments[] = sprintf('%03d', (int) $segment);
                continue;
            }

            $segments[] = sprintf('%03d', ord($segment) - ord('A') + 1);
        }

        foreach ($parts as $part) {
            if ($part === '' || !ctype_digit($part)) {
                throw new FoundryError(
                    'PRECANONICAL_ARCHIVE_LEGACY_ID_INVALID',
                    'validation',
                    ['legacy_id' => $legacyId],
                    'Pre-canonical legacy ID hyphen suffix must be numeric.',
                );
            }

            $segments[] = sprintf('%03d', (int) $part);
        }

        if ($segments === []) {
            throw new FoundryError(
                'PRECANONICAL_ARCHIVE_LEGACY_ID_INVALID',
                'validation',
                ['legacy_id' => $legacyId],
                'Pre-canonical legacy ID did not produce canonical segments.',
            );
        }

        return implode('.', $segments);
    }

    private function slug(string $value): string
    {
        $normalized = strtolower(trim($value));
        $normalized = str_replace(["\u{2013}", "\u{2014}", "\u{2212}"], '-', $normalized);
        $normalized = preg_replace('/[^a-z0-9]+/', '-', $normalized) ?? $normalized;
        $normalized = preg_replace('/-+/', '-', $normalized) ?? $normalized;

        return trim($normalized, '-');
    }

    /**
     * @param array<string,mixed> $model
     * @return list<array{path:string,kind:string,content:string}>
     */
    private function buildArtifacts(array $model, string $module): array
    {
        $artifacts = [];
        foreach ($model['specs'] as $spec) {
            $artifacts[] = [
                'path' => $spec['spec_path'],
                'kind' => 'spec',
                'content' => $this->renderSpec($spec, $model),
            ];
            $artifacts[] = [
                'path' => $spec['plan_path'],
                'kind' => 'plan',
                'content' => $this->renderPlan($spec, $model),
            ];
        }

        $artifacts[] = [
            'path' => 'Modules/' . $module . '/pre-canonical.spec.md',
            'kind' => 'context_spec',
            'content' => $this->renderModuleSpec($model),
        ];
        $artifacts[] = [
            'path' => 'Modules/' . $module . '/pre-canonical.md',
            'kind' => 'context_state',
            'content' => $this->renderModuleState($model),
        ];
        $artifacts[] = [
            'path' => 'Modules/' . $module . '/pre-canonical.decisions.md',
            'kind' => 'context_decisions',
            'content' => $this->renderModuleDecisions($model),
        ];
        $artifacts[] = [
            'path' => 'Modules/implementation.log',
            'kind' => 'implementation_log',
            'content' => $this->renderImplementationLog($model),
        ];

        return $artifacts;
    }

    /**
     * @param array<string,mixed> $spec
     * @param array<string,mixed> $model
     */
    private function renderSpec(array $spec, array $model): string
    {
        return '# Execution Spec: ' . $spec['id_and_slug'] . "\n\n"
            . "## Historical Import Note\n\n"
            . "This spec was imported from the explicitly marked pre-canonical archive.\n\n"
            . '- Legacy name: `' . $spec['name'] . "`\n"
            . '- Legacy id: `' . $spec['legacy_id'] . "`\n"
            . '- Canonical pre-canonical id: `' . $spec['canonical_id'] . "`\n"
            . '- Imported module: `' . $model['module'] . "`\n"
            . '- Source archive: `' . $model['source_path'] . "`\n\n"
            . "## Original Pre-Canonical Spec\n\n"
            . rtrim((string) $spec['body'], "\n") . "\n";
    }

    /**
     * @param array<string,mixed> $spec
     * @param array<string,mixed> $model
     */
    private function renderPlan(array $spec, array $model): string
    {
        $preambleText = $this->renderPreambles($spec['preambles']);
        $resultText = $this->renderResults($spec['results']);

        return '# Implementation Plan: ' . $spec['id_and_slug'] . "\n\n"
            . "## Historical Provenance\n\n"
            . '- Imported spec path: `' . $spec['spec_path'] . "`\n"
            . '- Source archive: `' . $model['source_path'] . "`\n"
            . '- Legacy name: `' . $spec['name'] . "`\n"
            . '- Legacy id: `' . $spec['legacy_id'] . "`\n"
            . '- Canonical pre-canonical id: `' . $spec['canonical_id'] . "`\n\n"
            . "## Historical Specification Summary\n\n"
            . "The original pre-canonical specification body is preserved in the imported execution spec. This reconstruction note records adjacent marked context and result evidence without inferring modern module ownership.\n\n"
            . "## Historical Preamble Context\n\n"
            . $preambleText . "\n\n"
            . "## Historical Implementation Evidence\n\n"
            . $resultText . "\n\n"
            . "## Historical Verification Evidence\n\n"
            . $this->verificationText($spec['results']) . "\n\n"
            . "## Historical Stabilization Notes\n\n"
            . $this->stabilizationText($spec['results']) . "\n\n"
            . "## Current Repository Alignment\n\n"
            . "The imported artifact is intentionally retained under `Modules/" . $model['module'] . "` as archive-lineage context. Modern module ownership remains deferred until a separate explicit alignment spec maps the pre-canonical intent into current modules.\n\n"
            . "## Uncertainty And Reconstruction Notes\n\n"
            . "No modern module inference was performed. The generated note preserves only the marked archive relationships available through `S`, `R`, and `P` blocks.\n";
    }

    /**
     * @param list<array<string,mixed>> $preambles
     */
    private function renderPreambles(array $preambles): string
    {
        if ($preambles === []) {
            return "No marked preamble block was associated with this spec.";
        }

        $lines = [];
        foreach ($preambles as $index => $preamble) {
            $lines[] = '### Preamble Block ' . ($index + 1);
            $lines[] = '';
            $lines[] = '- Name: `' . $preamble['name'] . '`';
            $lines[] = '';
            $lines[] = rtrim((string) $preamble['body'], "\n");
            $lines[] = '';
        }

        return rtrim(implode("\n", $lines));
    }

    /**
     * @param list<array<string,mixed>> $results
     */
    private function renderResults(array $results): string
    {
        if ($results === []) {
            return "No matching marked result block was present in the pre-canonical archive.";
        }

        $lines = [];
        foreach ($results as $index => $result) {
            $lines[] = '### Result Block ' . ($index + 1);
            $lines[] = '';
            $lines[] = '- Name: `' . $result['name'] . '`';
            $lines[] = '';
            $lines[] = rtrim((string) $result['body'], "\n");
            $lines[] = '';
        }

        return rtrim(implode("\n", $lines));
    }

    /**
     * @param list<array<string,mixed>> $results
     */
    private function verificationText(array $results): string
    {
        if ($results === []) {
            return 'No marked result evidence was available to reconstruct historical verification commands.';
        }

        return 'Historical verification details, when present, are preserved verbatim inside the paired result blocks above.';
    }

    /**
     * @param list<array<string,mixed>> $results
     */
    private function stabilizationText(array $results): string
    {
        if ($results === []) {
            return 'No marked result evidence was available to reconstruct historical stabilization notes.';
        }

        return 'Historical stabilization details, when present, are preserved verbatim inside the paired result blocks above.';
    }

    /**
     * @param array<string,mixed> $model
     */
    private function renderModuleSpec(array $model): string
    {
        return "# Feature Spec: pre-canonical\n\n"
            . "## Purpose\n\n"
            . "`Modules/" . $model['module'] . "` preserves explicitly marked pre-canonical archive material that predates the current module system. It is an archive-lineage module, not a cohesive modern runtime module.\n\n"
            . "## Archive Contract\n\n"
            . "- `S@...` blocks are imported as execution specs.\n"
            . "- `R@...` blocks are paired to specs by normalized `NAME:` text and preserved as reconstruction evidence.\n"
            . "- `P@...` blocks are preserved as contextual preamble evidence and are never imported as specs.\n"
            . "- Legacy IDs map to dot-separated padded numeric canonical IDs by preserving numeric, alphabetic, and hyphen suffix order.\n"
            . "- Imported pre-canonical specs must not be renumbered into modern modules without a later explicit alignment spec.\n";
    }

    /**
     * @param array<string,mixed> $model
     */
    private function renderModuleState(array $model): string
    {
        $first = $model['specs'][0]['id_and_slug'];
        $last = $model['specs'][count($model['specs']) - 1]['id_and_slug'];
        $global = $this->renderGlobalPreambles($model['global_preambles']);

        return "# Feature State: pre-canonical\n\n"
            . "## Current State\n\n"
            . "`Modules/" . $model['module'] . "` contains imported pre-canonical archive specs from `" . $model['source_path'] . "`. The import currently covers " . count($model['specs']) . " spec artifacts from `" . $first . "` through `" . $last . "`.\n\n"
            . "## Decision Summary\n\n"
            . "- Pre-canonical archive records are preserved under `Modules/" . $model['module'] . "` instead of inferred into modern modules.\n"
            . "- `S`, `R`, and `P` markers remain the durable archive boundary for imported material.\n"
            . "- Refreshed Through Spec: `" . $last . "`\n\n"
            . "## Imported Range\n\n"
            . "- First imported spec: `" . $first . "`\n"
            . "- Last imported spec: `" . $last . "`\n"
            . "- Imported spec count: `" . count($model['specs']) . "`\n\n"
            . "## Global Preamble Context\n\n"
            . $global . "\n\n"
            . "## Open Questions\n\n"
            . "- Which pre-canonical archive records should be mapped into modern module ownership remains intentionally unresolved.\n\n"
            . "## Next Steps\n\n"
            . "- Use explicit future alignment specs to connect imported pre-canonical lineage to current framework modules.\n";
    }

    /**
     * @param list<array<string,mixed>> $preambles
     */
    private function renderGlobalPreambles(array $preambles): string
    {
        if ($preambles === []) {
            return 'No unassociated marked preamble blocks were present in the imported archive.';
        }

        $lines = [];
        foreach ($preambles as $index => $preamble) {
            $lines[] = '- Global preamble ' . ($index + 1) . ': `' . $preamble['name'] . '`';
        }

        return implode("\n", $lines);
    }

    /**
     * @param array<string,mixed> $model
     */
    private function renderModuleDecisions(array $model): string
    {
        return "# Decisions: pre-canonical\n\n"
            . "## Decision: Preserve Explicit Pre-Canonical Archive Markers\n\n"
            . "Context: The imported source archive uses explicit `S`, `R`, and `P` markers to distinguish specifications, result evidence, and preamble context.\n\n"
            . "Decision: Preserve the marked archive under `Modules/" . $model['module'] . "` and use marker type plus normalized `NAME:` text as the only pairing authority.\n\n"
            . "Reasoning: The pre-canonical archive predates modern module ownership, so preserving lineage is safer than inferring current module placement.\n\n"
            . "Alternatives: Infer modern modules during import; discard result or preamble blocks; import preambles as specs.\n\n"
            . "Impact: Imported specs remain deterministic historical artifacts with reconstruction notes carrying paired evidence and context.\n\n"
            . "## Decision: Map Legacy IDs Without Renumbering\n\n"
            . "Context: Legacy IDs combine numeric, alphabetic, and hyphen suffix segments such as `19FB`, `30C-2`, and `35D7C`.\n\n"
            . "Decision: Convert legacy IDs into padded dot-separated canonical IDs while preserving segment order.\n\n"
            . "Reasoning: The mapping keeps lexical ordering aligned with intended historical ordering without inventing modern spec identities.\n\n"
            . "Alternatives: Allocate new contiguous modern module IDs or preserve raw legacy IDs in filenames.\n\n"
            . "Impact: Imported filenames are validator-compatible and stable across reruns.\n";
    }

    /**
     * @param array<string,mixed> $model
     */
    private function renderImplementationLog(array $model): string
    {
        $path = $this->paths->join('Modules/implementation.log');
        $existing = is_file($path) ? rtrim((string) file_get_contents($path), "\n") : "# Implementation Log\n";
        $append = [];

        foreach ($model['specs'] as $spec) {
            $line = '- spec: ' . $spec['spec_path'];
            if (str_contains($existing, $line) || in_array($line, $append, true)) {
                continue;
            }

            $append[] = '';
            $append[] = '## PreCanonical historical import: ' . $spec['id_and_slug'];
            $append[] = $line;
            $append[] = '- note: Imported from explicitly marked pre-canonical archive `' . $model['source_path'] . '`.';
        }

        if ($append === []) {
            return $existing . "\n";
        }

        return $existing . "\n" . implode("\n", $append) . "\n";
    }

    /**
     * @param list<array{path:string,kind:string,content:string}> $artifacts
     * @return list<array{path:string,kind:string,message:string}>
     */
    private function detectConflicts(array $artifacts, bool $force): array
    {
        $conflicts = [];
        foreach ($artifacts as $artifact) {
            $absolute = $this->paths->join($artifact['path']);
            if (!is_file($absolute)) {
                continue;
            }

            if (((string) file_get_contents($absolute)) === $artifact['content']) {
                continue;
            }

            if ($artifact['kind'] === 'implementation_log') {
                continue;
            }

            if (!$force) {
                $conflicts[] = [
                    'path' => $artifact['path'],
                    'kind' => $artifact['kind'],
                    'message' => 'Existing file has different content.',
                ];
            }
        }

        return $conflicts;
    }

    /**
     * @param array{path:string,kind:string,content:string} $artifact
     */
    private function artifactAction(array $artifact, bool $apply, bool $force): string
    {
        $absolute = $this->paths->join($artifact['path']);
        if (!is_file($absolute)) {
            return $apply ? 'written' : 'would_write';
        }

        if (((string) file_get_contents($absolute)) === $artifact['content']) {
            return 'already_current';
        }

        if ($artifact['kind'] === 'implementation_log') {
            return $apply ? 'updated' : 'would_update';
        }

        if ($force) {
            return $apply ? 'replaced' : 'would_replace';
        }

        return 'conflict';
    }

    private function writeFile(string $absolutePath, string $contents): void
    {
        $directory = dirname($absolutePath);
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new FoundryError(
                'PRECANONICAL_ARCHIVE_OUTPUT_DIRECTORY_CREATE_FAILED',
                'io',
                ['directory' => $this->outputPath($directory)],
                'Unable to create pre-canonical import output directory.',
            );
        }

        file_put_contents($absolutePath, $contents);
    }

    private function absolutePath(string $path): string
    {
        $normalized = str_replace('\\', '/', trim($path));
        if ($normalized !== '' && str_starts_with($normalized, '/')) {
            return rtrim($normalized, '/');
        }

        return rtrim($this->paths->join(ltrim($normalized, './')), '/');
    }

    private function outputPath(string $path): string
    {
        $normalized = str_replace('\\', '/', trim($path));
        $root = rtrim(str_replace('\\', '/', $this->paths->root()), '/');

        if ($normalized === $root) {
            return '.';
        }

        if (str_starts_with($normalized, $root . '/')) {
            return substr($normalized, strlen($root . '/'));
        }

        return ltrim($normalized, '/');
    }
}
