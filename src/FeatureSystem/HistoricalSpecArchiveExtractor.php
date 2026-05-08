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
            foreach ($this->extractFileCandidates($contents, basename($filePath)) as $candidate) {
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
            if (preg_match('#/candidate-\\d{3}/#', $path) === 1) {
                continue;
            }

            $basename = basename($path);
            if (in_array($basename, ['evidence-map.json', 'evidence-map.md'], true)) {
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
    private function extractFileCandidates(string $contents, string $sourceFilename): array
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", $contents);
        $lines = explode("\n", $normalized);
        $boundaries = $this->detectBoundaries($lines);
        $rejectedRootSignals = $this->rejectedRootSignals($lines);

        if ($boundaries === []) {
            $fallback = $this->fallbackCandidate($normalized, $sourceFilename, $rejectedRootSignals);

            return $fallback === null ? [] : [$fallback];
        }

        $candidates = [];
        $count = count($boundaries);
        for ($index = 0; $index < $count; $index++) {
            $boundary = $boundaries[$index];
            $start = (int) $boundary['line'];
            $end = (int) (($boundaries[$index + 1]['line'] ?? count($lines)) - 1);
            if ($end < $start) {
                continue;
            }

            $segment = implode("\n", array_slice($lines, $start, ($end - $start) + 1));
            $candidate = $this->buildCandidate(
                $segment,
                (string) $boundary['emission_reason'],
                $this->rejectedRootSignals(array_slice($lines, $start, ($end - $start) + 1)),
            );
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
     * @return list<array{line:int,emission_reason:string}>
     */
    private function detectBoundaries(array $lines): array
    {
        $boundaries = [];

        foreach ($lines as $index => $line) {
            $reason = $this->rootEmissionReason($line);
            if ($reason !== null) {
                $boundaries[] = ['line' => $index, 'emission_reason' => $reason];
            }
        }

        if ($boundaries === []) {
            return [];
        }

        if ((int) $boundaries[0]['line'] !== 0) {
            $boundaries = array_values(array_filter(
                $boundaries,
                static fn(array $boundary): bool => (int) $boundary['line'] > 0,
            ));
        }

        return $boundaries;
    }

    private function rootEmissionReason(string $line): ?string
    {
        $trimmed = trim($line);
        if ($trimmed === '') {
            return null;
        }

        if (preg_match('/^#{1,6}\s*Execution Spec\s*:\s*\S/i', $trimmed) === 1) {
            return 'execution_spec_heading';
        }

        if (preg_match('/^Execution Spec\s*:\s*\S/i', $trimmed) === 1) {
            return 'execution_spec_heading';
        }

        if (preg_match('/^#{0,6}\s*Spec\s*:\s*\S/i', $trimmed) === 1) {
            return 'explicit_spec_heading';
        }

        return preg_match('/^#{0,6}\s*Spec\s+\d+[0-9A-Za-z]*(?:-[0-9A-Za-z]+)?(?:[ \t]*(?::|-|\x{2013}|\x{2014})[ \t]*\S.*)?\s*$/iu', $trimmed) === 1
            ? 'explicit_spec_heading'
            : null;
    }

    /**
     * @param array<int,string> $lines
     * @return list<array{text:string,reason:string}>
     */
    private function rejectedRootSignals(array $lines): array
    {
        $signals = [];

        foreach ($lines as $line) {
            $trimmed = trim($line);
            if ($trimmed === '') {
                continue;
            }

            if ($this->isSectionFragment($trimmed)) {
                $signals[] = ['text' => $trimmed, 'reason' => 'section_fragment'];
                continue;
            }

            if (preg_match('/^#{0,6}\s*Spec\s+\d+[0-9A-Za-z]*(?:-[0-9A-Za-z]+)?\s+[A-Za-z]/i', $trimmed) === 1) {
                $signals[] = ['text' => $trimmed, 'reason' => 'embedded_prior_spec_reference'];
            }
        }

        return $this->dedupeRejectedSignals($signals);
    }

    private function isSectionFragment(string $trimmed): bool
    {
        $normalized = strtolower(trim($trimmed, "# \t"));
        $plainHeadings = [
            'architecture',
            'implementation',
            'ux contract',
            'foundation slice',
            'intelligence layer',
            'final polish',
            'goals',
            'non-goals',
            'acceptance criteria',
            'requirements',
            'testing',
            'verify',
        ];

        foreach ($plainHeadings as $heading) {
            if ($normalized === $heading || str_starts_with($normalized, $heading . ' (')) {
                return true;
            }
        }

        return preg_match('/^(must|should):$/i', $normalized) === 1
            || preg_match('/^(introduced|established|adds|completes)\b/i', $normalized) === 1;
    }

    /**
     * @param list<array{text:string,reason:string}> $signals
     * @return list<array{text:string,reason:string}>
     */
    private function dedupeRejectedSignals(array $signals): array
    {
        $deduped = [];
        foreach ($signals as $signal) {
            $key = (string) $signal['reason'] . "\n" . (string) $signal['text'];
            $deduped[$key] = [
                'text' => (string) $signal['text'],
                'reason' => (string) $signal['reason'],
            ];
        }

        return array_values($deduped);
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
    private function buildCandidate(string $segment, string $emissionReason, array $rejectedRootSignals = []): ?array
    {
        $sourceText = $this->normalizeText($segment);
        if ($sourceText === '') {
            return null;
        }

        $label = $this->detectSpecLabel($sourceText);
        $title = $this->detectTitle($sourceText, $label);
        $candidateQuality = $this->candidateQuality($sourceText, $emissionReason);
        $confidence = $this->detectConfidence($sourceText, $label, $title, $candidateQuality);
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
            'emission_reason' => $emissionReason,
            'candidate_quality' => $candidateQuality,
            'rejected_root_signals' => $rejectedRootSignals,
            'result_association_confidence' => $result !== null ? 'high' : 'unknown',
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
    private function fallbackCandidate(string $contents, string $sourceFilename, array $rejectedRootSignals): ?array
    {
        $sourceText = $this->normalizeText($contents);
        if ($sourceText === '') {
            return null;
        }

        $filenameLabel = $this->detectFilenameSpecLabel($sourceFilename);
        if ($filenameLabel !== '' && $this->hasContractSignal($sourceText)) {
            $candidate = $this->buildCandidate($sourceText, 'legacy_filename_single_spec', $rejectedRootSignals);
            if ($candidate === null) {
                return null;
            }

            if ((string) $candidate['detected_spec_label'] === '') {
                $candidate['detected_spec_label'] = $filenameLabel;
                $candidate['suggested_slug'] = $this->slugify($filenameLabel . ' ' . (string) $candidate['detected_title']);
            }

            $candidate['confidence'] = 'medium';
            $candidate['candidate_quality'] = 'probable';
            $candidate['notes'][] = 'Legacy filename fallback used for single-spec source file.';

            return $candidate;
        }

        $result = $this->extractSection($sourceText, ['RESULT', 'OUTPUT', 'IMPLEMENTATION RESULT']);
        if ($result !== null) {
            return $this->supportingEvidenceCandidate($sourceText, $result, $rejectedRootSignals);
        }

        return null;
    }

    /**
     * @param list<array{text:string,reason:string}> $rejectedRootSignals
     * @return array<string,mixed>
     */
    private function supportingEvidenceCandidate(string $sourceText, string $resultText, array $rejectedRootSignals): array
    {
        return [
            'source_text' => $sourceText,
            'spec_text' => "# Supporting Evidence\n\nResult-only historical evidence; review before import.\n",
            'source_segment' => 1,
            'source_segments_total' => 1,
            'detected_spec_label' => '',
            'detected_title' => 'Supporting evidence',
            'suggested_slug' => 'supporting-evidence',
            'suggested_module' => 'unknown',
            'confidence' => 'low',
            'emission_reason' => 'supporting_evidence',
            'candidate_quality' => 'supporting',
            'rejected_root_signals' => $rejectedRootSignals,
            'result_association_confidence' => 'low',
            'notes' => ['Result/output evidence found without a valid spec root; emitted as supporting evidence.'],
            'result_detected' => true,
            'result_text' => $resultText,
            'followups_detected' => false,
            'followups_text' => '',
        ];
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

        if (preg_match('/(?:^|\n)[ \t]*#*[ \t]*Spec[ \t]+(\d+[0-9A-Za-z]*(?:-[0-9A-Za-z]+)?)(?:[ \t]*(?::|-|\x{2013}|\x{2014})|[ \t]*(?:\n|$))/iu', $text, $match) === 1) {
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

    private function detectConfidence(string $text, string $label, string $title, string $candidateQuality): string
    {
        if ($candidateQuality === 'supporting') {
            return 'low';
        }

        if ($candidateQuality === 'weak') {
            return 'low';
        }

        if (preg_match('/Execution Spec\s*:/i', $text) === 1) {
            return 'high';
        }

        if ($label !== '' && preg_match('/^Spec\s+\d+[0-9A-Za-z]*(?:-[0-9A-Za-z]+)?$/i', $label) === 1) {
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

    private function candidateQuality(string $text, string $emissionReason): string
    {
        if ($emissionReason === 'supporting_evidence') {
            return 'supporting';
        }

        $hasContract = $this->hasContractSignal($text);
        $hasBody = str_word_count(strip_tags($text)) >= 5;

        if (in_array($emissionReason, ['explicit_spec_heading', 'execution_spec_heading'], true)) {
            return $hasContract && $hasBody ? 'strong' : 'weak';
        }

        if ($emissionReason === 'legacy_filename_single_spec') {
            return $hasContract && $hasBody ? 'probable' : 'weak';
        }

        return 'weak';
    }

    private function hasContractSignal(string $text): bool
    {
        return preg_match('/(?:^|\n)\s*#{0,6}\s*(Purpose|Goals|Non-Goals|Requirements|Acceptance Criteria|Testing|Implementation|CLI|Command|Output|Result|Done Means|Must|Should)\b\s*:?\s*/i', $text) === 1;
    }

    private function detectFilenameSpecLabel(string $sourceFilename): string
    {
        if (preg_match('/Foundry-Spec[-_ ]*(\d+[0-9A-Za-z]*(?:-[0-9A-Za-z]+)?)/i', $sourceFilename, $match) !== 1) {
            return '';
        }

        return 'Spec ' . trim((string) $match[1]);
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
                'emission_reason' => (string) ($candidate['emission_reason'] ?? 'manual_anchor'),
                'candidate_quality' => (string) ($candidate['candidate_quality'] ?? 'weak'),
                'rejected_root_signals' => (array) ($candidate['rejected_root_signals'] ?? []),
                'result_association_confidence' => (string) ($candidate['result_association_confidence'] ?? 'unknown'),
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
            'emission_reason' => (string) ($candidate['emission_reason'] ?? 'manual_anchor'),
            'candidate_quality' => (string) ($candidate['candidate_quality'] ?? 'weak'),
            'rejected_root_signals' => array_values(array_map(
                static fn(array $signal): array => [
                    'text' => (string) ($signal['text'] ?? ''),
                    'reason' => (string) ($signal['reason'] ?? ''),
                ],
                array_filter((array) ($candidate['rejected_root_signals'] ?? []), 'is_array'),
            )),
            'result_association_confidence' => (string) ($candidate['result_association_confidence'] ?? 'unknown'),
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
            'emission_reason' => (string) ($candidate['emission_reason'] ?? 'manual_anchor'),
            'candidate_quality' => (string) ($candidate['candidate_quality'] ?? 'weak'),
            'rejected_root_signals' => array_values(array_filter((array) ($candidate['rejected_root_signals'] ?? []), 'is_array')),
            'result_association_confidence' => (string) ($candidate['result_association_confidence'] ?? 'unknown'),
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
