<?php

declare(strict_types=1);

namespace Foundry\Context;

use Foundry\Support\Paths;

final class ExecutionSpecValidationService
{
    /**
     * @var list<string>
     */
    private const IGNORED_ROOT_FILES = [
        'Modules/README.md',
        'Modules/implementation.log',
        'Features/README.md',
        'Features/implementation.log',
    ];

    private const LEGACY_PLAN_HEADING_PREFIX = '# Implementation Plan: ';

    /**
     * @var list<string>
     */
    private const RECONSTRUCTION_SECTION_ORDER = [
        'Spec Implemented',
        'Implementation Summary',
        'Files Introduced',
        'Files Modified',
        'Runtime Contracts',
        'Deterministic Outputs',
        'Tests Added Or Updated',
        'Verification Commands',
        'Decisions And Tradeoffs',
        'Reconstruction Notes',
        'Follow-Up Dependencies',
    ];

    public function __construct(
        private readonly Paths $paths,
    ) {}

    /**
     * @return array{
     *     ok:bool,
     *     summary:array{checked_files:int,features:int,violations:int,warnings:int},
     *     violations:list<array<string,mixed>>,
     *     warnings:list<array<string,mixed>>
     * }
     */
    public function validate(bool $requireOutcomes = false): array
    {
        $violations = [];
        $warnings = [];
        $checkedFiles = 0;
        $features = [];
        $seenIds = [];
        $continuityCandidates = [];
        $activeSpecReferences = [];
        $activeSpecNames = [];
        $activeSpecPathsByFeature = [];
        $activeSpecLocations = [];
        $activeModuleSpecs = [];
        $activeModuleLegacyReferences = [];

        foreach ($this->specFiles() as $relativePath) {
            if (in_array($relativePath, self::IGNORED_ROOT_FILES, true)) {
                continue;
            }

            $checkedFiles++;

            $placement = $this->classifyPlacement($relativePath);
            if ($placement === null) {
                $violations[] = $this->violation(
                    'EXECUTION_SPEC_INVALID_DIRECTORY',
                    $relativePath,
                    'Execution specs must live at Modules/<Module>/specs/<id>-<slug>.md or Features/<Feature>/specs/<id>-<slug>.md (including drafts).',
                );

                continue;
            }

            $features[$placement['feature']] = true;

            $parsedName = ExecutionSpecFilename::parseName($placement['name']);
            if ($parsedName === null) {
                $violations[] = $this->violation(
                    'EXECUTION_SPEC_INVALID_FILENAME',
                    $relativePath,
                    'Execution spec filenames must use <id>-<slug>.md with one or more dot-separated 3-digit ID segments.',
                );

                continue;
            }

            $seenIds[$placement['feature']][$parsedName['id']][] = $relativePath;
            $location = $placement['status'] === 'draft' ? 'drafts' : 'active';
            if (!$this->isPreCanonicalFeature((string) $placement['feature'])) {
                $continuityCandidates[$placement['feature']][$location][] = [
                    'id' => $parsedName['id'],
                    'segments' => $parsedName['segments'],
                    'path' => $relativePath,
                ];
            }

            $contents = file_get_contents($this->paths->join($relativePath));
            if ($contents === false) {
                $violations[] = $this->violation(
                    'EXECUTION_SPEC_FILE_UNREADABLE',
                    $relativePath,
                    'Execution spec file could not be read.',
                );

                continue;
            }

            $fileHasViolations = false;

            if ($this->firstLine($contents) !== ExecutionSpecFilename::heading($parsedName['name'])) {
                $expectedHeading = ExecutionSpecFilename::heading($parsedName['name']);
                $actualHeading = $this->firstLine($contents);
                $violations[] = $this->violation(
                    'EXECUTION_SPEC_INVALID_HEADING',
                    $relativePath,
                    'Execution spec heading must mirror the filename only.',
                    [
                        'expected_heading' => $expectedHeading,
                        'actual_heading' => $actualHeading,
                    ],
                );
                $fileHasViolations = true;
            }

            $metadataViolations = $this->isPreCanonicalFeature((string) $placement['feature'])
                ? []
                : $this->metadataViolations($relativePath, $contents);
            foreach ($metadataViolations as $metadataViolation) {
                $violations[] = $metadataViolation;
            }
            if ($metadataViolations !== []) {
                $fileHasViolations = true;
            }

            if ($placement['status'] === 'active' && !$fileHasViolations) {
                $activeSpecReferences[$relativePath] = $this->implementationLogReferenceForActiveSpec(
                    $relativePath,
                    (string) $placement['feature'],
                    $parsedName['name'],
                    (string) $placement['scope'],
                );
                $activeSpecNames[$placement['feature']][$parsedName['name']] = true;
                $activeSpecPathsByFeature[$placement['feature']][$parsedName['name']] = $relativePath;
                $activeSpecLocations[$placement['feature']][$parsedName['name']][$placement['workspace']][] = $relativePath;
                if ($placement['scope'] === 'module') {
                    $activeModuleSpecs[] = [
                        'feature' => $placement['feature'],
                        'name' => $parsedName['name'],
                        'spec_path' => $relativePath,
                        'feature_dir' => (string) $placement['feature_dir'],
                        'expected_note_path' => $this->moduleReconstructionNotePath((string) $placement['feature_dir'], $parsedName['name']),
                    ];
                    $activeModuleLegacyReferences[$relativePath] = (string) $placement['feature'] . '/' . $parsedName['name'] . '.md';
                }
            }
        }

        foreach ($activeSpecLocations as $feature => $specNames) {
            foreach ($specNames as $name => $locations) {
                if (!isset($locations['canonical'], $locations['legacy'])) {
                    continue;
                }

                $canonicalPaths = array_values(array_unique(array_map('strval', (array) $locations['canonical'])));
                $legacyPaths = array_values(array_unique(array_map('strval', (array) $locations['legacy'])));
                sort($canonicalPaths);
                sort($legacyPaths);

                $violations[] = $this->violation(
                    'FEATURE_DUPLICATE_CANONICAL_AND_LEGACY',
                    $canonicalPaths[0] ?? ('Modules/' . $name . '.md'),
                    'Execution spec exists in both canonical and legacy feature workspaces.',
                    [
                        'feature' => $feature,
                        'spec_name' => $name,
                        'canonical_paths' => $canonicalPaths,
                        'legacy_paths' => $legacyPaths,
                    ],
                );
            }
        }

        foreach ($seenIds as $feature => $ids) {
            foreach ($ids as $id => $paths) {
                if (count($paths) < 2) {
                    continue;
                }

                sort($paths);
                $violations[] = $this->violation(
                    'EXECUTION_SPEC_DUPLICATE_ID',
                    $paths[0],
                    'Execution spec IDs must be unique within a feature.',
                    [
                        'feature' => $feature,
                        'id' => $id,
                        'paths' => $paths,
                    ],
                );
            }
        }

        $continuity = new ExecutionSpecIdContinuity();
        foreach ($continuityCandidates as $feature => $byLocation) {
            foreach ($byLocation as $location => $entries) {
                foreach ($continuity->gaps($entries) as $gap) {
                    $violations[] = $this->violation(
                        'EXECUTION_SPEC_ID_GAP',
                        (string) $gap['path'],
                        'Execution spec IDs must be contiguous. Skipping numbers violates execution-spec-system rules.',
                        [
                            'feature' => $feature,
                            'location' => $location,
                            'parent_id' => (string) ($gap['parent_id'] ?? 'top-level'),
                            'missing_id' => (string) $gap['missing_id'],
                            'expected_missing_id' => (string) $gap['missing_id'],
                            'next_observed_id' => (string) $gap['next_observed_id'],
                            'path' => (string) $gap['path'],
                        ],
                    );
                }
            }
        }

        $logPath = $this->implementationLogPath();
        $loggedSpecs = $this->implementationLogEntries($violations, $logPath);
        if ($loggedSpecs !== null) {
            $loggedContinuity = [];
            foreach ($activeSpecReferences as $relativePath => $specReference) {
                if (isset($loggedSpecs[$specReference])) {
                    continue;
                }

                $legacyReference = $activeModuleLegacyReferences[$relativePath] ?? null;
                if ($legacyReference !== null && isset($loggedSpecs[$legacyReference])) {
                    $violations[] = $this->violation(
                        'EXECUTION_SPEC_IMPLEMENTATION_LOG_PATH_NOT_CANONICAL',
                        $logPath,
                        'Implementation-log entries for framework modules must use canonical module spec paths.',
                        [
                            'entry' => $legacyReference,
                            'expected' => $specReference,
                            'spec_path' => $relativePath,
                        ],
                    );

                    continue;
                }

                $violations[] = $this->violation(
                    'EXECUTION_SPEC_IMPLEMENTATION_LOG_MISSING',
                    $relativePath,
                    'Active execution specs must have a matching implementation-log entry.',
                    [
                        'spec' => $specReference,
                        'log_path' => $logPath,
                    ],
                );
            }

            foreach (array_keys($loggedSpecs) as $specReference) {
                $parsedReference = $this->parseImplementationLogReference($specReference);
                if ($parsedReference === null) {
                    continue;
                }

                $parsedName = ExecutionSpecFilename::parseName((string) $parsedReference['name']);
                if ($parsedName === null) {
                    continue;
                }

                if ($this->isPreCanonicalFeature((string) $parsedReference['feature'])) {
                    continue;
                }

                $loggedContinuity[(string) $parsedReference['feature']][] = [
                    'id' => $parsedName['id'],
                    'segments' => $parsedName['segments'],
                    'path' => $logPath,
                ];
            }

            foreach ($loggedContinuity as $feature => $entries) {
                foreach ($continuity->gaps($entries) as $gap) {
                    $violations[] = $this->violation(
                        'EXECUTION_SPEC_IMPLEMENTATION_LOG_SKIPPED_ID',
                        $logPath,
                        'Implementation-log entries must not skip execution spec IDs. Skipping numbers violates execution-spec-system rules.',
                        [
                            'feature' => $feature,
                            'missing_id' => (string) $gap['missing_id'],
                            'next_observed_id' => (string) $gap['next_observed_id'],
                        ],
                    );
                }
            }
        }

        $seenPlanIds = [];
        $planNamesByFeature = [];

        foreach ($this->planFiles() as $relativePath) {
            $checkedFiles++;
            $features[$this->planFeatureHint($relativePath)] = true;

            $placement = $this->classifyPlanPlacement($relativePath);
            if ($placement === null) {
                $violations[] = $this->violation(
                    'EXECUTION_SPEC_PLAN_INVALID_DIRECTORY',
                    $relativePath,
                    'Reconstruction outcome notes must live at Modules/<Module>/outcomes/<id>-<slug>.md or Features/<Feature>/outcomes/<id>-<slug>.md.',
                );
                continue;
            }

            $features[$placement['feature']] = true;

            $parsedName = ExecutionSpecFilename::parseName($placement['name']);
            if ($parsedName === null) {
                $violations[] = $this->violation(
                    'EXECUTION_SPEC_PLAN_INVALID_FILENAME',
                    $relativePath,
                    'Reconstruction outcome filenames must use <id>-<slug>.md with one or more dot-separated 3-digit ID segments.',
                );
                continue;
            }

            $seenPlanIds[$placement['feature']][$parsedName['id']][] = $relativePath;
            $planNamesByFeature[$placement['feature']][$parsedName['name']] = true;

            $contents = file_get_contents($this->paths->join($relativePath));
            if ($contents === false) {
                $violations[] = $this->violation(
                    'EXECUTION_SPEC_PLAN_FILE_UNREADABLE',
                    $relativePath,
                    'Reconstruction outcome file could not be read.',
                );
                continue;
            }

            $fileHasViolations = false;
            $expectedHeading = '# Implementation Plan: ' . $parsedName['name'];
            $reconstructionHeading = '# ' . $parsedName['name'];
            $firstLine = $this->firstLine($contents);
            if ($firstLine !== $expectedHeading && $firstLine !== $reconstructionHeading) {
                $violations[] = $this->violation(
                    'EXECUTION_SPEC_PLAN_INVALID_HEADING',
                    $relativePath,
                    'Reconstruction outcomes must use either the legacy plan heading or the reconstruction-note filename heading.',
                    [
                        'expected_heading' => $expectedHeading,
                        'expected_reconstruction_heading' => $reconstructionHeading,
                        'actual_heading' => $firstLine,
                    ],
                );
                $fileHasViolations = true;
            }

            $metadataViolations = $this->isPreCanonicalFeature((string) $placement['feature'])
                ? []
                : $this->metadataViolations($relativePath, $contents, 'EXECUTION_SPEC_PLAN_FORBIDDEN_METADATA', 'Reconstruction outcomes must not define `%s` metadata inside the file.');
            foreach ($metadataViolations as $metadataViolation) {
                $violations[] = $metadataViolation;
            }
            if ($metadataViolations !== []) {
                $fileHasViolations = true;
            }

            if (!$fileHasViolations && !isset($activeSpecNames[$placement['feature']][$parsedName['name']])) {
                $violations[] = $this->violation(
                    'EXECUTION_SPEC_PLAN_ORPHAN',
                    $relativePath,
                    'Reconstruction outcome filename must match an active execution spec filename in the same feature.',
                    [
                        'feature' => $placement['feature'],
                        'id' => $parsedName['id'],
                    ],
                );
            }
        }

        foreach ($seenPlanIds as $feature => $ids) {
            foreach ($ids as $id => $paths) {
                if (count($paths) < 2) {
                    continue;
                }

                sort($paths);
                $violations[] = $this->violation(
                    'EXECUTION_SPEC_PLAN_DUPLICATE_ID',
                    $paths[0],
                    'Reconstruction outcome IDs must be unique within a feature.',
                    [
                        'feature' => $feature,
                        'id' => $id,
                        'paths' => $paths,
                    ],
                );
            }
        }

        if ($requireOutcomes) {
            foreach ($activeSpecNames as $feature => $names) {
                foreach (array_keys($names) as $name) {
                    if (isset($planNamesByFeature[$feature][$name])) {
                        continue;
                    }

                    $specPath = (string) ($activeSpecPathsByFeature[$feature][$name] ?? $this->canonicalActiveSpecPath($feature, $name));
                    $violations[] = $this->violation(
                        'EXECUTION_SPEC_PLAN_REQUIRED_MISSING',
                        $specPath,
                        'Active execution specs must have a matching reconstruction outcome note when --require-outcomes is enabled.',
                        [
                            'feature' => $feature,
                            'id' => (string) (ExecutionSpecFilename::parseName($name)['id'] ?? ''),
                            'plan_path' => $this->canonicalPlanPath($feature, $name),
                        ],
                    );
                }
            }
        }

        foreach ($activeModuleSpecs as $moduleSpec) {
            $notePath = (string) $moduleSpec['expected_note_path'];
            $noteAbsolutePath = $this->paths->join($notePath);
            if (!is_file($noteAbsolutePath)) {
                $violations[] = $this->violation(
                    'EXECUTION_SPEC_RECONSTRUCTION_NOTE_MISSING',
                    (string) $moduleSpec['spec_path'],
                    'Active module execution specs must have a matching reconstruction note in outcomes/.',
                    [
                        'spec_path' => (string) $moduleSpec['spec_path'],
                        'expected_path' => $notePath,
                    ],
                );

                continue;
            }

            $noteContents = file_get_contents($noteAbsolutePath);
            if ($noteContents === false) {
                $violations[] = $this->violation(
                    'EXECUTION_SPEC_RECONSTRUCTION_NOTE_MISSING',
                    (string) $moduleSpec['spec_path'],
                    'Active module execution specs must have a readable reconstruction note in outcomes/.',
                    [
                        'spec_path' => (string) $moduleSpec['spec_path'],
                        'expected_path' => $notePath,
                    ],
                );

                continue;
            }

            $firstLine = $this->firstLine($noteContents);
            if ($firstLine === '' || str_starts_with($firstLine, self::LEGACY_PLAN_HEADING_PREFIX)) {
                continue;
            }

            $expectedHeading = '# ' . (string) $moduleSpec['name'];
            if ($firstLine !== $expectedHeading) {
                $violations[] = $this->violation(
                    'EXECUTION_SPEC_RECONSTRUCTION_NOTE_HEADING_INVALID',
                    $notePath,
                    'Reconstruction note heading must mirror the filename only.',
                    [
                        'expected_heading' => $expectedHeading,
                        'actual_heading' => $firstLine,
                    ],
                );

                continue;
            }

            $sectionHeadings = $this->sectionHeadings($noteContents);
            $missingSections = [];
            foreach (self::RECONSTRUCTION_SECTION_ORDER as $sectionHeading) {
                if (!in_array($sectionHeading, $sectionHeadings, true)) {
                    $missingSections[] = $sectionHeading;
                }
            }

            if ($missingSections !== []) {
                foreach ($missingSections as $missingSection) {
                    $violations[] = $this->violation(
                        'EXECUTION_SPEC_RECONSTRUCTION_NOTE_SECTION_MISSING',
                        $notePath,
                        'Reconstruction notes must include all required sections.',
                        [
                            'missing_section' => $missingSection,
                            'required_sections' => self::RECONSTRUCTION_SECTION_ORDER,
                        ],
                    );
                }

                continue;
            }

            $positions = [];
            foreach (self::RECONSTRUCTION_SECTION_ORDER as $sectionHeading) {
                $positions[$sectionHeading] = array_search($sectionHeading, $sectionHeadings, true);
            }

            $isOrdered = true;
            $previousPosition = -1;
            foreach (self::RECONSTRUCTION_SECTION_ORDER as $sectionHeading) {
                $position = (int) $positions[$sectionHeading];
                if ($position > $previousPosition) {
                    $previousPosition = $position;

                    continue;
                }

                $isOrdered = false;
                break;
            }

            if (!$isOrdered) {
                $violations[] = $this->violation(
                    'EXECUTION_SPEC_RECONSTRUCTION_NOTE_SECTION_ORDER_INVALID',
                    $notePath,
                    'Reconstruction note required sections must appear in canonical order.',
                    [
                        'required_sections' => self::RECONSTRUCTION_SECTION_ORDER,
                        'actual_sections' => $sectionHeadings,
                    ],
                );
            }
        }

        $warnings = $this->decisionSummaryWarnings($activeModuleSpecs);

        usort($violations, static function (array $left, array $right): int {
            return strcmp(
                (string) (($left['file_path'] ?? '') . "\n" . ($left['code'] ?? '')),
                (string) (($right['file_path'] ?? '') . "\n" . ($right['code'] ?? '')),
            );
        });
        usort($warnings, static function (array $left, array $right): int {
            return strcmp(
                (string) (($left['file_path'] ?? '') . "\n" . ($left['code'] ?? '')),
                (string) (($right['file_path'] ?? '') . "\n" . ($right['code'] ?? '')),
            );
        });

        return [
            'ok' => $violations === [],
            'summary' => [
                'checked_files' => $checkedFiles,
                'features' => count($features),
                'violations' => count($violations),
                'warnings' => count($warnings),
            ],
            'violations' => $violations,
            'warnings' => $warnings,
        ];
    }

    /**
     * @return list<string>
     */
    private function specFiles(): array
    {
        $files = [];
        foreach ([
            'Modules/*/specs/*.md',
            'Modules/*/specs/drafts/*.md',
            'Modules/*/specs/*/*.md',
            'Features/*/specs/*.md',
            'Features/*/specs/drafts/*.md',
            'Features/*/specs/*/*.md',
            'docs/specs/*.md',
            'docs/specs/*/*.md',
            'docs/specs/*/drafts/*.md',
            'docs/*/specs/*.md',
            'docs/*/specs/drafts/*.md',
            'docs/*/specs/*/*.md',
        ] as $pattern) {
            foreach (glob($this->paths->join($pattern)) ?: [] as $path) {
                if (!is_file($path)) {
                    continue;
                }

                $relativePath = $this->relativePath($path);
                if ($relativePath === null) {
                    continue;
                }

                $files[] = $relativePath;
            }
        }

        sort($files);

        return array_values(array_unique($files));
    }

    /**
     * @return list<string>
     */
    private function planFiles(): array
    {
        $files = [];

        foreach ([
            'Modules/*/outcomes/*.md',
            'Modules/*/outcomes/*/*.md',
            'Features/*/outcomes/*.md',
            'Features/*/outcomes/*/*.md',
            'docs/specs/outcomes/*.md',
            'docs/specs/*/outcomes/*.md',
            'docs/*/outcomes/*.md',
            'Modules/*/plans/*.md',
            'Modules/*/plans/*/*.md',
            'Features/*/plans/*.md',
            'Features/*/plans/*/*.md',
            'docs/specs/plans/*.md',
            'docs/specs/*/plans/*.md',
            'docs/*/plans/*.md',
        ] as $pattern) {
            foreach (glob($this->paths->join($pattern)) ?: [] as $path) {
                if (!is_file($path)) {
                    continue;
                }

                $relativePath = $this->relativePath($path);
                if ($relativePath === null) {
                    continue;
                }

                $files[] = $relativePath;
            }
        }

        sort($files);

        return array_values(array_unique($files));
    }

    /**
     * @param list<array<string,mixed>> $violations
     * @return array<string,true>|null
     */
    private function implementationLogEntries(array &$violations, string $relativePath): ?array
    {
        $entries = [];
        $candidatePaths = [$relativePath];

        if ($relativePath === 'Modules/implementation.log') {
            $candidatePaths[] = 'Features/implementation.log';
        } elseif ($relativePath === 'Features/implementation.log') {
            $candidatePaths[] = 'Modules/implementation.log';
        }

        foreach (array_values(array_unique($candidatePaths)) as $candidatePath) {
            $absolutePath = $this->paths->join($candidatePath);
            if (!file_exists($absolutePath)) {
                continue;
            }

            if (is_dir($absolutePath)) {
                $violations[] = $this->violation(
                    'EXECUTION_SPEC_IMPLEMENTATION_LOG_INVALID',
                    $candidatePath,
                    'Execution spec implementation log must be a readable file.',
                    ['path' => $candidatePath],
                );

                return null;
            }

            $contents = file_get_contents($absolutePath);
            if ($contents === false) {
                $violations[] = $this->violation(
                    'EXECUTION_SPEC_IMPLEMENTATION_LOG_INVALID',
                    $candidatePath,
                    'Execution spec implementation log must be a readable file.',
                    ['path' => $candidatePath],
                );

                return null;
            }

            foreach (preg_split('/\R/', $contents) ?: [] as $line) {
                if (preg_match('/^- spec: (?<spec>.+)$/', $line, $matches) !== 1) {
                    continue;
                }

                $entries[(string) $matches['spec']] = true;
            }
        }

        return $entries;
    }

    /**
     * @return array{feature:string,status:string,name:string,workspace:string,scope:string,feature_dir?:string}|null
     */
    private function classifyPlacement(string $relativePath): ?array
    {
        if (preg_match('#^Modules/(?<feature_dir>[A-Z][A-Za-z0-9]*)/specs/(?<name>[^/]+)\.md$#', $relativePath, $matches) === 1) {
            return [
                'feature' => $this->slugFromPascal((string) $matches['feature_dir']),
                'status' => 'active',
                'name' => (string) $matches['name'],
                'workspace' => 'canonical',
                'scope' => 'module',
                'feature_dir' => (string) $matches['feature_dir'],
            ];
        }

        if (preg_match('#^Modules/(?<feature_dir>[A-Z][A-Za-z0-9]*)/specs/drafts/(?<name>[^/]+)\.md$#', $relativePath, $matches) === 1) {
            return [
                'feature' => $this->slugFromPascal((string) $matches['feature_dir']),
                'status' => 'draft',
                'name' => (string) $matches['name'],
                'workspace' => 'canonical',
                'scope' => 'module',
                'feature_dir' => (string) $matches['feature_dir'],
            ];
        }

        if (preg_match('#^Features/(?<feature_dir>[A-Z][A-Za-z0-9]*)/specs/(?<name>[^/]+)\.md$#', $relativePath, $matches) === 1) {
            return [
                'feature' => $this->slugFromPascal((string) $matches['feature_dir']),
                'status' => 'active',
                'name' => (string) $matches['name'],
                'workspace' => 'canonical',
                'scope' => 'feature',
                'feature_dir' => (string) $matches['feature_dir'],
            ];
        }

        if (preg_match('#^Features/(?<feature_dir>[A-Z][A-Za-z0-9]*)/specs/drafts/(?<name>[^/]+)\.md$#', $relativePath, $matches) === 1) {
            return [
                'feature' => $this->slugFromPascal((string) $matches['feature_dir']),
                'status' => 'draft',
                'name' => (string) $matches['name'],
                'workspace' => 'canonical',
                'scope' => 'feature',
                'feature_dir' => (string) $matches['feature_dir'],
            ];
        }

        return null;
    }

    /**
     * @return array{feature:string,name:string}|null
     */
    private function classifyPlanPlacement(string $relativePath): ?array
    {
        if (preg_match('#^Modules/(?<feature_dir>[A-Z][A-Za-z0-9]*)/(?:outcomes|plans)/(?<name>[^/]+)\.md$#', $relativePath, $matches) === 1) {
            return [
                'feature' => $this->slugFromPascal((string) $matches['feature_dir']),
                'name' => (string) $matches['name'],
            ];
        }

        if (preg_match('#^Features/(?<feature_dir>[A-Z][A-Za-z0-9]*)/(?:outcomes|plans)/(?<name>[^/]+)\.md$#', $relativePath, $matches) === 1) {
            return [
                'feature' => $this->slugFromPascal((string) $matches['feature_dir']),
                'name' => (string) $matches['name'],
            ];
        }

        return null;
    }

    private function planFeatureHint(string $relativePath): string
    {
        if (preg_match('#^Modules/(?<feature_dir>[A-Z][A-Za-z0-9]*)/#', $relativePath, $matches) === 1) {
            return $this->slugFromPascal((string) $matches['feature_dir']);
        }

        if (preg_match('#^Features/(?<feature_dir>[A-Z][A-Za-z0-9]*)/#', $relativePath, $matches) === 1) {
            return $this->slugFromPascal((string) $matches['feature_dir']);
        }

        return '_noncanonical';
    }

    private function relativePath(string $absolutePath): ?string
    {
        $root = rtrim($this->paths->root(), '/');
        if (!str_starts_with($absolutePath, $root . '/')) {
            return null;
        }

        return substr($absolutePath, strlen($root) + 1);
    }

    private function firstLine(string $contents): string
    {
        $firstLine = strtok(str_replace("\r\n", "\n", $contents), "\n");

        return $firstLine === false ? '' : trim($firstLine);
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function metadataViolations(
        string $relativePath,
        string $contents,
        string $code = 'EXECUTION_SPEC_FORBIDDEN_METADATA',
        string $messageTemplate = 'Execution specs must not define `%s` metadata inside the file.',
    ): array {
        $violations = [];
        $insideFence = false;

        foreach (preg_split('/\R/', $contents) ?: [] as $lineNumber => $line) {
            $trimmed = trim($line);

            if (str_starts_with($trimmed, '```')) {
                $insideFence = !$insideFence;

                continue;
            }

            if ($insideFence) {
                continue;
            }

            if (preg_match('/^(?:[-*]\s+)?(?<field>id|parent|status)\s*:/i', $trimmed, $matches) !== 1) {
                continue;
            }

            $field = strtolower((string) $matches['field']);
            $violations[] = $this->violation(
                $code,
                $relativePath,
                sprintf($messageTemplate, $field),
                [
                    'field' => $field,
                    'line' => $lineNumber + 1,
                ],
            );
        }

        return $violations;
    }

    private function isPreCanonicalFeature(string $feature): bool
    {
        return $feature === 'pre-canonical';
    }

    /**
     * @param array<string,mixed> $details
     * @return array<string,mixed>
     */
    private function violation(string $code, string $filePath, string $message, array $details = []): array
    {
        return [
            'code' => $code,
            'message' => $message,
            'file_path' => $filePath,
            'details' => $details,
        ];
    }

    /**
     * @param list<array<string,mixed>> $activeModuleSpecs
     * @return list<array<string,mixed>>
     */
    private function decisionSummaryWarnings(array $activeModuleSpecs): array
    {
        $warnings = [];
        $latestImplementedByModule = $this->latestImplementedModuleSpecNames();
        $modules = [];

        foreach ($activeModuleSpecs as $moduleSpec) {
            $moduleName = (string) ($moduleSpec['feature_dir'] ?? '');
            if ($moduleName === '') {
                continue;
            }
            $modules[$moduleName] = true;
        }

        foreach (array_keys($modules) as $moduleName) {
            $moduleSlug = $this->slugFromPascal($moduleName);
            $statePath = 'Modules/' . $moduleName . '/' . $moduleSlug . '.md';
            $stateAbsolutePath = $this->paths->join($statePath);
            if (!is_file($stateAbsolutePath)) {
                continue;
            }

            $contents = file_get_contents($stateAbsolutePath);
            if ($contents === false) {
                continue;
            }

            $summary = $this->decisionSummarySection($contents);
            $latestImplemented = $latestImplementedByModule[$moduleName] ?? null;

            if ($summary === null) {
                $warnings[] = $this->violation(
                    'DECISION_SUMMARY_MISSING',
                    $statePath,
                    'Module state should include a `## Decision Summary` section so decision-ledger history remains append-only.',
                    ['module' => $moduleName],
                );
                continue;
            }

            if ($latestImplemented === null) {
                continue;
            }

            $refreshedName = $this->summaryRefreshedSpecName($summary);
            if ($refreshedName === null || $refreshedName !== $latestImplemented) {
                $details = ['module' => $moduleName, 'latest_implemented_spec' => $latestImplemented];
                if ($refreshedName !== null) {
                    $details['refreshed_through_spec'] = $refreshedName;
                }

                $warnings[] = $this->violation(
                    'DECISION_SUMMARY_POSSIBLY_STALE',
                    $statePath,
                    'Decision summary may be stale; refresh it after implemented specs while preserving the append-only decision ledger.',
                    $details,
                );
            }
        }

        return $warnings;
    }

    /**
     * @return array<string,string>
     */
    private function latestImplementedModuleSpecNames(): array
    {
        $latestByModule = [];
        $logPath = $this->paths->join('Modules/implementation.log');
        if (!is_file($logPath)) {
            return $latestByModule;
        }

        $contents = file_get_contents($logPath);
        if ($contents === false) {
            return $latestByModule;
        }

        foreach (preg_split('/\R/', $contents) ?: [] as $line) {
            if (
                preg_match(
                    '#^- spec: Modules/(?<module>[A-Z][A-Za-z0-9]*)/specs/(?<name>[0-9]{3}(?:\.[0-9]{3})*-[a-z0-9]+(?:-[a-z0-9]+)*)\.md$#',
                    $line,
                    $matches,
                ) !== 1
            ) {
                continue;
            }

            $latestByModule[(string) $matches['module']] = (string) $matches['name'];
        }

        return $latestByModule;
    }

    private function decisionSummarySection(string $contents): ?string
    {
        if (preg_match('/^## Decision Summary\s*$(?<body>[\s\S]*?)(?=^##\s|\z)/m', $contents, $matches) !== 1) {
            return null;
        }

        return trim((string) ($matches['body'] ?? ''));
    }

    private function summaryRefreshedSpecName(string $summary): ?string
    {
        if (preg_match('/Refreshed Through Spec:\s*`?(?<name>[0-9]{3}(?:\.[0-9]{3})*-[a-z0-9]+(?:-[a-z0-9]+)*)`?/i', $summary, $matches) !== 1) {
            return null;
        }

        return (string) $matches['name'];
    }

    private function implementationLogPath(): string
    {
        $modulesCanonical = $this->paths->join('Modules/implementation.log');
        $canonical = $this->paths->join('Features/implementation.log');
        $modulesRoot = $this->paths->join('Modules');
        $featuresRoot = $this->paths->join('Features');

        if (is_file($modulesCanonical) || is_dir($modulesRoot)) {
            return 'Modules/implementation.log';
        }

        if (is_file($canonical) || is_dir($featuresRoot)) {
            return 'Features/implementation.log';
        }

        return 'Modules/implementation.log';
    }

    private function implementationLogReferenceForActiveSpec(string $relativePath, string $feature, string $name, string $scope): string
    {
        if ($scope === 'module') {
            return $relativePath;
        }

        return $feature . '/' . $name . '.md';
    }

    /**
     * @return array{feature:string,name:string}|null
     */
    private function parseImplementationLogReference(string $reference): ?array
    {
        $trimmed = trim($reference);
        if ($trimmed === '') {
            return null;
        }

        $activePath = ExecutionSpecFilename::parseActivePath($trimmed);
        if ($activePath !== null) {
            return [
                'feature' => $activePath['feature'],
                'name' => $activePath['name'],
            ];
        }

        if (preg_match('#^(?<feature>[a-z0-9]+(?:-[a-z0-9]+)*)/(?<name>[^/]+)\.md$#', $trimmed, $matches) !== 1) {
            return null;
        }

        return [
            'feature' => (string) $matches['feature'],
            'name' => (string) $matches['name'],
        ];
    }

    private function canonicalPlanPath(string $feature, string $name): string
    {
        $modulesCanonical = 'Modules/' . $this->pascalFromSlug($feature) . '/outcomes/' . $name . '.md';
        if (is_file($this->paths->join($modulesCanonical)) || is_dir($this->paths->join('Modules/' . $this->pascalFromSlug($feature)))) {
            return $modulesCanonical;
        }

        $canonical = 'Features/' . $this->pascalFromSlug($feature) . '/outcomes/' . $name . '.md';
        if (is_file($this->paths->join($canonical)) || is_dir($this->paths->join('Features/' . $this->pascalFromSlug($feature)))) {
            return $canonical;
        }

        return 'Features/' . $this->pascalFromSlug($feature) . '/outcomes/' . $name . '.md';
    }

    private function canonicalActiveSpecPath(string $feature, string $name): string
    {
        $modulePath = 'Modules/' . $this->pascalFromSlug($feature) . '/specs/' . $name . '.md';
        if (is_file($this->paths->join($modulePath)) || is_dir($this->paths->join('Modules/' . $this->pascalFromSlug($feature)))) {
            return $modulePath;
        }

        return 'Features/' . $this->pascalFromSlug($feature) . '/specs/' . $name . '.md';
    }

    private function moduleReconstructionNotePath(string $moduleName, string $name): string
    {
        return 'Modules/' . $moduleName . '/outcomes/' . $name . '.md';
    }

    /**
     * @return list<string>
     */
    private function sectionHeadings(string $contents): array
    {
        $headings = [];

        foreach (preg_split('/\R/', $contents) ?: [] as $line) {
            $trimmed = trim($line);
            if (!str_starts_with($trimmed, '## ')) {
                continue;
            }

            $headings[] = trim(substr($trimmed, 3));
        }

        return $headings;
    }

    private function pascalFromSlug(string $slug): string
    {
        $parts = array_filter(explode('-', $slug), static fn(string $part): bool => $part !== '');

        return implode('', array_map(static fn(string $part): string => ucfirst($part), $parts));
    }

    private function slugFromPascal(string $value): string
    {
        $hyphenated = (string) preg_replace('/(?<!^)[A-Z]/', '-$0', $value);

        return strtolower($hyphenated);
    }
}
