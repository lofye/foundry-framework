<?php

declare(strict_types=1);

namespace Foundry\FeatureSystem;

use Foundry\Support\Paths;

final class HistoricalReconstructionGenerator
{
    private const PROVENANCE = 'This reconstruction note was generated from archived pre-repository implementation records, Codex result output, embedded OUTPUT/RESULT evidence, follow-up prompts when available, and current repository state. Details marked inferred should be treated as reconstructed context rather than original implementation-session truth.';

    public function __construct(
        private readonly Paths $paths,
    ) {}

    /**
     * @return array{
     *     status:string,
     *     dry_run:bool,
     *     apply:bool,
     *     module:string|null,
     *     summary:array{imported_specs:int,notes_created:int,notes_existing:int,log_entries_appended:int,log_entries_existing:int,written:int},
     *     specs:list<array<string,mixed>>,
     *     implementation_log:array{path:string,status:string,appended_entries:list<string>,existing_entries:list<string>}
     * }
     */
    public function generate(?string $module, bool $apply, bool $dryRun): array
    {
        $moduleFilter = $this->normalizeModuleFilter($module);
        $specs = $this->importedCompletedSpecs($moduleFilter);
        $log = $this->implementationLogState($specs);
        $results = [];
        $summary = [
            'imported_specs' => count($specs),
            'notes_created' => 0,
            'notes_existing' => 0,
            'log_entries_appended' => count($log['appended_entries']),
            'log_entries_existing' => count($log['existing_entries']),
            'written' => 0,
        ];

        foreach ($specs as $spec) {
            $notePath = $this->notePath($spec);
            $noteAbsolute = $this->paths->join($notePath);
            $noteExists = is_file($noteAbsolute);
            $status = $noteExists ? 'existing' : 'created';
            $desired = $this->renderNote($spec);

            if ($status === 'created') {
                $summary['notes_created']++;
            } else {
                $summary['notes_existing']++;
            }

            if ($apply && !$dryRun && $status === 'created') {
                $directory = dirname($noteAbsolute);
                if (!is_dir($directory)) {
                    mkdir($directory, 0777, true);
                }
                file_put_contents($noteAbsolute, $desired);
                $summary['written']++;
            }

            $results[] = [
                'spec_path' => $spec['path'],
                'note_path' => $notePath,
                'note_status' => $status,
                'evidence' => [
                    'implementation' => $spec['evidence']['implementation_level'],
                    'verification' => $spec['evidence']['verification_level'],
                    'stabilization' => $spec['evidence']['stabilization_level'],
                ],
            ];
        }

        if ($apply && !$dryRun && $log['appended_entries'] !== []) {
            $this->appendLogEntries($log['appended_entries']);
            $summary['written']++;
            $log['status'] = 'updated';
        }

        return [
            'status' => 'ok',
            'dry_run' => $dryRun,
            'apply' => $apply,
            'module' => $moduleFilter,
            'summary' => $summary,
            'specs' => $results,
            'implementation_log' => $log,
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
     * @return list<array{module:string,name:string,path:string,contents:string,title:string,evidence:array<string,mixed>}>
     */
    private function importedCompletedSpecs(?string $moduleFilter): array
    {
        $specs = [];
        foreach (glob($this->paths->join('Modules/*/specs/*.md')) ?: [] as $path) {
            if (!is_file($path)) {
                continue;
            }

            $relative = $this->outputPath($path);
            if (preg_match('#^Modules/(?<module>[A-Z][A-Za-z0-9]*)/specs/(?<name>[^/]+)\.md$#', $relative, $matches) !== 1) {
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

            $specs[] = [
                'module' => $moduleName,
                'name' => (string) $matches['name'],
                'path' => $relative,
                'contents' => $contents,
                'title' => $this->titleFromSpec($contents, (string) $matches['name']),
                'evidence' => $this->extractEvidence($contents),
            ];
        }

        usort($specs, static fn(array $a, array $b): int => strcmp($a['path'], $b['path']));

        return $specs;
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
     * @return array{sections:list<array{heading:string,summary:list<string>,paths:list<string>,verification:list<string>,stabilization:list<string>}>,implementation_level:string,verification_level:string,stabilization_level:string}
     */
    private function extractEvidence(string $contents): array
    {
        $sections = [];
        $lines = preg_split('/\n/', str_replace(["\r\n", "\r"], "\n", $contents)) ?: [];
        $headings = ['OUTPUT', 'RESULT', 'IMPLEMENTATION RESULT', 'STRICT RESULT'];

        for ($index = 0; $index < count($lines); $index++) {
            $line = trim($lines[$index]);
            $matched = null;
            foreach ($headings as $heading) {
                if (preg_match('/^#{0,6}\s*' . preg_quote($heading, '/') . '\s*:?$/i', $line) === 1) {
                    $matched = $heading;
                    break;
                }
            }

            if ($matched === null) {
                continue;
            }

            $collected = [];
            for ($cursor = $index + 1; $cursor < count($lines); $cursor++) {
                $candidate = trim($lines[$cursor]);
                if (preg_match('/^#{1,6}\s+[A-Za-z]/', $candidate) === 1) {
                    break;
                }
                if ($candidate !== '') {
                    $collected[] = $candidate;
                }
            }

            $sections[] = [
                'heading' => $matched,
                'summary' => $this->summarizeLines($collected),
                'paths' => $this->extractPaths($collected),
                'verification' => $this->matchingLines($collected, '/\b(?:phpunit|coverage|verify|spec:validate|passed|failed|exit\s*0)\b/i'),
                'stabilization' => $this->matchingLines($collected, '/\b(?:fix|follow-up|stabili[sz]e|warning|deprecation|retry|repair)\b/i'),
            ];
        }

        $hasSections = $sections !== [];
        $hasVerification = array_any($sections, static fn(array $section): bool => $section['verification'] !== []);
        $hasStabilization = array_any($sections, static fn(array $section): bool => $section['stabilization'] !== []);

        return [
            'sections' => $sections,
            'implementation_level' => $hasSections ? 'confirmed' : 'unknown',
            'verification_level' => $hasVerification ? 'confirmed' : 'unknown',
            'stabilization_level' => $hasStabilization ? 'inferred' : 'unknown',
        ];
    }

    /**
     * @param list<string> $lines
     * @return list<string>
     */
    private function summarizeLines(array $lines): array
    {
        $summary = [];
        foreach ($lines as $line) {
            $clean = trim(preg_replace('/\s+/', ' ', $line) ?? $line);
            if ($clean === '') {
                continue;
            }
            $summary[] = substr($clean, 0, 180);
            if (count($summary) === 5) {
                break;
            }
        }

        return $summary;
    }

    /**
     * @param list<string> $lines
     * @return list<string>
     */
    private function extractPaths(array $lines): array
    {
        $paths = [];
        foreach ($lines as $line) {
            if (preg_match_all('#\b(?:src|tests|Modules|Features|docs|app)/[A-Za-z0-9._/-]+#', $line, $matches) < 1) {
                continue;
            }
            foreach ($matches[0] as $path) {
                $paths[$path] = true;
            }
        }

        $result = array_keys($paths);
        sort($result, SORT_STRING);

        return $result;
    }

    /**
     * @param list<string> $lines
     * @return list<string>
     */
    private function matchingLines(array $lines, string $pattern): array
    {
        $matches = [];
        foreach ($lines as $line) {
            $clean = trim(preg_replace('/\s+/', ' ', $line) ?? $line);
            if ($clean === '' || preg_match($pattern, $clean) !== 1) {
                continue;
            }
            $matches[] = substr($clean, 0, 180);
            if (count($matches) === 5) {
                break;
            }
        }

        return $matches;
    }

    /**
     * @param list<array{module:string,name:string,path:string,contents:string,title:string,evidence:array<string,mixed>}> $specs
     * @return array{path:string,status:string,appended_entries:list<string>,existing_entries:list<string>}
     */
    private function implementationLogState(array $specs): array
    {
        $path = 'Modules/implementation.log';
        $contents = is_file($this->paths->join($path)) ? (string) file_get_contents($this->paths->join($path)) : '';
        $existing = [];
        foreach (preg_split('/\R/', $contents) ?: [] as $line) {
            if (preg_match('/^- spec:\s*(.+)$/', $line, $match) === 1) {
                $existing[(string) $match[1]] = true;
            }
        }

        $appended = [];
        $already = [];
        foreach ($specs as $spec) {
            if (isset($existing[$spec['path']])) {
                $already[] = $spec['path'];
                continue;
            }
            $appended[] = $spec['path'];
        }

        return [
            'path' => $path,
            'status' => $appended === [] ? 'unchanged' : 'pending',
            'appended_entries' => $appended,
            'existing_entries' => $already,
        ];
    }

    /**
     * @param list<string> $entries
     */
    private function appendLogEntries(array $entries): void
    {
        $path = $this->paths->join('Modules/implementation.log');
        $existing = is_file($path) ? rtrim((string) file_get_contents($path)) : '';
        $lines = ['## Historical Imports'];
        foreach ($entries as $entry) {
            $lines[] = '- spec: ' . $entry;
            $lines[] = '- note: Historical imported spec reconstructed from archived records with explicit uncertainty markers.';
        }

        $next = ($existing === '' ? '' : $existing . "\n\n") . implode("\n", $lines) . "\n";
        file_put_contents($path, $next);
    }

    /**
     * @param array{module:string,name:string,path:string,contents:string,title:string,evidence:array<string,mixed>} $spec
     */
    private function notePath(array $spec): string
    {
        return 'Modules/' . $spec['module'] . '/outcomes/' . $spec['name'] . '.md';
    }

    /**
     * @param array{module:string,name:string,path:string,contents:string,title:string,evidence:array<string,mixed>} $spec
     */
    private function renderNote(array $spec): string
    {
        return $this->normalizeDocument(sprintf(
            <<<'MD'
# Implementation Plan: %s

## Historical Provenance

%s

- confirmed: imported spec path `%s`.
- inferred: historical implementation details are reconstructed from available embedded evidence and current repository state.

## Historical Specification Summary

- confirmed: `%s` was imported as a completed historical module execution spec.
- confirmed: canonical module `%s`.

## Historical Implementation Evidence

%s

## Historical Verification Evidence

%s

## Historical Stabilization Notes

%s

## Current Repository Alignment

%s

## Uncertainty And Reconstruction Notes

- inferred: exact original implementation ordering is unavailable unless explicitly present in embedded evidence.
- unknown: original full terminal transcript is not reproduced in this reconstruction note.
- inferred: generated summaries intentionally avoid duplicating entire archived Codex outputs verbatim.
MD,
            $spec['name'],
            self::PROVENANCE,
            $spec['path'],
            $spec['title'],
            $spec['module'],
            $this->implementationEvidence((array) $spec['evidence']),
            $this->verificationEvidence((array) $spec['evidence']),
            $this->stabilizationEvidence((array) $spec['evidence']),
            $this->currentRepositoryAlignment($spec),
        ));
    }

    /**
     * @param array<string,mixed> $evidence
     */
    private function implementationEvidence(array $evidence): string
    {
        $sections = (array) ($evidence['sections'] ?? []);
        if ($sections === []) {
            return 'No confirmed historical evidence available.';
        }

        $lines = [];
        foreach ($sections as $section) {
            $lines[] = '- confirmed: embedded `' . (string) ($section['heading'] ?? 'RESULT') . '` evidence was detected.';
            foreach ((array) ($section['summary'] ?? []) as $summary) {
                $lines[] = '  - inferred summary: ' . (string) $summary;
            }
            foreach ((array) ($section['paths'] ?? []) as $path) {
                $lines[] = '  - confirmed path reference: `' . (string) $path . '`';
            }
        }

        return implode("\n", $lines);
    }

    /**
     * @param array<string,mixed> $evidence
     */
    private function verificationEvidence(array $evidence): string
    {
        $lines = [];
        foreach ((array) ($evidence['sections'] ?? []) as $section) {
            foreach ((array) ($section['verification'] ?? []) as $verification) {
                $lines[] = '- confirmed: ' . (string) $verification;
            }
        }

        return $lines === [] ? 'No confirmed historical evidence available.' : implode("\n", $lines);
    }

    /**
     * @param array<string,mixed> $evidence
     */
    private function stabilizationEvidence(array $evidence): string
    {
        $lines = [];
        foreach ((array) ($evidence['sections'] ?? []) as $section) {
            foreach ((array) ($section['stabilization'] ?? []) as $note) {
                $lines[] = '- inferred: ' . (string) $note;
            }
        }

        return $lines === [] ? 'No confirmed historical evidence available.' : implode("\n", $lines);
    }

    /**
     * @param array{module:string,name:string,path:string,contents:string,title:string,evidence:array<string,mixed>} $spec
     */
    private function currentRepositoryAlignment(array $spec): string
    {
        $paths = [];
        foreach ((array) ($spec['evidence']['sections'] ?? []) as $section) {
            foreach ((array) ($section['paths'] ?? []) as $path) {
                $paths[(string) $path] = true;
            }
        }

        if ($paths === []) {
            return '- unknown: no file paths were confirmed from embedded historical evidence.';
        }

        $lines = [];
        foreach (array_keys($paths) as $path) {
            $exists = is_file($this->paths->join($path));
            $lines[] = '- ' . ($exists ? 'confirmed' : 'inferred') . ': `' . $path . '` ' . ($exists ? 'exists in the current repository.' : 'was referenced historically but is not present at that path.');
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
}
