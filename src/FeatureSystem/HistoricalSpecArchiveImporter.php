<?php

declare(strict_types=1);

namespace Foundry\FeatureSystem;

use Foundry\Support\FoundryError;
use Foundry\Support\Paths;

final class HistoricalSpecArchiveImporter
{
    public function __construct(
        private readonly Paths $paths,
    ) {}

    /**
     * @return array{
     *     status:string,
     *     dry_run:bool,
     *     apply:bool,
     *     force:bool,
     *     source_path:string,
     *     summary:array{candidates:int,importable:int,written:int,already_imported:int,conflicts:int,unmapped:int,invalid_metadata:int},
     *     candidates:list<array<string,mixed>>
     * }
     */
    public function import(string $sourcePath, bool $apply, bool $dryRun, bool $force): array
    {
        $sourceAbsolute = $this->absolutePath($sourcePath);
        if (!is_dir($sourceAbsolute)) {
            throw new FoundryError(
                'HISTORICAL_SPEC_IMPORT_SOURCE_DIRECTORY_MISSING',
                'validation',
                ['source' => $this->outputPath($sourceAbsolute)],
                'Historical spec import source directory is missing.',
            );
        }

        $candidateDirectories = $this->candidateDirectories($sourceAbsolute);
        $candidates = [];
        $written = 0;

        foreach ($candidateDirectories as $directory) {
            $candidate = $this->buildCandidate($directory, $force);
            if ($apply && !$dryRun && $candidate['action'] === 'write') {
                $this->writeImportedSpec($candidate);
                $candidate['action'] = 'written';
                $written++;
            }

            $candidates[] = $this->renderCandidate($candidate);
        }

        return [
            'status' => 'ok',
            'dry_run' => $dryRun,
            'apply' => $apply,
            'force' => $force,
            'source_path' => $this->outputPath($sourceAbsolute),
            'summary' => $this->summary($candidates, $written),
            'candidates' => $candidates,
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
    private function candidateDirectories(string $sourceAbsolute): array
    {
        $directories = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceAbsolute, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo instanceof \SplFileInfo || !$fileInfo->isFile()) {
                continue;
            }

            if ($fileInfo->getFilename() !== 'spec.md') {
                continue;
            }

            $directories[] = str_replace('\\', '/', $fileInfo->getPath());
        }

        sort($directories, \SORT_STRING);

        return array_values(array_unique($directories));
    }

    /**
     * @return array<string,mixed>
     */
    private function buildCandidate(string $directory, bool $force): array
    {
        $specPath = $directory . '/spec.md';
        $sourceSpecText = $this->normalizeText((string) file_get_contents($specPath));
        $metadataPath = $directory . '/metadata.json';
        $metadata = $this->readMetadata($metadataPath);
        $relativeDirectory = $this->outputPath($directory);

        $base = [
            'source_directory' => $relativeDirectory,
            'source_spec_path' => $this->outputPath($specPath),
            'metadata_path' => is_file($metadataPath) ? $this->outputPath($metadataPath) : '',
            'destination_path' => '',
            'implemented' => false,
            'source_confidence' => 'unknown',
            'status' => 'unmapped',
            'action' => 'skip',
            'code' => 'HISTORICAL_SPEC_IMPORT_UNMAPPED',
            'message' => 'Metadata is missing; candidate requires manual mapping.',
            'notes' => [],
            'rendered_content' => '',
        ];

        if ($metadata['status'] === 'missing') {
            return $base;
        }

        if ($metadata['status'] !== 'ok') {
            $base['status'] = 'invalid_metadata';
            $base['code'] = 'HISTORICAL_SPEC_IMPORT_INVALID_METADATA';
            $base['message'] = $metadata['message'];

            return $base;
        }

        /** @var array<string,mixed> $data */
        $data = $metadata['data'];
        $validation = $this->validateMetadata($data);
        if ($validation !== []) {
            $base['status'] = 'invalid_metadata';
            $base['code'] = 'HISTORICAL_SPEC_IMPORT_INVALID_METADATA';
            $base['message'] = implode(' ', $validation);

            return $base;
        }

        $module = (string) $data['module'];
        $specId = (string) $data['spec_id'];
        $slug = (string) $data['slug'];
        $implemented = ($data['implemented'] ?? null) === true;
        $sourceConfidence = $this->normalizeConfidence((string) ($data['source_confidence'] ?? 'unknown'));
        $targetGroup = $implemented ? 'specs' : 'specs/drafts';
        $destinationPath = 'Modules/' . $module . '/' . $targetGroup . '/' . $specId . '-' . $slug . '.md';
        $destinationAbsolute = $this->paths->join($destinationPath);
        $renderedContent = $this->renderImportedSpec($specId, $slug, $sourceSpecText);

        $base['destination_path'] = $destinationPath;
        $base['implemented'] = $implemented;
        $base['source_confidence'] = $sourceConfidence;
        $base['rendered_content'] = $renderedContent;

        if (!is_dir($this->paths->join('Modules/' . $module))) {
            $base['status'] = 'invalid_metadata';
            $base['code'] = 'HISTORICAL_SPEC_IMPORT_INVALID_METADATA';
            $base['message'] = 'Metadata module does not exist in Modules/.';

            return $base;
        }

        if (is_file($destinationAbsolute)) {
            $existing = (string) file_get_contents($destinationAbsolute);
            if ($existing === $renderedContent) {
                $base['status'] = 'already_imported';
                $base['action'] = 'already_imported';
                $base['code'] = null;
                $base['message'] = 'Destination already contains the exact imported content.';

                return $base;
            }

            if (!$force) {
                $base['status'] = 'conflict';
                $base['code'] = 'HISTORICAL_SPEC_IMPORT_CONFLICT';
                $base['message'] = 'Destination already exists with different content.';

                return $base;
            }

            $base['notes'][] = 'Force enabled; destination content will be replaced in apply mode.';
        }

        $base['status'] = 'importable';
        $base['action'] = 'write';
        $base['code'] = null;
        $base['message'] = $implemented
            ? 'Candidate maps to an active module spec.'
            : 'Candidate implementation status is uncertain; candidate maps to drafts.';

        return $base;
    }

    /**
     * @return array{status:string,data?:array<string,mixed>,message:string}
     */
    private function readMetadata(string $metadataPath): array
    {
        if (!is_file($metadataPath)) {
            return ['status' => 'missing', 'message' => 'Metadata is missing.'];
        }

        try {
            $decoded = json_decode((string) file_get_contents($metadataPath), true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return ['status' => 'invalid', 'message' => 'Metadata JSON is malformed.'];
        }

        if (!is_array($decoded)) {
            return ['status' => 'invalid', 'message' => 'Metadata JSON must decode to an object.'];
        }

        return ['status' => 'ok', 'data' => $decoded, 'message' => ''];
    }

    /**
     * @param array<string,mixed> $metadata
     * @return list<string>
     */
    private function validateMetadata(array $metadata): array
    {
        $violations = [];
        $module = trim((string) ($metadata['module'] ?? ''));
        $specId = trim((string) ($metadata['spec_id'] ?? ''));
        $slug = trim((string) ($metadata['slug'] ?? ''));

        if ($module === '' || preg_match('/^[A-Z][A-Za-z0-9]*$/', $module) !== 1) {
            $violations[] = 'Metadata module must be a canonical module name.';
        }

        if ($specId === '' || preg_match('/^[0-9]{3}(?:\.[0-9]{3})*$/', $specId) !== 1) {
            $violations[] = 'Metadata spec_id must use padded numeric segments.';
        }

        if ($slug === '' || preg_match('/^[a-z0-9][a-z0-9-]*$/', $slug) !== 1) {
            $violations[] = 'Metadata slug must be lowercase kebab-case.';
        }

        if (array_key_exists('implemented', $metadata) && !is_bool($metadata['implemented'])) {
            $violations[] = 'Metadata implemented must be boolean when present.';
        }

        $confidence = strtolower(trim((string) ($metadata['source_confidence'] ?? 'unknown')));
        if (!in_array($confidence, ['high', 'medium', 'low', 'unknown'], true)) {
            $violations[] = 'Metadata source_confidence must be high, medium, low, or unknown when present.';
        }

        return $violations;
    }

    private function normalizeConfidence(string $value): string
    {
        $normalized = strtolower(trim($value));
        if (in_array($normalized, ['high', 'medium', 'low', 'unknown'], true)) {
            return $normalized;
        }

        return 'unknown';
    }

    private function normalizeText(string $text): string
    {
        $normalized = str_replace(["\r\n", "\r"], "\n", trim($text));
        if ($normalized === '') {
            return '';
        }

        return rtrim($normalized, "\n") . "\n";
    }

    private function renderImportedSpec(string $specId, string $slug, string $sourceSpecText): string
    {
        $heading = '# Execution Spec: ' . $specId . '-' . $slug;
        $body = $sourceSpecText;
        if (preg_match('/^# Execution Spec:\s*' . preg_quote($specId . '-' . $slug, '/') . '\s*$/m', $body) === 1) {
            $body = preg_replace('/^# Execution Spec:[^\n]*\n?/', '', $body, 1) ?? $body;
            $body = ltrim($body, "\n");
        }

        return $heading . "\n\n"
            . "## Historical Import Note\n\n"
            . "This spec was imported from archived pre-repository implementation records. "
            . "It reflects the original archived spec as closely as possible. "
            . "Known uncertainty remains documented in the import report and follow-up reconstruction records.\n\n"
            . rtrim($body, "\n") . "\n";
    }

    /**
     * @param array<string,mixed> $candidate
     */
    private function writeImportedSpec(array $candidate): void
    {
        $destinationPath = (string) $candidate['destination_path'];
        $destinationAbsolute = $this->paths->join($destinationPath);
        $directory = dirname($destinationAbsolute);
        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new FoundryError(
                'HISTORICAL_SPEC_IMPORT_DESTINATION_CREATE_FAILED',
                'io',
                ['destination' => $destinationPath],
                'Unable to create historical spec destination directory.',
            );
        }

        file_put_contents($destinationAbsolute, (string) $candidate['rendered_content']);
    }

    /**
     * @param list<array<string,mixed>> $candidates
     * @return array{candidates:int,importable:int,written:int,already_imported:int,conflicts:int,unmapped:int,invalid_metadata:int}
     */
    private function summary(array $candidates, int $written): array
    {
        $summary = [
            'candidates' => count($candidates),
            'importable' => 0,
            'written' => $written,
            'already_imported' => 0,
            'conflicts' => 0,
            'unmapped' => 0,
            'invalid_metadata' => 0,
        ];

        foreach ($candidates as $candidate) {
            $status = (string) ($candidate['status'] ?? '');
            if ($status === 'importable' || $status === 'written') {
                $summary['importable']++;
            } elseif ($status === 'already_imported') {
                $summary['already_imported']++;
            } elseif ($status === 'conflict') {
                $summary['conflicts']++;
            } elseif ($status === 'unmapped') {
                $summary['unmapped']++;
            } elseif ($status === 'invalid_metadata') {
                $summary['invalid_metadata']++;
            }
        }

        return $summary;
    }

    /**
     * @param array<string,mixed> $candidate
     * @return array<string,mixed>
     */
    private function renderCandidate(array $candidate): array
    {
        unset($candidate['rendered_content']);

        return $candidate;
    }
}
