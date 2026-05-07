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

        $anchors = $this->loadAnchors($anchorsPath);
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

            $isSupporting = in_array($basename, self::SUPPORTING_EVIDENCE_FILES, true);
            if ($isSupporting) {
                $supportingEvidenceFiles[] = $relativePath;
            }

            $segments = $this->splitIntoSegments($contents);
            if ($segments === []) {
                $fallbackLabel = $this->detectLegacyLabel('', $basename);
                if ($fallbackLabel === '') {
                    continue;
                }

                $segments = [$this->normalizeText($contents)];
            }

            if ($isSupporting && !$this->containsExecutionSpecHeading($contents)) {
                continue;
            }

            $segmentTotal = count($segments);
            foreach ($segments as $index => $segment) {
                $candidate = $this->buildCandidate(
                    sourceFile: $relativePath,
                    sourceFilename: $basename,
                    segmentText: $segment,
                    segmentIndex: $index + 1,
                    segmentTotal: $segmentTotal,
                    anchors: $anchors,
                    withGitEvidence: $withGitEvidence,
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

        $renderedCandidates = array_map(
            fn(array $candidate): array => $this->renderCandidate($candidate),
            $candidates,
        );

        $map = [
            'version' => 1,
            'source_root' => $this->outputPath($sourceAbsolute),
            'ordering_strategy' => 'legacy_label_then_filename_then_candidate',
            'supporting_evidence_files' => $supportingEvidenceFiles,
            'candidates' => $renderedCandidates,
        ];

        $jsonPath = $this->outputPath($sourceAbsolute . '/evidence-map.json');
        $markdownPath = $this->outputPath($sourceAbsolute . '/evidence-map.md');
        $didWrite = $write && !$dryRun;
        if ($didWrite) {
            file_put_contents(
                $sourceAbsolute . '/evidence-map.json',
                json_encode($map, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR) . "\n",
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
            if (str_contains($path, '/candidate-')) {
                continue;
            }

            $extension = strtolower((string) pathinfo($path, \PATHINFO_EXTENSION));
            if (!in_array($extension, ['md', 'txt'], true)) {
                continue;
            }

            $entries[] = $path;
        }

        sort($entries, \SORT_STRING);

        return $entries;
    }

    /**
     * @return array<string,array{
     *     canonical_module:string,
     *     canonical_spec_id:string,
     *     canonical_slug:string,
     *     confidence:string,
     *     notes:string
     * }>
     */
    private function loadAnchors(?string $anchorsPath): array
    {
        $candidate = trim((string) $anchorsPath);
        if ($candidate === '') {
            $candidate = '_import/historical-specs/import-anchors.json';
        }

        $path = $this->absolutePath($candidate);
        if (!is_file($path)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($path), true);
        if (!is_array($decoded)) {
            return [];
        }

        $anchors = [];
        foreach ((array) ($decoded['anchors'] ?? []) as $row) {
            if (!is_array($row)) {
                continue;
            }

            $label = strtoupper(trim((string) ($row['legacy_label'] ?? '')));
            if ($label === '') {
                continue;
            }

            $anchors[$label] = [
                'canonical_module' => trim((string) ($row['canonical_module'] ?? '')),
                'canonical_spec_id' => trim((string) ($row['canonical_spec_id'] ?? '')),
                'canonical_slug' => trim((string) ($row['canonical_slug'] ?? '')),
                'confidence' => $this->normalizeConfidence((string) ($row['confidence'] ?? 'unknown')),
                'notes' => trim((string) ($row['notes'] ?? '')),
            ];
        }

        return $anchors;
    }

    /**
     * @return list<string>
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

        if ($boundaries[0] !== 0) {
            array_unshift($boundaries, 0);
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
            if ($segment !== '') {
                $segments[] = $segment;
            }
        }

        return $segments;
    }

    private function containsExecutionSpecHeading(string $contents): bool
    {
        return preg_match('/Execution Spec\s*:/i', $contents) === 1;
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

        if (preg_match('/^Execution Spec\s*:/i', $trimmed) === 1) {
            return true;
        }

        if (preg_match('/^#{1,6}\s*Spec(?:\s|:|$)/i', $trimmed) === 1) {
            return true;
        }

        return preg_match('/^Spec\s*[0-9][0-9A-Za-z-]*/i', $trimmed) === 1;
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

    /**
     * @param array<string,array{
     *     canonical_module:string,
     *     canonical_spec_id:string,
     *     canonical_slug:string,
     *     confidence:string,
     *     notes:string
     * }> $anchors
     * @return array<string,mixed>|null
     */
    private function buildCandidate(
        string $sourceFile,
        string $sourceFilename,
        string $segmentText,
        int $segmentIndex,
        int $segmentTotal,
        array $anchors,
        bool $withGitEvidence,
    ): ?array {
        $legacyLabel = $this->detectLegacyLabel($segmentText, $sourceFilename);
        $legacyOrderKey = $this->legacyOrderKey($legacyLabel);
        $title = $this->detectTitle($segmentText, $sourceFilename);
        $implemented = preg_match('/\b(implemented|implementation complete|completed)\b/i', $segmentText) === 1;

        if ($legacyLabel === '' && $title === '') {
            return null;
        }

        $resultSection = $this->extractSection($segmentText, ['RESULT', 'OUTPUT']);
        $followupsSection = $this->extractSection($segmentText, ['FOLLOWUPS', 'FOLLOW-UPS', 'FOLLOW UPS']);
        $notes = [];
        $module = '';
        $suggestedPath = '';
        $confidence = 'unknown';
        $evidence = [
            'source_text' => 'confirmed',
            'codex_result' => $resultSection === null ? 'unknown' : 'confirmed',
            'followups' => $followupsSection === null ? 'unknown' : 'confirmed',
            'git_commit' => 'unknown',
            'current_source' => 'unknown',
        ];
        $git = ['matched_commits' => []];

        $anchor = $legacyLabel === '' ? null : ($anchors[strtoupper($legacyLabel)] ?? null);
        if (is_array($anchor)) {
            $module = (string) ($anchor['canonical_module'] ?? '');
            $specId = (string) ($anchor['canonical_spec_id'] ?? '');
            $slug = (string) ($anchor['canonical_slug'] ?? '');
            if ($module !== '' && $specId !== '' && $slug !== '') {
                $suggestedPath = 'Modules/' . $module . '/specs/' . $specId . '-' . $slug . '.md';
            }
            $confidence = $this->normalizeConfidence((string) ($anchor['confidence'] ?? 'high'));
            $evidence['current_source'] = 'inferred';
            $notes[] = 'Anchor matched: ' . $legacyLabel;
            $anchorNotes = trim((string) ($anchor['notes'] ?? ''));
            if ($anchorNotes !== '') {
                $notes[] = $anchorNotes;
            }
        } else {
            $suggested = $this->suggestModule($title, $sourceFilename);
            $module = $suggested['module'];
            if ($module !== '') {
                $evidence['current_source'] = 'inferred';
                $confidence = $legacyLabel !== '' ? 'medium' : 'low';
                $suggestedPath = $this->suggestedSpecPath($module, $legacyLabel, $title);
            } elseif ($legacyLabel !== '') {
                $confidence = 'low';
            }
        }

        if ($legacyLabel === '') {
            $notes[] = 'Legacy label not detected.';
        }

        if ($legacyOrderKey === '') {
            $notes[] = 'Legacy ordering key unknown; filename fallback applied.';
        }

        if ($segmentTotal > 1) {
            $notes[] = 'Multi-spec source file segment.';
        }

        if ($withGitEvidence) {
            $git = $this->gitEvidence($sourceFile, $legacyLabel, $title);
            $evidence['git_commit'] = $git['matched_commits'] === [] ? 'unknown' : 'inferred';
        }

        return [
            'candidate_id' => '',
            'source_file' => $sourceFile,
            'source_segment' => $segmentIndex,
            'source_segments_total' => $segmentTotal,
            'legacy_label' => $legacyLabel,
            'legacy_order_key' => $legacyOrderKey,
            'detected_title' => $title,
            'suggested_module' => $module !== '' ? $module : 'unknown',
            'suggested_spec_path' => $suggestedPath,
            'implemented' => $implemented,
            'confidence' => $this->normalizeConfidence($confidence),
            'evidence' => $evidence,
            'notes' => $notes,
            'result_detected' => $resultSection !== null,
            'result_text' => $resultSection,
            'followups_detected' => $followupsSection !== null,
            'followups_text' => $followupsSection,
            'git' => $git,
        ];
    }

    private function detectLegacyLabel(string $segmentText, string $sourceFilename): string
    {
        if (preg_match('/Execution Spec\s*:\s*([0-9][0-9A-Za-z.-]*)/i', $segmentText, $match) === 1) {
            return 'Spec' . strtoupper(trim((string) $match[1]));
        }

        if (preg_match('/Spec\s*([0-9][0-9A-Za-z-]*)/i', $segmentText, $match) === 1) {
            return 'Spec' . strtoupper(trim((string) $match[1]));
        }

        if (preg_match('/Spec[-_ ]*([0-9][0-9A-Za-z-]*)/i', $sourceFilename, $match) === 1) {
            return 'Spec' . strtoupper(trim((string) $match[1]));
        }

        return '';
    }

    private function detectTitle(string $segmentText, string $sourceFilename): string
    {
        if (preg_match('/^Title\s*:\s*(.+)$/im', $segmentText, $match) === 1) {
            return trim((string) $match[1]);
        }

        if (preg_match('/^#{1,6}\s*(.+)$/m', $segmentText, $match) === 1) {
            $heading = trim((string) $match[1]);
            $heading = preg_replace('/^Execution Spec\s*:\s*/i', '', $heading) ?? $heading;
            return trim($heading);
        }

        $base = preg_replace('/\.[A-Za-z0-9]+$/', '', $sourceFilename) ?? $sourceFilename;
        $base = preg_replace('/^Foundry-Spec[-_ ]*/i', '', $base) ?? $base;
        $base = trim(str_replace(['_', '-'], ' ', $base));

        return $base;
    }

    private function legacyOrderKey(string $legacyLabel): string
    {
        if ($legacyLabel === '') {
            return '';
        }

        $suffix = strtoupper(trim($legacyLabel));
        $suffix = preg_replace('/^SPEC\s*/', '', $suffix) ?? $suffix;
        if ($suffix === '' || preg_match('/^[0-9]/', $suffix) !== 1) {
            return '';
        }

        $parts = preg_split('/-+/', $suffix) ?: [];
        $segments = [];
        foreach ($parts as $part) {
            if ($part === '') {
                continue;
            }

            $chars = str_split($part);
            $token = '';
            $tokenType = null;
            foreach ($chars as $char) {
                $type = ctype_digit($char) ? 'digit' : (ctype_alpha($char) ? 'alpha' : 'other');
                if ($type === 'other') {
                    continue;
                }

                if ($tokenType !== null && $tokenType !== $type) {
                    $this->appendLegacyToken($segments, $token, $tokenType);
                    $token = '';
                }

                $tokenType = $type;
                $token .= $char;
            }

            if ($token !== '' && $tokenType !== null) {
                $this->appendLegacyToken($segments, $token, $tokenType);
            }
        }

        if ($segments === [] || !is_int($segments[0])) {
            return '';
        }

        $rendered = [];
        foreach ($segments as $segment) {
            if (is_int($segment)) {
                $rendered[] = sprintf('%03d', $segment);
            } else {
                $rendered[] = $segment;
            }
        }

        return implode('.', $rendered);
    }

    /**
     * @param list<int|string> $segments
     */
    private function appendLegacyToken(array &$segments, string $token, string $tokenType): void
    {
        if ($tokenType === 'digit') {
            $segments[] = (int) $token;
            return;
        }

        foreach (str_split(strtoupper($token)) as $char) {
            $segments[] = $char;
        }
    }

    /**
     * @return array{module:string}
     */
    private function suggestModule(string $title, string $sourceFilename): array
    {
        $haystack = strtolower($title . ' ' . $sourceFilename);
        $mapping = [
            'context' => 'ContextPersistence',
            'generate' => 'GenerateEngine',
            'marketplace' => 'Marketplace',
            'mcp' => 'McpServer',
            'extension' => 'ExtensionSystem',
            'feature-system' => 'FeatureSystem',
            'feature system' => 'FeatureSystem',
            'state-store' => 'StateStore',
            'state store' => 'StateStore',
            'quality' => 'QualityEnforcement',
            'cli' => 'CliExperience',
            'compiler' => 'CompilerDeterminism',
            'event' => 'EventSystem',
            'canonical' => 'CanonicalIdentifiers',
        ];

        foreach ($mapping as $needle => $module) {
            if (str_contains($haystack, $needle)) {
                return ['module' => $module];
            }
        }

        return ['module' => ''];
    }

    private function suggestedSpecPath(string $module, string $legacyLabel, string $title): string
    {
        $slugSource = trim($legacyLabel . ' ' . $title);
        $slug = strtolower($slugSource);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');
        if ($slug === '') {
            return '';
        }

        return 'Modules/' . $module . '/specs/' . $slug . '.md';
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
     * @param list<array<string,mixed>> $candidates
     * @return list<array<string,mixed>>
     */
    private function sortCandidates(array $candidates): array
    {
        usort($candidates, static function (array $a, array $b): int {
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
     * @return array{matched_commits:list<array{hash:string,subject:string,confidence:string}>}
     */
    private function gitEvidence(string $sourceFile, string $legacyLabel, string $title): array
    {
        $root = $this->paths->root();
        $command = sprintf(
            'cd %s && git log --format=%%H%%x09%%s -- %s 2>/dev/null',
            escapeshellarg($root),
            escapeshellarg($sourceFile),
        );
        $output = shell_exec($command);
        if (!is_string($output) || trim($output) === '') {
            return ['matched_commits' => []];
        }

        $matches = [];
        $needle = strtolower(trim($legacyLabel . ' ' . $title));
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

            $confidence = str_contains(strtolower($subject), $needle) ? 'medium' : 'low';
            $matches[] = [
                'hash' => substr($hash, 0, 7),
                'subject' => $subject,
                'confidence' => $confidence,
            ];
            if (count($matches) === 5) {
                break;
            }
        }

        return ['matched_commits' => $matches];
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
            'suggested_module' => (string) ($candidate['suggested_module'] ?? 'unknown'),
            'suggested_spec_path' => (string) ($candidate['suggested_spec_path'] ?? ''),
            'implemented' => (bool) ($candidate['implemented'] ?? false),
            'confidence' => (string) ($candidate['confidence'] ?? 'unknown'),
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
            '- supporting_evidence_files: ' . count((array) ($map['supporting_evidence_files'] ?? [])),
            '- candidates: ' . count((array) ($map['candidates'] ?? [])),
            '',
            '| Candidate | Legacy Label | Order Key | Suggested Module | Confidence | Source |',
            '|---|---|---|---|---|---|',
        ];

        foreach ((array) ($map['candidates'] ?? []) as $candidate) {
            if (!is_array($candidate)) {
                continue;
            }

            $lines[] = sprintf(
                '| %s | %s | %s | %s | %s | %s |',
                (string) ($candidate['candidate_id'] ?? ''),
                (string) ($candidate['legacy_label'] ?? ''),
                (string) ($candidate['legacy_order_key'] ?? ''),
                (string) ($candidate['suggested_module'] ?? ''),
                (string) ($candidate['confidence'] ?? ''),
                (string) ($candidate['source_file'] ?? ''),
            );
        }

        $lines[] = '';

        return implode("\n", $lines);
    }
}
