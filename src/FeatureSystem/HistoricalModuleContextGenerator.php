<?php

declare(strict_types=1);

namespace Foundry\FeatureSystem;

use Foundry\Support\Paths;

final class HistoricalModuleContextGenerator
{
    private const IMPORT_NOTE = 'Historical import note: this section was reconstructed from archived specs, Codex implementation results, follow-up prompts, and current repository state. Details marked inferred should be treated as lower-confidence historical reconstruction.';

    public function __construct(
        private readonly Paths $paths,
    ) {}

    /**
     * @return array{
     *     status:string,
     *     dry_run:bool,
     *     apply:bool,
     *     module:string|null,
     *     summary:array{modules:int,imported_specs:int,created:int,updated:int,unchanged:int,written:int},
     *     modules:list<array<string,mixed>>
     * }
     */
    public function generate(?string $module, bool $apply, bool $dryRun): array
    {
        $moduleFilter = $this->normalizeModuleFilter($module);
        $groups = $this->importedSpecGroups($moduleFilter);
        $modules = [];
        $totals = [
            'modules' => count($groups),
            'imported_specs' => 0,
            'created' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'written' => 0,
        ];

        foreach ($groups as $moduleName => $specs) {
            $result = $this->generateModule($moduleName, $specs, $apply, $dryRun);
            $modules[] = $result;
            $totals['imported_specs'] += count($specs);

            foreach (['created', 'updated', 'unchanged'] as $status) {
                foreach ($result['context_files'] as $file) {
                    if (($file['status'] ?? null) === $status) {
                        $totals[$status]++;
                    }
                }
            }

            if (!$dryRun && $apply) {
                foreach ($result['context_files'] as $file) {
                    if (in_array((string) ($file['status'] ?? ''), ['created', 'updated'], true)) {
                        $totals['written']++;
                    }
                }
            }
        }

        return [
            'status' => 'ok',
            'dry_run' => $dryRun,
            'apply' => $apply,
            'module' => $moduleFilter,
            'summary' => $totals,
            'modules' => $modules,
        ];
    }

    private function normalizeModuleFilter(?string $module): ?string
    {
        $candidate = trim((string) $module);
        if ($candidate === '') {
            return null;
        }

        $candidate = str_replace(['-', '_'], ' ', $candidate);
        $candidate = str_replace(' ', '', ucwords($candidate));

        return $candidate;
    }

    /**
     * @return array<string,list<array{path:string,name:string,status:string,uncertain:bool,title:string}>>
     */
    private function importedSpecGroups(?string $moduleFilter): array
    {
        $groups = [];
        $patterns = [
            'Modules/*/specs/*.md' => 'active',
            'Modules/*/specs/drafts/*.md' => 'draft',
        ];

        foreach ($patterns as $pattern => $status) {
            foreach (glob($this->paths->join($pattern)) ?: [] as $path) {
                if (!is_file($path)) {
                    continue;
                }

                $relative = $this->outputPath($path);
                if (preg_match('#^Modules/(?<module>[A-Z][A-Za-z0-9]*)/specs/(?:drafts/)?(?<name>[^/]+)\.md$#', $relative, $matches) !== 1) {
                    continue;
                }

                $moduleName = (string) $matches['module'];
                if ($moduleFilter !== null && $moduleName !== $moduleFilter) {
                    continue;
                }

                $contents = (string) file_get_contents($path);
                if (!$this->isHistoricalImportedSpec($contents)) {
                    continue;
                }

                $groups[$moduleName][] = [
                    'path' => $relative,
                    'name' => (string) $matches['name'],
                    'status' => $status,
                    'uncertain' => $status === 'draft' || preg_match('/\b(?:uncertain|inferred|lower-confidence)\b/i', $contents) === 1,
                    'title' => $this->titleFromSpec($contents, (string) $matches['name']),
                ];
            }
        }

        ksort($groups, SORT_STRING);
        foreach ($groups as &$specs) {
            usort($specs, static fn(array $a, array $b): int => strcmp($a['path'], $b['path']));
        }
        unset($specs);

        return $groups;
    }

    private function isHistoricalImportedSpec(string $contents): bool
    {
        return preg_match('/\A# Execution Spec:[^\n]+\n\n## Historical Import Note\s*$/m', $contents) === 1;
    }

    private function titleFromSpec(string $contents, string $fallback): string
    {
        if (preg_match('/^# Execution Spec:\s*(.+)$/m', $contents, $match) === 1) {
            return trim((string) $match[1]);
        }

        return $fallback;
    }

    /**
     * @param list<array{path:string,name:string,status:string,uncertain:bool,title:string}> $specs
     * @return array<string,mixed>
     */
    private function generateModule(string $moduleName, array $specs, bool $apply, bool $dryRun): array
    {
        $slug = $this->slugFromPascal($moduleName);
        $moduleRoot = 'Modules/' . $moduleName;
        $files = [
            'spec' => $moduleRoot . '/' . $slug . '.spec.md',
            'state' => $moduleRoot . '/' . $slug . '.md',
            'decisions' => $moduleRoot . '/' . $slug . '.decisions.md',
        ];

        $desired = [
            'spec' => $this->desiredSpec($moduleName, $slug, $specs, $files['spec']),
            'state' => $this->desiredState($moduleName, $slug, $specs, $files['state']),
            'decisions' => $this->desiredDecisions($moduleName, $slug, $specs, $files['decisions']),
        ];

        $contextFiles = [];
        foreach ($files as $kind => $path) {
            $absolute = $this->paths->join($path);
            $exists = is_file($absolute);
            $current = $exists ? (string) file_get_contents($absolute) : '';
            $next = $desired[$kind];
            $status = !$exists ? 'created' : ($current === $next ? 'unchanged' : 'updated');

            if ($apply && !$dryRun && $status !== 'unchanged') {
                $directory = dirname($absolute);
                if (!is_dir($directory)) {
                    mkdir($directory, 0777, true);
                }
                file_put_contents($absolute, $next);
            }

            $contextFiles[] = [
                'kind' => $kind,
                'path' => $path,
                'status' => $status,
            ];
        }

        return [
            'module' => $moduleName,
            'feature' => $slug,
            'imported_specs' => array_map(fn(array $spec): array => $this->renderImportedSpec($spec), $specs),
            'uncertain_imports' => array_values(array_map(
                static fn(array $spec): string => $spec['path'],
                array_filter($specs, static fn(array $spec): bool => (bool) $spec['uncertain']),
            )),
            'context_files' => $contextFiles,
        ];
    }

    /**
     * @param list<array{path:string,name:string,status:string,uncertain:bool,title:string}> $specs
     */
    private function desiredSpec(string $moduleName, string $slug, array $specs, string $path): string
    {
        $existing = $this->readExisting($path);
        if ($existing !== '') {
            $contents = $existing;
            $contents = $this->ensureSectionBullet(
                $contents,
                'Expected Behavior',
                'Historical imported specs are documented in module context with explicit uncertainty markers.',
            );
            $contents = $this->ensureSectionBullet(
                $contents,
                'Acceptance Criteria',
                'Imported historical specs have module-level context with explicit caveats.',
            );
            $contents = $this->replaceManagedSection($contents, 'Historical Imports', $this->historicalSectionBody($specs, true));

            return $this->normalizeDocument($contents);
        }

        return $this->normalizeDocument(sprintf(
            <<<'MD'
# Feature Spec: %s

## Purpose

Define recovered module context for `%s` from imported historical execution specs.

%s

## Goals

- Preserve imported historical execution-spec context for `%s`.
- Keep uncertainty visible when historical details are inferred or draft-only.
- Maintain canonical module context files under `Modules/%s/`.

## Non-Goals

- Do not infer runtime behavior beyond imported historical specs and current repository state.
- Do not compact or rewrite decision history.
- Do not move framework runtime code out of `src/*`.

## Constraints

- Historical context output must be deterministic and repository-relative.
- Uncertain or inferred details must remain explicitly marked.
- Decision ledgers remain append-only.

## Expected Behavior

- Historical imported specs are documented in module context with explicit uncertainty markers.
- Module context files remain canonical and consumable.
- Historical import caveats remain visible for inferred or draft historical records.

## Acceptance Criteria

- Imported historical specs have module-level context with explicit caveats.
- Missing module context files are created deterministically.
- Decision ledger entries remain append-only.

## Assumptions

- Imported historical specs are lower-confidence reconstruction records unless corroborated by current source, tests, reconstruction notes, or decision ledgers.

## Historical Imports

%s
MD,
            $slug,
            $moduleName,
            self::IMPORT_NOTE,
            $moduleName,
            $moduleName,
            $this->historicalSectionBody($specs, false),
        ));
    }

    /**
     * @param list<array{path:string,name:string,status:string,uncertain:bool,title:string}> $specs
     */
    private function desiredState(string $moduleName, string $slug, array $specs, string $path): string
    {
        $existing = $this->readExisting($path);
        if ($existing !== '') {
            $contents = $existing;
            foreach ([
                'Historical imported specs are documented in module context with explicit uncertainty markers.',
                'Imported historical specs have module-level context with explicit caveats.',
                'Missing module context files are created deterministically.',
                'Decision ledger entries remain append-only.',
            ] as $bullet) {
                $contents = $this->ensureSectionBullet($contents, 'Current State', $bullet);
            }
            $contents = $this->replaceManagedSection($contents, 'Implemented Specs', $this->implementedSpecsBody($specs));
            $contents = $this->replaceManagedSection($contents, 'Active Boundaries', $this->activeBoundariesBody($moduleName));
            $contents = $this->replaceManagedSection($contents, 'Historical Import Caveats', $this->caveatsBody($specs));

            return $this->normalizeDocument($contents);
        }

        return $this->normalizeDocument(sprintf(
            <<<'MD'
# Feature: %s

## Purpose

- Record recovered module context for `%s` from imported historical execution specs.
- %s

## Current State

- Historical imported specs are documented in module context with explicit uncertainty markers.
- Module context files remain canonical and consumable.
- Historical import caveats remain visible for inferred or draft historical records.
- Imported historical specs have module-level context with explicit caveats.
- Missing module context files are created deterministically.
- Decision ledger entries remain append-only.

## Implemented Specs

%s

## Active Boundaries

%s

## Historical Import Caveats

%s

## Open Questions

- Imported historical details marked inferred require review against archived results before being treated as complete reconstruction.

## Next Steps

- Review imported historical specs before generating reconstruction notes or implementation-log entries.
MD,
            $slug,
            $moduleName,
            self::IMPORT_NOTE,
            $this->implementedSpecsBody($specs),
            $this->activeBoundariesBody($moduleName),
            $this->caveatsBody($specs),
        ));
    }

    /**
     * @param list<array{path:string,name:string,status:string,uncertain:bool,title:string}> $specs
     */
    private function desiredDecisions(string $moduleName, string $slug, array $specs, string $path): string
    {
        $existing = $this->readExisting($path);
        $entryTitle = 'record historical module context import for ' . $moduleName;
        if (str_contains($existing, '### Decision: ' . $entryTitle)) {
            return $this->normalizeDocument($existing);
        }

        $entry = sprintf(
            <<<'MD'
### Decision: %s

Timestamp: <ISO-8601>

**Context**

- Historical module context was generated from imported execution specs for `%s`.
%s

**Decision**

- Preserve generated module context files under `Modules/%s/`.
- Mark inferred or uncertain historical details explicitly instead of promoting them as fully verified current behavior.
- Keep decision history append-only and record this import as a reconstruction event.

**Reasoning**

- Imported specs are useful durable context only when module-level intent, state, and caveats are navigable.
- Explicit caveats prevent historical reconstruction from being mistaken for verified current implementation.
- Append-only decision logging preserves existing history while recording the import boundary.

**Alternatives Considered**

- Leave imported specs without module context.
- Rewrite existing decision ledgers into compact summaries.
- Treat every imported spec as fully certain current behavior.

**Impact**

- Future agents can locate imported historical specs through module context files.
- Uncertain historical records remain reviewable before follow-up reconstruction notes or implementation-log entries are generated.

**Spec Reference**

- Goals
- Context File Roles
- Historical Import Marking
- Acceptance Criteria
MD,
            $entryTitle,
            $moduleName,
            $this->decisionSpecBullets($specs),
            $moduleName,
        );

        return $this->normalizeDocument(trim($existing) === '' ? $entry : rtrim($existing) . "\n\n" . $entry);
    }

    /**
     * @param array{path:string,name:string,status:string,uncertain:bool,title:string} $spec
     * @return array{path:string,name:string,status:string,uncertain:bool,title:string}
     */
    private function renderImportedSpec(array $spec): array
    {
        return [
            'path' => $spec['path'],
            'name' => $spec['name'],
            'status' => $spec['status'],
            'uncertain' => (bool) $spec['uncertain'],
            'title' => $spec['title'],
        ];
    }

    private function readExisting(string $relativePath): string
    {
        $path = $this->paths->join($relativePath);
        if (!is_file($path)) {
            return '';
        }

        return (string) file_get_contents($path);
    }

    private function ensureSectionBullet(string $contents, string $section, string $bullet): string
    {
        if (str_contains($contents, '- ' . $bullet)) {
            return $contents;
        }

        $pattern = '/(^## ' . preg_quote($section, '/') . '\s*$\R)(.*?)(?=^## |\z)/ms';
        if (preg_match($pattern, $contents) !== 1) {
            return rtrim($contents) . "\n\n## " . $section . "\n\n- " . $bullet . "\n";
        }

        return preg_replace_callback($pattern, static function (array $matches) use ($bullet): string {
            $body = rtrim((string) $matches[2]);
            $body = $body === '' ? '- ' . $bullet : $body . "\n- " . $bullet;

            return (string) $matches[1] . $body . "\n\n";
        }, $contents, 1) ?? $contents;
    }

    private function replaceManagedSection(string $contents, string $section, string $body): string
    {
        $replacement = "## " . $section . "\n\n" . rtrim($body) . "\n";
        $pattern = '/^## ' . preg_quote($section, '/') . '\s*$\R.*?(?=^## |\z)/ms';
        if (preg_match($pattern, $contents) === 1) {
            return preg_replace($pattern, $replacement . "\n", $contents, 1) ?? $contents;
        }

        return rtrim($contents) . "\n\n" . $replacement;
    }

    /**
     * @param list<array{path:string,name:string,status:string,uncertain:bool,title:string}> $specs
     */
    private function historicalSectionBody(array $specs, bool $includeNote): string
    {
        $lines = [];
        if ($includeNote) {
            $lines[] = self::IMPORT_NOTE;
            $lines[] = '';
        }

        $lines[] = 'Imported specs:';
        foreach ($specs as $spec) {
            $certainty = $spec['uncertain'] ? 'inferred/uncertain' : 'imported';
            $lines[] = '- `' . $spec['path'] . '` (' . $spec['status'] . ', ' . $certainty . ')';
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * @param list<array{path:string,name:string,status:string,uncertain:bool,title:string}> $specs
     */
    private function implementedSpecsBody(array $specs): string
    {
        $lines = [];
        foreach ($specs as $spec) {
            $status = $spec['status'] === 'active' ? 'active imported spec' : 'draft imported spec';
            $certainty = $spec['uncertain'] ? '; inferred or uncertain' : '';
            $lines[] = '- `' . $spec['path'] . '` - ' . $status . $certainty . '.';
        }

        return implode("\n", $lines) . "\n";
    }

    private function activeBoundariesBody(string $moduleName): string
    {
        return '- Framework runtime remains layer-organized under `src/*` unless a future active spec changes placement.' . "\n"
            . '- Module governance context lives under `Modules/' . $moduleName . '/`.' . "\n"
            . '- Imported historical records do not by themselves prove current runtime behavior without source, tests, or follow-up reconstruction evidence.' . "\n";
    }

    /**
     * @param list<array{path:string,name:string,status:string,uncertain:bool,title:string}> $specs
     */
    private function caveatsBody(array $specs): string
    {
        $uncertain = array_values(array_filter($specs, static fn(array $spec): bool => (bool) $spec['uncertain']));
        $lines = [self::IMPORT_NOTE];
        if ($uncertain === []) {
            $lines[] = '- No draft-only imported specs were detected for this module.';
        } else {
            foreach ($uncertain as $spec) {
                $lines[] = '- `' . $spec['path'] . '` is inferred or uncertain and requires review before being treated as complete implementation history.';
            }
        }

        return implode("\n", $lines) . "\n";
    }

    /**
     * @param list<array{path:string,name:string,status:string,uncertain:bool,title:string}> $specs
     */
    private function decisionSpecBullets(array $specs): string
    {
        $lines = [];
        foreach ($specs as $spec) {
            $lines[] = '- Imported spec: `' . $spec['path'] . '` (' . $spec['status'] . ($spec['uncertain'] ? ', inferred/uncertain' : '') . ').';
        }

        return implode("\n", $lines);
    }

    private function normalizeDocument(string $contents): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", trim($contents));
        $normalized = preg_replace("/\n{3,}/", "\n\n", $normalized) ?? $normalized;

        return rtrim($normalized) . "\n";
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

    private function slugFromPascal(string $value): string
    {
        $slug = preg_replace('/(?<!^)[A-Z]/', '-$0', $value) ?? $value;
        $slug = strtolower($slug);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? $slug;

        return trim($slug, '-');
    }
}
