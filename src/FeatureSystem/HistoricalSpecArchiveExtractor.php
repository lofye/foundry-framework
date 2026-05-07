<?php

declare(strict_types=1);

namespace Foundry\FeatureSystem;

use Foundry\Support\FoundryError;
use Foundry\Support\Paths;

final class HistoricalSpecArchiveExtractor
{
    public function __construct(
        private readonly Paths $paths,
    ) {}

    /**
     * @return array{
     *     status:string,
     *     dry_run:bool,
     *     source_path:string,
     *     target_path:string,
     *     summary:array{files_scanned:int,candidates:int,written:int},
     *     candidates:list<array{
     *         candidate_id:string,
     *         original_file:string,
     *         source_segment:int,
     *         source_segments_total:int,
     *         detected_spec_label:string,
     *         detected_title:string,
     *         suggested_slug:string,
     *         suggested_module:string,
     *         confidence:string,
     *         notes:list<string>,
     *         result_detected:bool,
     *         followups_detected:bool,
     *         output_paths:array{directory:string,spec:string,source:string,metadata:string}
     *     }>
     * }
     */
    public function extract(string $sourcePath, string $targetPath, bool $dryRun): array
    {
        $sourceAbsolute = $this->absolutePath($sourcePath);
        $targetAbsolute = $this->absolutePath($targetPath);

        if (!is_dir($sourceAbsolute)) {
            throw new FoundryError(
                'HISTORICAL_SPECS_SOURCE_DIRECTORY_MISSING',
                'validation',
                ['source' => $this->outputPath($sourceAbsolute)],
                'Historical spec source directory is missing.',
            );
        }

        $files = $this->sourceFiles($sourceAbsolute);
        $rawCandidates = [];

        foreach ($files as $filePath) {
            $contents = file_get_contents($filePath);
            if (!is_string($contents) || trim($contents) === '') {
                continue;
            }

            $relativeFile = $this->outputPath($filePath);
            foreach ($this->extractFileCandidates($contents) as $candidate) {
                $candidate['original_file'] = $relativeFile;
                $rawCandidates[] = $candidate;
            }
        }

        $candidates = $this->finalizeCandidates($rawCandidates, $targetAbsolute);
        $written = 0;

        if (!$dryRun) {
            $this->prepareTargetDirectory($targetAbsolute);

            foreach ($candidates as $candidate) {
                $this->writeCandidate($targetAbsolute, $candidate);
                $written++;
            }
        }

        return [
            'status' => 'ok',
            'dry_run' => $dryRun,
            'source_path' => $this->outputPath($sourceAbsolute),
            'target_path' => $this->outputPath($targetAbsolute),
            'summary' => [
                'files_scanned' => count($files),
                'candidates' => count($candidates),
                'written' => $written,
            ],
            'candidates' => array_map(fn(array $candidate): array => $this->renderCandidate($candidate), $candidates),
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
     * @return list<array{
     *     source_text:string,
     *     spec_text:string,
     *     source_segment:int,
     *     source_segments_total:int,
     *     detected_spec_label:string,
     *     detected_title:string,
     *     suggested_slug:string,
     *     suggested_module:string,
     *     confidence:string,
     *     notes:list<string>,
     *     result_detected:bool,
     *     result_text:string,
     *     followups_detected:bool,
     *     followups_text:string,
     *     original_file?:string
     * }>
     */
    private function extractFileCandidates(string $contents): array
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $contents);
        $lines = explode("\n", $normalized);
        $boundaries = $this->detectBoundaries($lines);

        if ($boundaries === []) {
            $fallback = $this->fallbackCandidate($normalized);

            return $fallback === null ? [] : [$fallback];
        }

        $candidates = [];
        $count = count($boundaries);
        for ($index = 0; $index < $count; $index++) {
            $start = $boundaries[$index];
            $end = ($boundaries[$index + 1] ?? count($lines)) - 1;
            if ($end < $start) {
                continue;
            }

            $segment = implode("\n", array_slice($lines, $start, ($end - $start) + 1));
            $candidate = $this->buildCandidate($segment);
            if ($candidate !== null) {
                $candidate['source_segment'] = $index + 1;
                $candidate['source_segments_total'] = $count;
                $candidates[] = $candidate;
            }
        }

        return $candidates;
    }

    /**
     * @param array<int,string> $lines
     * @return list<int>
     */
    private function detectBoundaries(array $lines): array
    {
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
            $boundaries = array_values(array_filter(
                $boundaries,
                static fn(int $line): bool => $line > 0,
            ));
        }

        return $boundaries;
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

        return preg_match('/^Spec\s+\d+[A-Za-z]*(?:-[0-9A-Za-z]+)?(?:\b|:|\s|-)/i', $trimmed) === 1;
    }

    /**
     * @return array{
     *     source_text:string,
     *     spec_text:string,
     *     source_segment:int,
     *     source_segments_total:int,
     *     detected_spec_label:string,
     *     detected_title:string,
     *     suggested_slug:string,
     *     suggested_module:string,
     *     confidence:string,
     *     notes:list<string>,
     *     result_detected:bool,
     *     result_text:string,
     *     followups_detected:bool,
     *     followups_text:string,
     *     original_file?:string
     * }|null
     */
    private function buildCandidate(string $segment): ?array
    {
        $sourceText = $this->normalizeText($segment);
        if ($sourceText === '') {
            return null;
        }

        $label = $this->detectSpecLabel($sourceText);
        $title = $this->detectTitle($sourceText, $label);
        $confidence = $this->detectConfidence($sourceText, $label, $title);
        $notes = $this->buildNotes($sourceText, $label, $title, $confidence);
        $cleaned = $this->cleanSpecText($sourceText);
        $result = $this->extractSection($sourceText, ['RESULT', 'OUTPUT']);
        $followups = $this->extractSection($sourceText, ['FOLLOWUPS', 'FOLLOW-UPS', 'FOLLOW UPS']);

        return [
            'source_text' => $sourceText,
            'spec_text' => $cleaned,
            'source_segment' => 1,
            'source_segments_total' => 1,
            'detected_spec_label' => $label,
            'detected_title' => $title,
            'suggested_slug' => $this->slugify($label . ' ' . $title),
            'suggested_module' => 'unknown',
            'confidence' => $confidence,
            'notes' => $notes,
            'result_detected' => $result !== null,
            'result_text' => $result ?? '',
            'followups_detected' => $followups !== null,
            'followups_text' => $followups ?? '',
        ];
    }

    /**
     * @return array{
     *     source_text:string,
     *     spec_text:string,
     *     source_segment:int,
     *     source_segments_total:int,
     *     detected_spec_label:string,
     *     detected_title:string,
     *     suggested_slug:string,
     *     suggested_module:string,
     *     confidence:string,
     *     notes:list<string>,
     *     result_detected:bool,
     *     result_text:string,
     *     followups_detected:bool,
     *     followups_text:string,
     *     original_file?:string
     * }|null
     */
    private function fallbackCandidate(string $contents): ?array
    {
        $sourceText = $this->normalizeText($contents);
        if ($sourceText === '') {
            return null;
        }

        $hasTitle = preg_match('/^Title\s*:\s*(.+)$/im', $sourceText) === 1;
        $hasPurpose = preg_match('/^Purpose\s*:\s*(.+)$/im', $sourceText) === 1;
        if (!$hasTitle && !$hasPurpose) {
            return null;
        }

        $candidate = $this->buildCandidate($sourceText);
        if ($candidate === null) {
            return null;
        }

        $candidate['confidence'] = 'low';
        $candidate['notes'][] = 'Weak boundary match; extracted using title/purpose fallback.';

        return $candidate;
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

    private function normalizeText(string $value): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $value);
        $normalized = trim($normalized);
        if ($normalized === '') {
            return '';
        }

        return rtrim($normalized, "\n") . "\n";
    }

    private function detectSpecLabel(string $text): string
    {
        if (preg_match('/Execution Spec\s*:\s*([^\n]+)/i', $text, $match) === 1) {
            return trim((string) $match[1]);
        }

        if (preg_match('/(?:^|\n)\s*#*\s*Spec\s+(\d+[A-Za-z]*(?:-[0-9A-Za-z]+)?)/i', $text, $match) === 1) {
            return 'Spec ' . trim((string) $match[1]);
        }

        return '';
    }

    private function detectTitle(string $text, string $label): string
    {
        if (preg_match('/^Title\s*:\s*(.+)$/im', $text, $match) === 1) {
            return trim((string) $match[1]);
        }

        $lines = preg_split('/\n/', trim($text)) ?: [];
        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            if (preg_match('/^#{1,6}\s*(.+)$/', $trimmed, $match) === 1) {
                $heading = trim((string) $match[1]);
                if (stripos($heading, 'Execution Spec:') === 0) {
                    $heading = trim(substr($heading, strlen('Execution Spec:')));
                }

                if ($label !== '' && strcasecmp($heading, $label) === 0) {
                    continue;
                }

                return $heading;
            }

            if ($label !== '' && str_starts_with(strtolower($trimmed), strtolower($label))) {
                $suffix = trim(substr($trimmed, strlen($label)));
                $suffix = ltrim($suffix, ":- \t");
                if ($suffix !== '') {
                    return $suffix;
                }
            }
        }

        return '';
    }

    private function detectConfidence(string $text, string $label, string $title): string
    {
        if (preg_match('/Execution Spec\s*:/i', $text) === 1) {
            return 'high';
        }

        if ($label !== '' && preg_match('/^Spec\s+\d+[A-Za-z]*(?:-[0-9A-Za-z]+)?$/i', $label) === 1) {
            return 'high';
        }

        if (
            preg_match('/^Title\s*:/im', $text) === 1
            && preg_match('/^Purpose\s*:/im', $text) === 1
        ) {
            return 'medium';
        }

        if ($title !== '') {
            return 'medium';
        }

        return 'low';
    }

    /**
     * @return list<string>
     */
    private function buildNotes(string $text, string $label, string $title, string $confidence): array
    {
        $notes = [];
        if ($label === '') {
            $notes[] = 'Spec label not detected from explicit heading.';
        }
        if ($title === '') {
            $notes[] = 'Title not detected from heading or Title: field.';
        }
        if ($confidence === 'low') {
            $notes[] = 'Low-confidence extraction; manual review recommended.';
        }
        if (preg_match('/^Purpose\s*:/im', $text) !== 1) {
            $notes[] = 'Purpose field not explicitly detected.';
        }

        return $notes;
    }

    private function cleanSpecText(string $sourceText): string
    {
        $text = trim($sourceText);
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        if (preg_match('/^Execution Spec\s*:/i', $text) === 1) {
            $text = '# ' . $text;
        } elseif (preg_match('/^Spec\s+\d+[A-Za-z]*(?:-[0-9A-Za-z]+)?/i', $text) === 1) {
            $text = '# ' . $text;
        }

        return rtrim($text, "\n") . "\n";
    }

    private function slugify(string $value): string
    {
        $normalized = strtolower(trim($value));
        $normalized = preg_replace('/[^a-z0-9]+/', '-', $normalized) ?? '';
        $normalized = trim($normalized, '-');

        return $normalized !== '' ? $normalized : 'historical-spec-candidate';
    }

    /**
     * @param list<array{
     *     source_text:string,
     *     spec_text:string,
     *     source_segment:int,
     *     source_segments_total:int,
     *     detected_spec_label:string,
     *     detected_title:string,
     *     suggested_slug:string,
     *     suggested_module:string,
     *     confidence:string,
     *     notes:list<string>,
     *     result_detected:bool,
     *     result_text:string,
     *     followups_detected:bool,
     *     followups_text:string,
     *     original_file:string
     * }> $rawCandidates
     * @return list<array<string,mixed>>
     */
    private function finalizeCandidates(array $rawCandidates, string $targetAbsolute): array
    {
        $slugCounts = [];
        $final = [];

        foreach (array_values($rawCandidates) as $index => $candidate) {
            $baseSlug = (string) ($candidate['suggested_slug'] ?? 'historical-spec-candidate');
            $count = (int) ($slugCounts[$baseSlug] ?? 0) + 1;
            $slugCounts[$baseSlug] = $count;
            $slug = $count > 1 ? $baseSlug . '-' . $count : $baseSlug;

            $candidateNumber = $index + 1;
            $candidateId = sprintf('candidate-%03d', $candidateNumber);
            $directory = $targetAbsolute . '/' . $candidateId;

            $final[] = [
                'candidate_id' => $candidateId,
                'original_file' => (string) $candidate['original_file'],
                'source_segment' => (int) ($candidate['source_segment'] ?? 1),
                'source_segments_total' => (int) ($candidate['source_segments_total'] ?? 1),
                'detected_spec_label' => (string) $candidate['detected_spec_label'],
                'detected_title' => (string) $candidate['detected_title'],
                'suggested_slug' => $slug,
                'suggested_module' => (string) ($candidate['suggested_module'] ?? 'unknown'),
                'confidence' => (string) $candidate['confidence'],
                'notes' => (array) $candidate['notes'],
                'result_detected' => (bool) ($candidate['result_detected'] ?? false),
                'result_text' => (string) ($candidate['result_text'] ?? ''),
                'followups_detected' => (bool) ($candidate['followups_detected'] ?? false),
                'followups_text' => (string) ($candidate['followups_text'] ?? ''),
                'source_text' => (string) $candidate['source_text'],
                'spec_text' => (string) $candidate['spec_text'],
                'output_paths' => [
                    'directory' => $directory,
                    'spec' => $directory . '/spec.md',
                    'source' => $directory . '/source.md',
                    'metadata' => $directory . '/metadata.json',
                ],
            ];
        }

        return $final;
    }

    private function prepareTargetDirectory(string $targetAbsolute): void
    {
        if (!is_dir($targetAbsolute) && !mkdir($targetAbsolute, 0777, true) && !is_dir($targetAbsolute)) {
            throw new FoundryError(
                'HISTORICAL_SPECS_TARGET_DIRECTORY_CREATE_FAILED',
                'io',
                ['target' => $this->outputPath($targetAbsolute)],
                'Unable to create historical spec target directory.',
            );
        }

        $items = scandir($targetAbsolute) ?: [];
        foreach ($items as $item) {
            if (!preg_match('/^candidate-\d{3}$/', (string) $item)) {
                continue;
            }

            $this->deleteDirectory($targetAbsolute . '/' . $item);
        }
    }

    private function deleteDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $fileInfo) {
            $filePath = $fileInfo->getPathname();
            if ($fileInfo->isDir()) {
                @rmdir($filePath);
            } else {
                @unlink($filePath);
            }
        }

        @rmdir($path);
    }

    /**
     * @param array<string,mixed> $candidate
     */
    private function writeCandidate(string $targetAbsolute, array $candidate): void
    {
        $candidateId = (string) ($candidate['candidate_id'] ?? '');
        if ($candidateId === '') {
            return;
        }

        $directory = $targetAbsolute . '/' . $candidateId;
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new FoundryError(
                'HISTORICAL_SPECS_TARGET_DIRECTORY_CREATE_FAILED',
                'io',
                ['target' => $this->outputPath($directory)],
                'Unable to create candidate output directory.',
            );
        }

        $metadata = [
            'original_file' => (string) ($candidate['original_file'] ?? ''),
            'candidate_id' => $candidateId,
            'source_segment' => (int) ($candidate['source_segment'] ?? 1),
            'source_segments_total' => (int) ($candidate['source_segments_total'] ?? 1),
            'detected_spec_label' => (string) ($candidate['detected_spec_label'] ?? ''),
            'detected_title' => (string) ($candidate['detected_title'] ?? ''),
            'suggested_slug' => (string) ($candidate['suggested_slug'] ?? ''),
            'suggested_module' => (string) ($candidate['suggested_module'] ?? 'unknown'),
            'confidence' => (string) ($candidate['confidence'] ?? 'low'),
            'notes' => array_values(array_map('strval', (array) ($candidate['notes'] ?? []))),
            'result_detected' => (bool) ($candidate['result_detected'] ?? false),
            'followups_detected' => (bool) ($candidate['followups_detected'] ?? false),
        ];

        file_put_contents($directory . '/source.md', (string) ($candidate['source_text'] ?? ''));
        file_put_contents($directory . '/spec.md', (string) ($candidate['spec_text'] ?? ''));
        if ((bool) ($candidate['result_detected'] ?? false)) {
            file_put_contents($directory . '/result.md', (string) ($candidate['result_text'] ?? ''));
        }
        if ((bool) ($candidate['followups_detected'] ?? false)) {
            file_put_contents($directory . '/followups.md', (string) ($candidate['followups_text'] ?? ''));
        }
        file_put_contents(
            $directory . '/metadata.json',
            json_encode($metadata, \JSON_PRETTY_PRINT | \JSON_UNESCAPED_SLASHES | \JSON_THROW_ON_ERROR) . "\n",
        );
    }

    /**
     * @param array<string,mixed> $candidate
     * @return array{
     *     candidate_id:string,
     *     original_file:string,
     *     source_segment:int,
     *     source_segments_total:int,
     *     detected_spec_label:string,
     *     detected_title:string,
     *     suggested_slug:string,
     *     suggested_module:string,
     *     confidence:string,
     *     notes:list<string>,
     *     result_detected:bool,
     *     followups_detected:bool,
     *     output_paths:array{directory:string,spec:string,source:string,metadata:string}
     * }
     */
    private function renderCandidate(array $candidate): array
    {
        return [
            'candidate_id' => (string) $candidate['candidate_id'],
            'original_file' => (string) $candidate['original_file'],
            'source_segment' => (int) ($candidate['source_segment'] ?? 1),
            'source_segments_total' => (int) ($candidate['source_segments_total'] ?? 1),
            'detected_spec_label' => (string) $candidate['detected_spec_label'],
            'detected_title' => (string) $candidate['detected_title'],
            'suggested_slug' => (string) $candidate['suggested_slug'],
            'suggested_module' => (string) $candidate['suggested_module'],
            'confidence' => (string) $candidate['confidence'],
            'notes' => array_values(array_map('strval', (array) $candidate['notes'])),
            'result_detected' => (bool) ($candidate['result_detected'] ?? false),
            'followups_detected' => (bool) ($candidate['followups_detected'] ?? false),
            'output_paths' => [
                'directory' => $this->outputPath((string) $candidate['output_paths']['directory']),
                'spec' => $this->outputPath((string) $candidate['output_paths']['spec']),
                'source' => $this->outputPath((string) $candidate['output_paths']['source']),
                'metadata' => $this->outputPath((string) $candidate['output_paths']['metadata']),
            ],
        ];
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
