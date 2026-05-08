<?php

declare(strict_types=1);

namespace Foundry\FeatureSystem;

use Foundry\Support\FoundryError;
use Foundry\Support\Paths;

final class HistoricalSpecEvidenceMapper
{
    /**
     * @var list<string>
     */
    private const SUPPORTING_EVIDENCE_FILES = [
        'Foundry-Spec-Summaries.md',
        'Foundry-Plans-for-19A-19F.md',
    ];

    /**
     * @var array{legacy_label:string,canonical_path:string,canonical_module:string,canonical_spec_id:string,canonical_slug:string,confidence:string,notes:list<string>}
     */
    private const DEFAULT_TRANSITION = [
        'legacy_label' => 'Spec35D1',
        'canonical_path' => 'docs/features/context-persistence/001-initial.md',
        'canonical_module' => 'ContextPersistence',
        'canonical_spec_id' => '001',
        'canonical_slug' => 'initial',
        'confidence' => 'high',
        'notes' => ['First known canonical numeric spec-file era anchor.'],
    ];

    public function __construct(
        private readonly Paths $paths,
    ) {}

    /**
     * @return array{
     *     status:string,
     *     dry_run:bool,
     *     write:bool,
     *     source_root:string,
     *     ordering_strategy:string,
     *     canonical_transition:array{legacy_label:string,canonical_path:string,current_canonical_path:string|null,confidence:string},
     *     counts:array{pre_canonical:int,canonical_existing:int,ambiguous:int,supporting_evidence:int},
     *     supporting_evidence_files:list<string>,
     *     candidates:list<array<string,mixed>>,
     *     outputs:array{evidence_map_json:string,evidence_map_markdown:string,written:bool}
     * }
     */
    public function build(
        string $sourcePath,
        ?string $anchorsPath,
        bool $withGitEvidence,
        bool $write,
        bool $dryRun,
    ): array {
        $sourceAbsolute = $this->absolutePath($sourcePath);
        if (!is_dir($sourceAbsolute)) {
            throw new FoundryError(
                'HISTORICAL_SPECS_EVIDENCE_SOURCE_DIRECTORY_MISSING',
                'validation',
                ['source' => $this->outputPath($sourceAbsolute)],
                'Historical spec evidence source directory is missing.',
            );
        }

        $anchorConfig = $this->loadAnchorConfig($anchorsPath);
        $transition = $anchorConfig['canonical_transition'];
        $files = $this->sourceFiles($sourceAbsolute);

        $supportingEvidenceFiles = [];
        $candidates = [];

        foreach ($files as $filePath) {
            $relativePath = $this->outputPath($filePath);
            $basename = basename($relativePath);
            $contents = file_get_contents($filePath);
            if (!is_string($contents)) {
                continue;
            }

            $isWebsiteSpec = $this->isWebsiteSpecFile($basename);
            $isSupporting = in_array($basename, self::SUPPORTING_EVIDENCE_FILES, true) || $isWebsiteSpec;
            if ($isSupporting) {
                $supportingEvidenceFiles[] = $relativePath;
            }

            $segments = $this->splitIntoSegments($contents);

            if ($segments === []) {
                if ($isSupporting) {
                    $candidates[] = $this->supportingEvidenceCandidate($relativePath, $basename, $isWebsiteSpec);
                    continue;
                }

                $fallback = $this->buildCandidate(
                    sourceFile: $relativePath,
                    sourceFilename: $basename,
                    segmentText: $this->normalizeText($contents),
                    segmentIndex: 1,
                    segmentTotal: 1,
                    anchors: $anchorConfig['anchors'],
                    transition: $transition,
                    withGitEvidence: $withGitEvidence,
                    isSupportingEvidence: $isSupporting,
                );

                if ($fallback !== null) {
                    $candidates[] = $fallback;
                }

                continue;
            }

            $segmentTotal = count($segments);
            foreach ($segments as $index => $segment) {
                $candidate = $this->buildCandidate(
                    sourceFile: $relativePath,
                    sourceFilename: $basename,
                    segmentText: (string) ($segment['text'] ?? ''),
                    segmentIndex: $index + 1,
                    segmentTotal: $segmentTotal,
                    anchors: $anchorConfig['anchors'],
                    transition: $transition,
                    withGitEvidence: $withGitEvidence,
                    isSupportingEvidence: $isSupporting,
                );

                if ($candidate !== null) {
                    $candidates[] = $candidate;
                }
            }
        }

        usort($supportingEvidenceFiles, static fn(string $a, string $b): int => strcmp($a, $b));
        $candidates = $this->sortCandidates($candidates);

        foreach ($candidates as $index => &$candidate) {
            $candidateId = sprintf('candidate-%03d', $index + 1);
            $candidate['candidate_id'] = $candidateId;
            if ((bool) ($candidate['result_detected'] ?? false)) {
                $candidate['result_file'] = $candidateId . '/result.md';
            }
            if ((bool) ($candidate['followups_detected'] ?? false)) {
                $candidate['followups_file'] = $candidateId . '/followups.md';
            }
        }
        unset($candidate);

        $counts = [
            'pre_canonical' => 0,
            'canonical_existing' => 0,
            'ambiguous' => 0,
            'supporting_evidence' => 0,
        ];

        foreach ($candidates as $candidate) {
            $era = (string) ($candidate['era'] ?? 'ambiguous');
            if (array_key_exists($era, $counts)) {
                $counts[$era]++;
            }
        }

        $transitionOutput = [
            'legacy_label' => (string) ($transition['legacy_label'] ?? self::DEFAULT_TRANSITION['legacy_label']),
            'canonical_path' => (string) ($transition['canonical_path'] ?? self::DEFAULT_TRANSITION['canonical_path']),
            'current_canonical_path' => $this->discoverCurrentCanonicalPath($transition),
            'confidence' => $this->normalizeConfidence((string) ($transition['confidence'] ?? self::DEFAULT_TRANSITION['confidence'])),
        ];

        $renderedCandidates = array_map(
            fn(array $candidate): array => $this->renderCandidate($candidate),
            $candidates,
        );

        $map = [
            'version' => 1,
            'source_root' => $this->outputPath($sourceAbsolute),
            'ordering_strategy' => 'legacy_label_then_filename_then_candidate',
            'canonical_transition' => $transitionOutput,
            'counts' => $counts,
            'supporting_evidence_files' => $supportingEvidenceFiles,
            'candidates' => $renderedCandidates,
        ];

        $jsonPath = $this->outputPath($sourceAbsolute . '/evidence-map.json');
        $markdownPath = $this->outputPath($sourceAbsolute . '/evidence-map.md');
        $didWrite = $write && !$dryRun;

        if ($didWrite) {
            file_put_contents(
                $sourceAbsolute . '/evidence-map.json',
                json_encode($map, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n",
            );
            file_put_contents(
                $sourceAbsolute . '/evidence-map.md',
                $this->renderMarkdownReport($map),
            );
        }

        return [
            'status' => 'ok',
            'dry_run' => $dryRun,
            'write' => $write,
            'source_root' => $map['source_root'],
            'ordering_strategy' => $map['ordering_strategy'],
            'canonical_transition' => $map['canonical_transition'],
            'counts' => $map['counts'],
            'supporting_evidence_files' => $map['supporting_evidence_files'],
            'candidates' => $map['candidates'],
            'outputs' => [
                'evidence_map_json' => $jsonPath,
                'evidence_map_markdown' => $markdownPath,
                'written' => $didWrite,
            ],
        ];
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

    /**
     * @return list<string>
     */
    private function sourceFiles(string $sourceAbsolute): array
    {
        $entries = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceAbsolute, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo instanceof \SplFileInfo || !$fileInfo->isFile()) {
                continue;
            }

            $path = str_replace('\\', '/', $fileInfo->getPathname());
            if (preg_match('#/candidate-\\d{3}/#', $path) === 1) {
                continue;
            }

            $basename = basename($path);
            if (in_array($basename, ['evidence-map.json', 'evidence-map.md'], true)) {
                continue;
            }

            $extension = strtolower((string) pathinfo($path, PATHINFO_EXTENSION));
            if (!in_array($extension, ['md', 'txt'], true)) {
                continue;
            }

            $entries[] = $path;
        }

        sort($entries, SORT_STRING);

        return $entries;
    }

    /**
     * @return array{canonical_transition:array<string,mixed>,anchors:array<string,array<string,mixed>>}
     */
    private function loadAnchorConfig(?string $anchorsPath): array
    {
        $transition = self::DEFAULT_TRANSITION;
        $anchors = [
            strtoupper(self::DEFAULT_TRANSITION['legacy_label']) => self::DEFAULT_TRANSITION,
        ];

        $candidate = trim((string) $anchorsPath);
        if ($candidate === '') {
            $candidate = '_import/historical-specs/import-anchors.json';
        }

        $path = $this->absolutePath($candidate);
        if (!is_file($path)) {
            return [
                'canonical_transition' => $transition,
                'anchors' => $anchors,
            ];
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (!is_array($decoded)) {
            return [
                'canonical_transition' => $transition,
                'anchors' => $anchors,
            ];
        }

        $transitionRow = $decoded['canonical_transition'] ?? null;
        if (is_array($transitionRow)) {
            $transition = $this->normalizeTransition($transitionRow, $transition);
            $anchors[strtoupper((string) $transition['legacy_label'])] = $transition;
        }

        foreach ((array) ($decoded['anchors'] ?? []) as $row) {
            if (!is_array($row)) {
                continue;
            }

            $label = $this->normalizeLegacyLabel((string) ($row['legacy_label'] ?? ''));
            if ($label === '') {
                continue;
            }

            $anchors[strtoupper($label)] = [
                'legacy_label' => $label,
                'canonical_path' => trim((string) ($row['canonical_path'] ?? '')),
                'canonical_module' => trim((string) ($row['canonical_module'] ?? '')),
                'canonical_spec_id' => trim((string) ($row['canonical_spec_id'] ?? '')),
                'canonical_slug' => trim((string) ($row['canonical_slug'] ?? '')),
                'confidence' => $this->normalizeConfidence((string) ($row['confidence'] ?? 'unknown')),
                'notes' => array_values(array_map('strval', (array) ($row['notes'] ?? []))),
            ];

            $singleNote = trim((string) ($row['notes'] ?? ''));
            if ($singleNote !== '' && $anchors[strtoupper($label)]['notes'] === []) {
                $anchors[strtoupper($label)]['notes'] = [$singleNote];
            }
        }

        return [
            'canonical_transition' => $transition,
            'anchors' => $anchors,
        ];
    }

    /**
     * @param array<string,mixed> $row
     * @param array<string,mixed> $fallback
     * @return array<string,mixed>
     */
    private function normalizeTransition(array $row, array $fallback): array
    {
        $label = $this->normalizeLegacyLabel((string) ($row['legacy_label'] ?? ($fallback['legacy_label'] ?? '')));
        if ($label === '') {
            $label = (string) ($fallback['legacy_label'] ?? self::DEFAULT_TRANSITION['legacy_label']);
        }

        $notes = array_values(array_map('strval', (array) ($row['notes'] ?? [])));
        if ($notes === []) {
            $single = trim((string) ($row['notes'] ?? ''));
            if ($single !== '') {
                $notes[] = $single;
            }
        }

        if ($notes === []) {
            $notes = (array) ($fallback['notes'] ?? self::DEFAULT_TRANSITION['notes']);
        }

        return [
            'legacy_label' => $label,
            'canonical_path' => trim((string) ($row['canonical_path'] ?? ($fallback['canonical_path'] ?? self::DEFAULT_TRANSITION['canonical_path']))),
            'canonical_module' => trim((string) ($row['canonical_module'] ?? ($fallback['canonical_module'] ?? self::DEFAULT_TRANSITION['canonical_module']))),
            'canonical_spec_id' => trim((string) ($row['canonical_spec_id'] ?? ($fallback['canonical_spec_id'] ?? self::DEFAULT_TRANSITION['canonical_spec_id']))),
            'canonical_slug' => trim((string) ($row['canonical_slug'] ?? ($fallback['canonical_slug'] ?? self::DEFAULT_TRANSITION['canonical_slug']))),
            'confidence' => $this->normalizeConfidence((string) ($row['confidence'] ?? ($fallback['confidence'] ?? self::DEFAULT_TRANSITION['confidence']))),
            'notes' => $notes,
        ];
    }

    /**
     * @param array<string,mixed> $transition
     */
    private function discoverCurrentCanonicalPath(array $transition): ?string
    {
        $module = trim((string) ($transition['canonical_module'] ?? ''));
        $specId = trim((string) ($transition['canonical_spec_id'] ?? ''));
        $slug = trim((string) ($transition['canonical_slug'] ?? ''));
        if ($module === '' || $specId === '' || $slug === '') {
            return null;
        }

        $path = $this->paths->join('Modules/' . $module . '/specs/' . $specId . '-' . $slug . '.md');
        if (is_file($path)) {
            return $this->outputPath($path);
        }

        return null;
    }

    /**
     * @return list<array{text:string}>
     */
    private function splitIntoSegments(string $contents): array
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $contents);
        $lines = explode("\n", $normalized);
        $boundaries = [];

        foreach ($lines as $index => $line) {
            if ($this->isBoundaryLine($line)) {
                $boundaries[] = $index;
            }
        }

        if ($boundaries === []) {
            return [];
        }

        $segments = [];
        for ($index = 0; $index < count($boundaries); $index++) {
            $start = $boundaries[$index];
            $end = ($boundaries[$index + 1] ?? count($lines)) - 1;
            if ($end < $start) {
                continue;
            }

            $segment = implode("\n", array_slice($lines, $start, ($end - $start) + 1));
            $segment = $this->normalizeText($segment);
            if ($segment === '') {
                continue;
            }

            $segments[] = ['text' => $segment];
        }

        return $segments;
    }

    private function isBoundaryLine(string $line): bool
    {
        $trimmed = trim($line);
        if ($trimmed === '') {
            return false;
        }

        if (preg_match('/^#{1,6}\s*Execution Spec\s*:/i', $trimmed) === 1) {
            return true;
        }

        if (preg_match('/^Execution Spec\s*:\s*[0-9]/i', $trimmed) === 1) {
            return true;
        }

        if (preg_match('/^#{0,6}\s*Spec\s*[0-9][0-9A-Za-z-]*/i', $trimmed) === 1) {
            return true;
        }

        return false;
    }

    private function normalizeText(string $value): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $value);
        $normalized = trim($normalized);
        if ($normalized === '') {
            return '';
        }

        return rtrim($normalized, "\n") . "\n";
    }

    private function isWebsiteSpecFile(string $basename): bool
    {
        return preg_match('/(?:^|[-_])WS\.md$/i', $basename) === 1
            || preg_match('/(?:^|[-_])website(?:[-_.]|$)/i', $basename) === 1;
    }

    /**
     * @return array<string,mixed>
     */
    private function supportingEvidenceCandidate(string $sourceFile, string $sourceFilename, bool $isWebsiteSpec = false): array
    {
        return [
            'candidate_id' => '',
            'source_file' => $sourceFile,
            'source_segment' => 1,
            'source_segments_total' => 1,
            'legacy_label' => '',
            'legacy_order_key' => '',
            'detected_title' => preg_replace('/\\.[A-Za-z0-9]+$/', '', $sourceFilename) ?: $sourceFilename,
            'era' => 'supporting_evidence',
            'import_action' => 'ignore_supporting',
            'canonical_transition_relative' => 'unknown',
            'suggested_module' => null,
            'suggested_spec_path' => '',
            'existing_spec_path' => '',
            'implemented' => false,
            'confidence' => 'unknown',
            'module_inference' => [
                'suggested_module' => null,
                'confidence' => 'low',
                'evidence' => [
                    [
                        'type' => 'supporting_file',
                        'value' => basename($sourceFile),
                        'confidence' => 'high',
                    ],
                ],
                'alternatives' => [],
            ],
            'evidence' => [
                'source_text' => 'confirmed',
                'codex_result' => 'unknown',
                'followups' => 'unknown',
                'git_commit' => 'unknown',
                'current_source' => 'unknown',
            ],
            'notes' => [$isWebsiteSpec
                ? 'Website historical spec; excluded from framework import.'
                : 'Supporting evidence source; not importable spec candidate by default.'],
            'result_detected' => false,
            'result_text' => null,
            'followups_detected' => false,
            'followups_text' => null,
            'git' => ['matched_commits' => []],
        ];
    }

    /**
     * @param array<string,array<string,mixed>> $anchors
     * @param array<string,mixed> $transition
     * @return array<string,mixed>|null
     */
    private function buildCandidate(
        string $sourceFile,
        string $sourceFilename,
        string $segmentText,
        int $segmentIndex,
        int $segmentTotal,
        array $anchors,
        array $transition,
        bool $withGitEvidence,
        bool $isSupportingEvidence,
    ): ?array {
        if (trim($segmentText) === '') {
            return null;
        }

        $internalLabel = $this->detectInternalLegacyLabel($segmentText);
        $filenameLabel = $this->detectFilenameLegacyLabel($sourceFilename);
        $legacyLabel = $internalLabel !== '' ? $internalLabel : $filenameLabel;
        $legacyOrderKey = $this->legacyOrderKey($legacyLabel);
        $title = $this->detectTitle($segmentText, $sourceFilename);

        if ($legacyLabel === '' && $title === '') {
            return null;
        }

        $notes = [];
        if ($internalLabel !== '' && $filenameLabel !== '' && $internalLabel !== $filenameLabel) {
            $notes[] = 'Filename label differs from internal segment label; internal heading used for candidate identity.';
        }

        $transitionRelative = $this->canonicalTransitionRelative($legacyLabel, (string) ($transition['legacy_label'] ?? self::DEFAULT_TRANSITION['legacy_label']));
        $era = $this->classifyEra($isSupportingEvidence, $transitionRelative, $legacyLabel);

        $resultSection = $this->extractSection($segmentText, ['RESULT', 'OUTPUT']);
        $followupsSection = $this->extractSection($segmentText, ['FOLLOWUPS', 'FOLLOW-UPS', 'FOLLOW UPS']);

        $gitEvidence = $withGitEvidence
            ? $this->gitEvidence($legacyLabel, $title)
            : ['matched_commits' => [], 'touched_paths' => []];

        $moduleInference = $this->inferModule($title, $segmentText, $sourceFilename, $gitEvidence);

        $anchor = $legacyLabel === '' ? null : ($anchors[strtoupper($legacyLabel)] ?? null);
        $suggestedSpecPath = '';
        if (is_array($anchor)) {
            $moduleInference['suggested_module'] = trim((string) ($anchor['canonical_module'] ?? $moduleInference['suggested_module']));
            $moduleInference['confidence'] = $this->normalizeConfidence((string) ($anchor['confidence'] ?? $moduleInference['confidence']));
            $suggestedSpecPath = $this->anchorSpecPath($anchor);
            $notes[] = 'Anchor matched: ' . $legacyLabel;
            foreach ((array) ($anchor['notes'] ?? []) as $note) {
                $noteText = trim((string) $note);
                if ($noteText !== '') {
                    $notes[] = $noteText;
                }
            }
        }

        $existingSpecPath = '';
        if ($era === 'canonical_existing') {
            $existingSpecPath = $this->resolveExistingSpecPath($legacyLabel, $title, $anchor, $transition, $moduleInference);
        }

        $importAction = $this->determineImportAction($era, $moduleInference, $existingSpecPath);

        if ($era === 'pre_canonical' && $importAction === 'review' && $moduleInference['suggested_module'] === null) {
            $notes[] = 'No confident module inference; candidate requires review before import.';
        }

        if ($isSupportingEvidence) {
            $notes[] = 'Supporting or website-owned source; excluded from framework import.';
        }

        if ($segmentTotal > 1) {
            $notes[] = 'Multi-spec source file segment.';
        }

        if ($legacyLabel === '') {
            $notes[] = 'Legacy label not detected.';
        }

        if ($legacyOrderKey === '') {
            $notes[] = 'Legacy ordering key unknown; filename fallback applied.';
        }

        $evidence = [
            'source_text' => 'confirmed',
            'codex_result' => $resultSection === null ? 'unknown' : 'confirmed',
            'followups' => $followupsSection === null ? 'unknown' : 'confirmed',
            'git_commit' => $gitEvidence['matched_commits'] === [] ? 'unknown' : 'inferred',
            'current_source' => is_array($anchor) ? 'inferred' : 'unknown',
        ];

        if ((bool) ($moduleInference['conflict'] ?? false)) {
            $evidence['current_source'] = 'conflict';
            $notes[] = 'Conflicting module inference evidence; marked for review.';
            $importAction = 'review';
        }

        $topConfidence = $this->normalizeConfidence((string) $moduleInference['confidence']);
        if ($era === 'supporting_evidence') {
            $topConfidence = 'unknown';
        } elseif ($era === 'canonical_existing' && $existingSpecPath === '') {
            $topConfidence = 'low';
        }

        $implemented = preg_match('/\b(implemented|implementation complete|completed)\b/i', $segmentText) === 1;

        return [
            'candidate_id' => '',
            'source_file' => $sourceFile,
            'source_segment' => $segmentIndex,
            'source_segments_total' => $segmentTotal,
            'legacy_label' => $legacyLabel,
            'legacy_order_key' => $legacyOrderKey,
            'detected_title' => $title,
            'era' => $era,
            'import_action' => $importAction,
            'canonical_transition_relative' => $transitionRelative,
            'suggested_module' => $moduleInference['suggested_module'],
            'suggested_spec_path' => $suggestedSpecPath,
            'existing_spec_path' => $existingSpecPath,
            'implemented' => $implemented,
            'confidence' => $topConfidence,
            'module_inference' => [
                'suggested_module' => $moduleInference['suggested_module'],
                'confidence' => $this->normalizeConfidence((string) $moduleInference['confidence']),
                'evidence' => $moduleInference['evidence'],
                'alternatives' => $moduleInference['alternatives'],
            ],
            'evidence' => $evidence,
            'notes' => $this->dedupeNotes($notes),
            'result_detected' => $resultSection !== null,
            'result_text' => $resultSection,
            'followups_detected' => $followupsSection !== null,
            'followups_text' => $followupsSection,
            'git' => ['matched_commits' => $gitEvidence['matched_commits']],
        ];
    }

    private function detectInternalLegacyLabel(string $segmentText): string
    {
        if (preg_match('/Execution Spec\s*:\s*([A-Za-z0-9.-]+)/i', $segmentText, $match) === 1) {
            return $this->normalizeLegacyLabel((string) $match[1]);
        }

        if (preg_match('/(?:^|\n)\s*#{0,6}\s*Spec\s*([A-Za-z0-9.-]+(?:-[0-9]+)?)/i', $segmentText, $match) === 1) {
            return $this->normalizeLegacyLabel((string) $match[1]);
        }

        return '';
    }

    private function detectFilenameLegacyLabel(string $sourceFilename): string
    {
        if (preg_match('/Foundry-Spec[-_ ]*([0-9][0-9A-Za-z]*(?:-[0-9]+)?)/i', $sourceFilename, $match) !== 1) {
            return '';
        }

        return $this->normalizeLegacyLabel((string) $match[1]);
    }

    private function normalizeLegacyLabel(string $value): string
    {
        $raw = strtoupper(trim($value));
        $raw = preg_replace('/^SPEC\s*/', '', $raw) ?? $raw;
        $raw = trim($raw);
        if ($raw === '') {
            return '';
        }

        if (preg_match('/^[0-9][0-9A-Z]*(?:-[0-9]+)?$/', $raw) !== 1) {
            return '';
        }

        return 'Spec' . $raw;
    }

    private function detectTitle(string $segmentText, string $sourceFilename): string
    {
        if (preg_match('/^Title\s*:\s*(.+)$/im', $segmentText, $match) === 1) {
            return trim((string) $match[1]);
        }

        if (preg_match('/^#{1,6}\s*(.+)$/m', $segmentText, $match) === 1) {
            $heading = trim((string) $match[1]);
            $heading = preg_replace('/^Execution Spec\s*:\s*/i', '', $heading) ?? $heading;
            $heading = preg_replace('/^Spec\s*[0-9][0-9A-Za-z.-]*(?:-[0-9]+)?\s*[:\-]?\s*/i', '', $heading) ?? $heading;
            $heading = trim($heading);
            if ($heading !== '') {
                return $heading;
            }
        }

        $base = preg_replace('/\.[A-Za-z0-9]+$/', '', $sourceFilename) ?? $sourceFilename;
        $base = preg_replace('/^Foundry-Spec[-_ ]*/i', '', $base) ?? $base;
        $base = trim(str_replace(['_', '-'], ' ', $base));

        return $base;
    }

    private function legacyOrderKey(string $legacyLabel): string
    {
        $tokens = $this->legacyOrderTokens($legacyLabel);
        if ($tokens === null) {
            return '';
        }

        $rendered = [];
        foreach ($tokens as $token) {
            if (is_int($token)) {
                $rendered[] = sprintf('%03d', $token);
            } else {
                $rendered[] = (string) $token;
            }
        }

        return implode('.', $rendered);
    }

    /**
     * @return list<int|string>|null
     */
    private function legacyOrderTokens(string $legacyLabel): ?array
    {
        if ($legacyLabel === '') {
            return null;
        }

        $suffix = strtoupper(trim($legacyLabel));
        $suffix = preg_replace('/^SPEC\s*/', '', $suffix) ?? $suffix;
        if ($suffix === '' || preg_match('/^[0-9]/', $suffix) !== 1) {
            return null;
        }

        if (preg_match('/^[0-9][0-9A-Z]*(?:-[0-9]+)?$/', $suffix) !== 1) {
            return null;
        }

        $parts = explode('-', $suffix);
        $base = (string) ($parts[0] ?? '');
        $suffixNumber = isset($parts[1]) ? (int) $parts[1] : null;

        $tokens = [];
        $buffer = '';
        $mode = null;

        foreach (str_split($base) as $char) {
            $type = ctype_digit($char) ? 'digit' : 'alpha';
            if ($mode !== null && $mode !== $type) {
                $this->flushLegacyToken($tokens, $buffer, $mode);
                $buffer = '';
            }

            $mode = $type;
            $buffer .= $char;
        }

        if ($buffer !== '' && $mode !== null) {
            $this->flushLegacyToken($tokens, $buffer, $mode);
        }

        if ($tokens === [] || !is_int($tokens[0])) {
            return null;
        }

        if ($suffixNumber !== null) {
            $tokens[] = $suffixNumber;
        }

        return $tokens;
    }

    /**
     * @param list<int|string> $tokens
     */
    private function flushLegacyToken(array &$tokens, string $buffer, string $mode): void
    {
        if ($mode === 'digit') {
            $tokens[] = (int) $buffer;
            return;
        }

        foreach (str_split(strtoupper($buffer)) as $char) {
            $tokens[] = $char;
        }
    }

    private function canonicalTransitionRelative(string $legacyLabel, string $transitionLabel): string
    {
        $candidate = $this->legacyOrderTokens($legacyLabel);
        $transition = $this->legacyOrderTokens($transitionLabel);
        if ($candidate === null || $transition === null) {
            return 'unknown';
        }

        $comparison = $this->compareLegacyTokens($candidate, $transition);

        return match (true) {
            $comparison < 0 => 'before',
            $comparison === 0 => 'at',
            $comparison > 0 => 'after',
        };
    }

    /**
     * @param list<int|string> $left
     * @param list<int|string> $right
     */
    private function compareLegacyTokens(array $left, array $right): int
    {
        $max = max(count($left), count($right));
        for ($index = 0; $index < $max; $index++) {
            if (!array_key_exists($index, $left)) {
                return -1;
            }
            if (!array_key_exists($index, $right)) {
                return 1;
            }

            $a = $left[$index];
            $b = $right[$index];
            if (is_int($a) && is_int($b)) {
                if ($a !== $b) {
                    return $a <=> $b;
                }
                continue;
            }

            if (is_int($a) && !is_int($b)) {
                return -1;
            }
            if (!is_int($a) && is_int($b)) {
                return 1;
            }

            $cmp = strcmp((string) $a, (string) $b);
            if ($cmp !== 0) {
                return $cmp;
            }
        }

        return 0;
    }

    private function classifyEra(bool $isSupportingEvidence, string $transitionRelative, string $legacyLabel): string
    {
        if ($isSupportingEvidence) {
            return 'supporting_evidence';
        }

        if ($legacyLabel === '' || $transitionRelative === 'unknown') {
            return 'ambiguous';
        }

        return match ($transitionRelative) {
            'before' => 'pre_canonical',
            'at', 'after' => 'canonical_existing',
            default => 'ambiguous',
        };
    }

    /**
     * @param array{matched_commits:list<array{hash:string,subject:string,confidence:string,touched_paths:list<string>}>,touched_paths:list<string>} $gitEvidence
     * @return array{suggested_module:string|null,confidence:string,evidence:list<array{type:string,value:string,confidence:string}>,alternatives:list<array{module:string,confidence:string,reason:string}>,conflict:bool}
     */
    private function inferModule(string $title, string $segmentText, string $sourceFilename, array $gitEvidence): array
    {
        $signals = [];

        $haystack = strtolower($title . "\n" . $segmentText . "\n" . $sourceFilename);
        $keywordMap = [
            ['needle' => 'context persistence', 'module' => 'ContextPersistence', 'confidence' => 'high', 'type' => 'title_keyword'],
            ['needle' => 'marketplace', 'module' => 'Marketplace', 'confidence' => 'high', 'type' => 'title_keyword'],
            ['needle' => 'mcp', 'module' => 'McpServer', 'confidence' => 'medium', 'type' => 'title_keyword'],
            ['needle' => 'generate', 'module' => 'GenerateEngine', 'confidence' => 'medium', 'type' => 'title_keyword'],
            ['needle' => 'quality', 'module' => 'QualityEnforcement', 'confidence' => 'medium', 'type' => 'title_keyword'],
            ['needle' => 'state store', 'module' => 'StateStore', 'confidence' => 'medium', 'type' => 'title_keyword'],
            ['needle' => 'sqlite', 'module' => 'StateStore', 'confidence' => 'medium', 'type' => 'title_keyword'],
            ['needle' => 'extension', 'module' => 'ExtensionSystem', 'confidence' => 'medium', 'type' => 'title_keyword'],
            ['needle' => 'feature system', 'module' => 'FeatureSystem', 'confidence' => 'medium', 'type' => 'title_keyword'],
            ['needle' => 'cli', 'module' => 'CliExperience', 'confidence' => 'low', 'type' => 'title_keyword'],
            ['needle' => 'compiler', 'module' => 'CompilerDeterminism', 'confidence' => 'medium', 'type' => 'title_keyword'],
        ];

        foreach ($keywordMap as $row) {
            if (!str_contains($haystack, (string) $row['needle'])) {
                continue;
            }

            $signals[] = [
                'module' => (string) $row['module'],
                'type' => (string) $row['type'],
                'value' => (string) $row['needle'],
                'confidence' => (string) $row['confidence'],
                'reason' => 'Keyword evidence.',
            ];
        }

        $pathSignals = [
            'src/context/' => 'ContextPersistence',
            'src/generate/' => 'GenerateEngine',
            'src/marketplace/' => 'Marketplace',
            'src/mcp/' => 'McpServer',
            'src/quality/' => 'QualityEnforcement',
            'src/state/' => 'StateStore',
            'src/featuresystem/' => 'FeatureSystem',
            'src/cli/' => 'CliExperience',
        ];

        foreach ($pathSignals as $path => $module) {
            if (!str_contains($haystack, $path)) {
                continue;
            }

            $signals[] = [
                'module' => $module,
                'type' => 'path_hint',
                'value' => $path,
                'confidence' => 'medium',
                'reason' => 'Path mention evidence.',
            ];
        }

        foreach ((array) ($gitEvidence['touched_paths'] ?? []) as $path) {
            $normalized = strtolower((string) $path);
            foreach ($pathSignals as $prefix => $module) {
                if (!str_starts_with($normalized, $prefix)) {
                    continue;
                }

                $signals[] = [
                    'module' => $module,
                    'type' => 'git_touched_path',
                    'value' => (string) $path,
                    'confidence' => 'medium',
                    'reason' => 'Git touched-path evidence.',
                ];
            }
        }

        if ($signals === []) {
            return [
                'suggested_module' => null,
                'confidence' => 'low',
                'evidence' => [],
                'alternatives' => [],
                'conflict' => false,
            ];
        }

        $weights = ['high' => 3, 'medium' => 2, 'low' => 1];
        $scores = [];
        foreach ($signals as $signal) {
            $module = (string) $signal['module'];
            $weight = $weights[(string) $signal['confidence']] ?? 1;
            $scores[$module] = ($scores[$module] ?? 0) + $weight;
        }

        $modules = array_keys($scores);
        usort($modules, static function (string $a, string $b) use ($scores): int {
            $scoreCmp = $scores[$b] <=> $scores[$a];
            if ($scoreCmp !== 0) {
                return $scoreCmp;
            }

            return strcmp($a, $b);
        });

        $best = $modules[0] ?? null;
        $bestScore = $best === null ? 0 : (int) ($scores[$best] ?? 0);
        $second = $modules[1] ?? null;
        $secondScore = $second === null ? -1 : (int) ($scores[$second] ?? -1);
        $conflict = $best !== null && $second !== null && $bestScore === $secondScore;

        $confidence = match (true) {
            $bestScore >= 5 => 'high',
            $bestScore >= 3 => 'medium',
            default => 'low',
        };

        $evidenceRows = [];
        foreach ($signals as $signal) {
            if ((string) $signal['module'] !== (string) $best) {
                continue;
            }

            $evidenceRows[] = [
                'type' => (string) $signal['type'],
                'value' => (string) $signal['value'],
                'confidence' => (string) $signal['confidence'],
            ];
        }

        usort($evidenceRows, static function (array $a, array $b): int {
            $typeCmp = strcmp((string) ($a['type'] ?? ''), (string) ($b['type'] ?? ''));
            if ($typeCmp !== 0) {
                return $typeCmp;
            }

            return strcmp((string) ($a['value'] ?? ''), (string) ($b['value'] ?? ''));
        });

        $alternatives = [];
        foreach (array_slice($modules, 1) as $module) {
            $score = (int) ($scores[$module] ?? 0);
            $altConfidence = match (true) {
                $score >= 5 => 'high',
                $score >= 3 => 'medium',
                default => 'low',
            };

            $alternatives[] = [
                'module' => $module,
                'confidence' => $altConfidence,
                'reason' => 'Secondary evidence score=' . $score . '.',
            ];
        }

        return [
            'suggested_module' => $best,
            'confidence' => $confidence,
            'evidence' => $evidenceRows,
            'alternatives' => $alternatives,
            'conflict' => $conflict,
        ];
    }

    /**
     * @param array<string,mixed>|null $anchor
     * @param array<string,mixed> $transition
     * @param array{suggested_module:string|null,confidence:string,evidence:list<array{type:string,value:string,confidence:string}>,alternatives:list<array{module:string,confidence:string,reason:string}>,conflict:bool} $moduleInference
     */
    private function resolveExistingSpecPath(
        string $legacyLabel,
        string $title,
        ?array $anchor,
        array $transition,
        array $moduleInference,
    ): string {
        if (is_array($anchor)) {
            $path = $this->anchorSpecPath($anchor);
            if ($path !== '') {
                $absolute = $this->paths->join($path);
                if (is_file($absolute)) {
                    return $path;
                }
            }

            $anchorCanonicalPath = trim((string) ($anchor['canonical_path'] ?? ''));
            if ($anchorCanonicalPath !== '' && is_file($this->paths->join($anchorCanonicalPath))) {
                return $anchorCanonicalPath;
            }
        }

        $transitionLabel = (string) ($transition['legacy_label'] ?? self::DEFAULT_TRANSITION['legacy_label']);
        if (strcasecmp($legacyLabel, $transitionLabel) === 0) {
            $current = $this->discoverCurrentCanonicalPath($transition);
            if ($current !== null && $current !== '') {
                return $current;
            }

            $legacyPath = (string) ($transition['canonical_path'] ?? self::DEFAULT_TRANSITION['canonical_path']);
            if ($legacyPath !== '' && is_file($this->paths->join($legacyPath))) {
                return $legacyPath;
            }
        }

        $module = $moduleInference['suggested_module'];
        if (!is_string($module) || $module === '') {
            return '';
        }

        if (preg_match('/^([0-9]+(?:\.[0-9]+)*)-([a-z0-9][a-z0-9-]*)$/i', strtolower($title), $match) === 1) {
            $candidate = 'Modules/' . $module . '/specs/' . $match[1] . '-' . $match[2] . '.md';
            if (is_file($this->paths->join($candidate))) {
                return $candidate;
            }
        }

        return '';
    }

    /**
     * @param array<string,mixed> $anchor
     */
    private function anchorSpecPath(array $anchor): string
    {
        $module = trim((string) ($anchor['canonical_module'] ?? ''));
        $specId = trim((string) ($anchor['canonical_spec_id'] ?? ''));
        $slug = trim((string) ($anchor['canonical_slug'] ?? ''));

        if ($module === '' || $specId === '' || $slug === '') {
            return '';
        }

        return 'Modules/' . $module . '/specs/' . $specId . '-' . $slug . '.md';
    }

    /**
     * @param array{suggested_module:string|null,confidence:string,evidence:list<array{type:string,value:string,confidence:string}>,alternatives:list<array{module:string,confidence:string,reason:string}>,conflict:bool} $moduleInference
     */
    private function determineImportAction(string $era, array $moduleInference, string $existingSpecPath): string
    {
        if ($era === 'supporting_evidence') {
            return 'ignore_supporting';
        }

        if ($era === 'ambiguous') {
            return 'review';
        }

        if ((bool) ($moduleInference['conflict'] ?? false)) {
            return 'review';
        }

        if ($era === 'canonical_existing') {
            return $existingSpecPath !== '' ? 'link_existing' : 'review';
        }

        $module = $moduleInference['suggested_module'];
        $confidence = $this->normalizeConfidence((string) $moduleInference['confidence']);
        if (is_string($module) && $module !== '' && in_array($confidence, ['high', 'medium'], true)) {
            return 'import';
        }

        return 'review';
    }

    /**
     * @param list<string> $headings
     */
    private function extractSection(string $text, array $headings): ?string
    {
        $lines = preg_split('/\n/', $text) ?: [];
        $start = null;
        foreach ($lines as $index => $line) {
            $trimmed = trim($line);
            foreach ($headings as $heading) {
                $pattern = '/^#{0,6}\s*' . preg_quote($heading, '/') . '\s*:?/i';
                if (preg_match($pattern, $trimmed) === 1) {
                    $start = $index + 1;
                    break 2;
                }
            }
        }

        if ($start === null) {
            return null;
        }

        $collected = [];
        for ($index = $start; $index < count($lines); $index++) {
            $line = $lines[$index];
            if (preg_match('/^#{1,6}\s+[A-Za-z]/', trim($line)) === 1) {
                break;
            }
            $collected[] = $line;
        }

        $section = trim(implode("\n", $collected));
        if ($section === '') {
            return null;
        }

        return $section . "\n";
    }

    private function normalizeConfidence(string $value): string
    {
        $normalized = strtolower(trim($value));
        if (in_array($normalized, ['high', 'medium', 'low', 'unknown'], true)) {
            return $normalized;
        }

        return 'unknown';
    }

    /**
     * @return array{matched_commits:list<array{hash:string,subject:string,confidence:string,touched_paths:list<string>}>,touched_paths:list<string>}
     */
    private function gitEvidence(string $legacyLabel, string $title): array
    {
        $root = $this->paths->root();
        $command = sprintf(
            'cd %s && git log --max-count=300 --format=%%H%%x09%%s 2>/dev/null',
            escapeshellarg($root),
        );
        $output = shell_exec($command);
        if (!is_string($output) || trim($output) === '') {
            return ['matched_commits' => [], 'touched_paths' => []];
        }

        $matches = [];
        $paths = [];

        $labelNeedle = strtolower($legacyLabel);
        $titleNeedle = strtolower(trim($title));

        foreach (preg_split('/\n/', trim($output)) ?: [] as $line) {
            if (trim($line) === '') {
                continue;
            }

            [$hash, $subject] = array_pad(explode("\t", $line, 2), 2, '');
            $hash = trim($hash);
            $subject = trim($subject);
            if ($hash === '' || $subject === '') {
                continue;
            }

            $subjectLower = strtolower($subject);
            $confidence = null;
            if ($labelNeedle !== '' && str_contains($subjectLower, $labelNeedle)) {
                $confidence = 'medium';
            } elseif ($titleNeedle !== '' && str_contains($subjectLower, $titleNeedle)) {
                $confidence = 'low';
            }

            if ($confidence === null) {
                continue;
            }

            $touched = $this->gitTouchedPaths($hash);
            foreach ($touched as $path) {
                $paths[$path] = true;
            }

            $matches[] = [
                'hash' => substr($hash, 0, 7),
                'subject' => $subject,
                'confidence' => $confidence,
                'touched_paths' => $touched,
            ];

            if (count($matches) === 5) {
                break;
            }
        }

        ksort($paths);

        return [
            'matched_commits' => $matches,
            'touched_paths' => array_keys($paths),
        ];
    }

    /**
     * @return list<string>
     */
    private function gitTouchedPaths(string $hash): array
    {
        $command = sprintf(
            'cd %s && git show --name-only --format= %s 2>/dev/null',
            escapeshellarg($this->paths->root()),
            escapeshellarg($hash),
        );
        $output = shell_exec($command);
        if (!is_string($output) || trim($output) === '') {
            return [];
        }

        $rows = [];
        foreach (preg_split('/\n/', trim($output)) ?: [] as $line) {
            $path = trim((string) $line);
            if ($path === '') {
                continue;
            }

            $rows[] = str_replace('\\', '/', $path);
        }

        sort($rows, SORT_STRING);

        return array_values(array_unique($rows));
    }

    /**
     * @param list<string> $notes
     * @return list<string>
     */
    private function dedupeNotes(array $notes): array
    {
        $unique = [];
        foreach ($notes as $note) {
            $trimmed = trim((string) $note);
            if ($trimmed === '') {
                continue;
            }

            $unique[$trimmed] = true;
        }

        return array_keys($unique);
    }

    /**
     * @param list<array<string,mixed>> $candidates
     * @return list<array<string,mixed>>
     */
    private function sortCandidates(array $candidates): array
    {
        usort($candidates, function (array $a, array $b): int {
            $aKnown = (string) ($a['legacy_order_key'] ?? '') !== '';
            $bKnown = (string) ($b['legacy_order_key'] ?? '') !== '';
            if ($aKnown !== $bKnown) {
                return $aKnown ? -1 : 1;
            }

            $aKey = (string) ($a['legacy_order_key'] ?? '');
            $bKey = (string) ($b['legacy_order_key'] ?? '');
            if ($aKey !== $bKey) {
                return strcmp($aKey, $bKey);
            }

            $aFile = (string) ($a['source_file'] ?? '');
            $bFile = (string) ($b['source_file'] ?? '');
            if ($aFile !== $bFile) {
                return strcmp($aFile, $bFile);
            }

            return ((int) ($a['source_segment'] ?? 1)) <=> ((int) ($b['source_segment'] ?? 1));
        });

        return $candidates;
    }

    /**
     * @param array<string,mixed> $candidate
     * @return array<string,mixed>
     */
    private function renderCandidate(array $candidate): array
    {
        $row = [
            'candidate_id' => (string) ($candidate['candidate_id'] ?? ''),
            'source_file' => (string) ($candidate['source_file'] ?? ''),
            'legacy_label' => (string) ($candidate['legacy_label'] ?? ''),
            'legacy_order_key' => (string) ($candidate['legacy_order_key'] ?? ''),
            'detected_title' => (string) ($candidate['detected_title'] ?? ''),
            'era' => (string) ($candidate['era'] ?? 'ambiguous'),
            'import_action' => (string) ($candidate['import_action'] ?? 'review'),
            'canonical_transition_relative' => (string) ($candidate['canonical_transition_relative'] ?? 'unknown'),
            'suggested_module' => $candidate['suggested_module'] ?? null,
            'suggested_spec_path' => (string) ($candidate['suggested_spec_path'] ?? ''),
            'existing_spec_path' => (string) ($candidate['existing_spec_path'] ?? ''),
            'implemented' => (bool) ($candidate['implemented'] ?? false),
            'confidence' => (string) ($candidate['confidence'] ?? 'unknown'),
            'module_inference' => (array) ($candidate['module_inference'] ?? []),
            'evidence' => (array) ($candidate['evidence'] ?? []),
            'notes' => array_values(array_map('strval', (array) ($candidate['notes'] ?? []))),
            'source_segment' => (int) ($candidate['source_segment'] ?? 1),
            'source_segments_total' => (int) ($candidate['source_segments_total'] ?? 1),
            'result_detected' => (bool) ($candidate['result_detected'] ?? false),
            'followups_detected' => (bool) ($candidate['followups_detected'] ?? false),
            'git' => (array) ($candidate['git'] ?? ['matched_commits' => []]),
        ];

        if ((bool) ($candidate['result_detected'] ?? false)) {
            $row['result_file'] = (string) ($candidate['result_file'] ?? '');
        }
        if ((bool) ($candidate['followups_detected'] ?? false)) {
            $row['followups_file'] = (string) ($candidate['followups_file'] ?? '');
        }

        return $row;
    }

    /**
     * @param array<string,mixed> $map
     */
    private function renderMarkdownReport(array $map): string
    {
        $lines = [
            '# Historical Evidence Map',
            '',
            '- version: ' . (string) ($map['version'] ?? 1),
            '- source_root: ' . (string) ($map['source_root'] ?? ''),
            '- ordering_strategy: ' . (string) ($map['ordering_strategy'] ?? ''),
            '- transition_anchor: ' . (string) (($map['canonical_transition']['legacy_label'] ?? '') ?: ''),
            '- pre_canonical: ' . (int) ($map['counts']['pre_canonical'] ?? 0),
            '- canonical_existing: ' . (int) ($map['counts']['canonical_existing'] ?? 0),
            '- ambiguous: ' . (int) ($map['counts']['ambiguous'] ?? 0),
            '- supporting_evidence: ' . (int) ($map['counts']['supporting_evidence'] ?? 0),
            '',
            '| Candidate | Legacy Label | Era | Action | Suggested Module | Confidence | Source |',
            '|---|---|---|---|---|---|---|',
        ];

        foreach ((array) ($map['candidates'] ?? []) as $candidate) {
            if (!is_array($candidate)) {
                continue;
            }

            $lines[] = sprintf(
                '| %s | %s | %s | %s | %s | %s | %s |',
                (string) ($candidate['candidate_id'] ?? ''),
                (string) ($candidate['legacy_label'] ?? ''),
                (string) ($candidate['era'] ?? ''),
                (string) ($candidate['import_action'] ?? ''),
                (string) (($candidate['suggested_module'] ?? null) ?? ''),
                (string) ($candidate['confidence'] ?? ''),
                (string) ($candidate['source_file'] ?? ''),
            );
        }

        $lines[] = '';

        return implode("\n", $lines);
    }
}
