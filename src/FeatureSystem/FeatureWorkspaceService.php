<?php

declare(strict_types=1);

namespace Foundry\FeatureSystem;

use Foundry\Support\FeatureNaming;
use Foundry\Support\FoundryError;
use Foundry\Support\Paths;

final class FeatureWorkspaceService
{
    public function __construct(private readonly Paths $paths) {}

    /**
     * @return array{
     *     features:list<array<string,mixed>>,
     *     duplicates:list<array<string,mixed>>,
     *     misplaced_framework_modules:list<array<string,string>>
     * }
     */
    public function scan(): array
    {
        $rows = [];
        $duplicates = [];

        foreach ($this->canonicalFeatures() as $feature) {
            $slug = $feature['slug'];
            $rows[$slug] = $feature;
        }

        ksort($rows);

        return [
            'features' => array_values($rows),
            'duplicates' => $duplicates,
            'misplaced_framework_modules' => $this->misplacedFrameworkModules(),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function list(): array
    {
        $scan = $this->scan();
        $enforced = $this->boundaryEnforced();

        return [
            'features' => array_values(array_map(
                static fn(array $row): array => [
                    'slug' => $row['slug'],
                    'name' => $row['name'],
                    'path' => $row['path'],
                    'has_context' => $row['has_context'],
                    'has_specs' => $row['has_specs'],
                    'has_src' => $row['has_src'],
                    'has_tests' => $row['has_tests'],
                    'boundary_enforced' => $enforced,
                ],
                $scan['features'],
            )),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function inspect(string $feature): array
    {
        $feature = FeatureNaming::canonical($feature);
        $scan = $this->scan();

        foreach ((array) $scan['features'] as $row) {
            if (!is_array($row) || (string) ($row['slug'] ?? '') !== $feature) {
                continue;
            }

            return [
                'feature' => [
                    'slug' => $row['slug'],
                    'name' => $row['name'],
                    'path' => $row['path'],
                    'context' => $row['context'],
                    'directories' => $row['directories'],
                    'dependencies' => $this->dependencies($row),
                ],
            ];
        }

        throw new FoundryError('FEATURE_UNKNOWN', 'not_found', ['feature' => $feature], 'Feature not found in canonical or legacy workspace.');
    }

    /**
     * @return array<string,mixed>
     */
    public function map(): array
    {
        $scan = $this->scan();

        $features = [];
        foreach ((array) $scan['features'] as $row) {
            if (!is_array($row)) {
                continue;
            }

            $owned = $this->ownedPaths((string) $row['path']);
            $features[] = [
                'slug' => $row['slug'],
                'owned_paths' => $owned,
                'shared_glue_paths' => [],
            ];
        }

        usort($features, static fn(array $a, array $b): int => strcmp((string) $a['slug'], (string) $b['slug']));

        return [
            'features' => $features,
            'unowned_paths' => [],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function verify(): array
    {
        $scan = $this->scan();
        $enforced = $this->boundaryEnforced();

        $violations = [];
        if (is_dir($this->paths->join('app/features'))) {
            $violations[] = [
                'code' => 'APP_FEATURES_LEGACY_DIRECTORY_PRESENT',
                'feature' => '',
                'path' => 'app/features',
                'message' => 'Legacy app/features directory is not part of the current Foundry app layout. App feature source belongs under Features/<Feature>/.',
            ];
        }

        foreach ($this->legacyDocsFeatureContextViolations() as $violation) {
            $violations[] = $violation;
        }

        foreach ((array) $scan['duplicates'] as $duplicate) {
            if (!is_array($duplicate)) {
                continue;
            }

            $violations[] = [
                'code' => (string) ($duplicate['code'] ?? 'FEATURE_DUPLICATE_CANONICAL_AND_LEGACY'),
                'feature' => (string) ($duplicate['feature'] ?? ''),
                'path' => (string) ($duplicate['canonical_path'] ?? ''),
                'message' => 'Feature exists in both canonical and legacy locations.',
                'details' => [
                    'legacy_path' => (string) ($duplicate['legacy_path'] ?? ''),
                ],
            ];
        }

        foreach ((array) $scan['misplaced_framework_modules'] as $misplaced) {
            if (!is_array($misplaced)) {
                continue;
            }

            $path = (string) ($misplaced['path'] ?? '');
            $expectedPath = (string) ($misplaced['expected_path'] ?? '');
            $feature = (string) ($misplaced['feature'] ?? '');
            $isDuplicate = (bool) ($misplaced['duplicate'] ?? false);

            $violations[] = [
                'code' => $isDuplicate ? 'FRAMEWORK_MODULE_DUPLICATE_LOCATION' : 'FRAMEWORK_MODULE_IN_FEATURES_ROOT',
                'feature' => $feature,
                'path' => $path,
                'message' => $isDuplicate
                    ? 'Framework module exists in both Modules/ and Features/ roots.'
                    : 'Framework module governance directory is misplaced under Features/.',
                'details' => [
                    'expected_path' => $expectedPath,
                ],
            ];
        }

        if ($enforced) {
            foreach ((array) $scan['features'] as $row) {
                if (!is_array($row)) {
                    continue;
                }

                if ((bool) ($row['has_context'] ?? false)) {
                    continue;
                }

                $violations[] = [
                    'code' => 'FEATURE_MISSING_CONTEXT',
                    'feature' => (string) ($row['slug'] ?? ''),
                    'path' => (string) ($row['path'] ?? ''),
                    'message' => 'Feature is missing canonical context files.',
                ];
            }
        }

        foreach ($this->applicationFeatureLayoutViolations() as $violation) {
            $violations[] = $violation;
        }

        $warnings = [];
        if (!$enforced) {
            $warnings[] = [
                'code' => 'FEATURE_BOUNDARY_ENFORCEMENT_DISABLED',
                'message' => 'Feature boundary enforcement is disabled. This is not recommended.',
            ];
        }

        return [
            'status' => ($violations === []) ? 'ok' : 'failed',
            'enforcement' => $enforced ? 'enabled' : 'disabled',
            'violations' => $violations,
            'warnings' => $warnings,
        ];
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function canonicalFeatures(): array
    {
        $rootName = 'Features';
        $root = $this->paths->join($rootName);
        if (!is_dir($root)) {
            return [];
        }

        $items = scandir($root) ?: [];
        $rows = [];

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $root . '/' . $item;
            if (!is_dir($path)) {
                continue;
            }

            $slug = $this->detectSlug($path, $item);
            $rows[] = $this->row(
                slug: $slug,
                name: $item,
                relativePath: $rootName . '/' . $item,
                isCanonical: true,
            );
        }

        usort($rows, static fn(array $a, array $b): int => strcmp((string) $a['slug'], (string) $b['slug']));

        return $rows;
    }

    /**
     * @return array<string,mixed>
     */
    private function row(string $slug, string $name, string $relativePath, bool $isCanonical): array
    {
        $contextBase = $isCanonical
            ? $relativePath . '/' . $slug
            : $relativePath . '/' . $slug;

        $context = [
            'spec' => $contextBase . '.spec.md',
            'state' => $contextBase . '.md',
            'decisions' => $contextBase . '.decisions.md',
        ];

        $directories = [
            'specs' => $relativePath . '/specs',
            'outcomes' => $relativePath . '/outcomes',
            'docs' => $relativePath . '/docs',
            'src' => $relativePath . '/src',
            'tests' => $relativePath . '/tests',
        ];

        return [
            'slug' => $slug,
            'name' => $name,
            'path' => $relativePath,
            'legacy_path' => $isCanonical ? null : $relativePath,
            'has_context' => $this->allFilesExist($context),
            'has_specs' => is_dir($this->paths->join($directories['specs'])),
            'has_src' => is_dir($this->paths->join($directories['src'])),
            'has_tests' => is_dir($this->paths->join($directories['tests'])),
            'context' => $context,
            'directories' => $directories,
        ];
    }

    /**
     * @param array<string,string> $paths
     */
    private function allFilesExist(array $paths): bool
    {
        foreach ($paths as $path) {
            if (!is_file($this->paths->join($path))) {
                return false;
            }
        }

        return true;
    }

    private function detectSlug(string $absolutePath, string $name): string
    {
        $pattern = $absolutePath . '/*.spec.md';
        $matches = glob($pattern) ?: [];
        sort($matches);

        if ($matches !== []) {
            $file = basename((string) $matches[0]);

            return substr($file, 0, -strlen('.spec.md'));
        }

        $manifestPath = $absolutePath . '/feature.yaml';
        if (is_file($manifestPath)) {
            $contents = (string) file_get_contents($manifestPath);
            if (preg_match('/^feature:\s*([a-z0-9_-]+(?:[-_][a-z0-9]+)*)\s*$/m', $contents, $matches) === 1) {
                return FeatureNaming::canonical((string) $matches[1]);
            }
        }

        return FeatureNaming::canonical(strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $name) ?? $name));
    }

    /**
     * @param array<string, mixed> $row
     * @return list<string>
     */
    private function dependencies(array $row): array
    {
        $manifestPath = $row['path'] . '/feature.json';
        $absolute = $this->paths->join($manifestPath);
        if (!is_file($absolute)) {
            return [];
        }

        $decoded = json_decode((string) file_get_contents($absolute), true);
        if (!is_array($decoded)) {
            return [];
        }

        $dependencies = array_values(array_unique(array_map('strval', (array) ($decoded['dependencies'] ?? []))));
        sort($dependencies);

        return $dependencies;
    }

    /**
     * @return list<string>
     */
    private function ownedPaths(string $relativeRoot): array
    {
        $absoluteRoot = $this->paths->join($relativeRoot);
        if (!is_dir($absoluteRoot)) {
            return [];
        }

        $paths = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($absoluteRoot, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo instanceof \SplFileInfo || !$fileInfo->isFile()) {
                continue;
            }

            $absolute = $fileInfo->getPathname();
            $root = rtrim($this->paths->root(), '/') . '/';
            if (!str_starts_with($absolute, $root)) {
                continue;
            }

            $paths[] = substr($absolute, strlen($root));
        }

        sort($paths);

        return array_values(array_unique($paths));
    }

    private function boundaryEnforced(): bool
    {
        $configPhp = $this->paths->join('config/foundry/features.php');
        if (is_file($configPhp)) {
            $value = require $configPhp;
            if (is_array($value) && is_array($value['features'] ?? null)) {
                return (bool) (($value['features']['enforce_boundaries'] ?? true));
            }

            if (is_array($value)) {
                return (bool) ($value['enforce_boundaries'] ?? true);
            }
        }

        $configJson = $this->paths->join('.foundry/config/features.json');
        if (is_file($configJson)) {
            $decoded = json_decode((string) file_get_contents($configJson), true);
            if (is_array($decoded)) {
                return (bool) ($decoded['enforce_boundaries'] ?? true);
            }
        }

        return true;
    }

    private function pascalFromSlug(string $slug): string
    {
        $parts = array_filter(explode('-', $slug), static fn(string $part): bool => $part !== '');

        return implode('', array_map(static fn(string $part): string => ucfirst($part), $parts));
    }

    /**
     * @return list<array{feature:string,path:string,expected_path:string,duplicate:bool}>
     */
    private function misplacedFrameworkModules(): array
    {
        if (!is_dir($this->paths->join('Modules'))) {
            return [];
        }

        $featuresRoot = $this->paths->join('Features');
        if (!is_dir($featuresRoot)) {
            return [];
        }

        $rows = [];
        foreach (scandir($featuresRoot) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $featurePath = $featuresRoot . '/' . $item;
            if (!is_dir($featurePath) || preg_match('/^[A-Z][A-Za-z0-9]*$/', $item) !== 1) {
                continue;
            }

            if (is_file($featurePath . '/feature.yaml')) {
                continue;
            }

            $expected = 'Modules/' . $item;
            if (!is_dir($this->paths->join($expected))) {
                continue;
            }

            $slug = FeatureNaming::canonical(strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $item)));
            $rows[] = [
                'feature' => $slug,
                'path' => 'Features/' . $item,
                'expected_path' => $expected,
                'duplicate' => is_dir($this->paths->join($expected)),
            ];
        }

        usort($rows, static fn(array $a, array $b): int => strcmp($a['path'], $b['path']));

        return $rows;
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function applicationFeatureLayoutViolations(): array
    {
        $violations = [];

        foreach ($this->applicationFeatures() as $feature) {
            $slug = (string) ($feature['slug'] ?? '');
            $path = (string) ($feature['path'] ?? '');
            $name = (string) ($feature['name'] ?? '');
            $directories = (array) ($feature['directories'] ?? []);
            $isExecutable = $this->isExecutableApplicationFeature($path);

            if (!(bool) ($feature['has_context'] ?? false)) {
                $violations[] = [
                    'code' => 'APP_FEATURE_MISSING_CONTEXT',
                    'feature' => $slug,
                    'path' => $path,
                    'message' => 'Application feature is missing canonical context files under its feature root.',
                ];
            }

            if ($isExecutable && !(bool) ($feature['has_src'] ?? false)) {
                $violations[] = [
                    'code' => 'APP_FEATURE_RUNTIME_SRC_MISSING',
                    'feature' => $slug,
                    'path' => (string) ($directories['src'] ?? ($path . '/src')),
                    'message' => 'Executable application feature must contain Features/<Feature>/src/.',
                    'details' => [
                        'expected_path' => (string) ($directories['src'] ?? ($path . '/src')),
                    ],
                ];
            }

            if ($isExecutable && !(bool) ($feature['has_tests'] ?? false)) {
                $violations[] = [
                    'code' => 'APP_FEATURE_RUNTIME_TESTS_MISSING',
                    'feature' => $slug,
                    'path' => (string) ($directories['tests'] ?? ($path . '/tests')),
                    'message' => 'Executable application feature must contain Features/<Feature>/tests/.',
                    'details' => [
                        'expected_path' => (string) ($directories['tests'] ?? ($path . '/tests')),
                    ],
                ];
            }

            foreach (['specs', 'outcomes', 'docs'] as $kind) {
                $directoryPath = (string) ($directories[$kind] ?? '');
                if ($directoryPath === '') {
                    continue;
                }

                $absoluteDirectoryPath = $this->paths->join($directoryPath);
                if (file_exists($absoluteDirectoryPath) && !is_dir($absoluteDirectoryPath)) {
                    $violations[] = [
                        'code' => 'APP_FEATURE_' . strtoupper($kind) . '_PATH_INVALID',
                        'feature' => $slug,
                        'path' => $directoryPath,
                        'message' => sprintf('Application feature %s path must be a directory when present.', $kind),
                    ];
                }
            }

        }

        return $violations;
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function applicationFeatures(): array
    {
        $root = $this->paths->join('Features');
        if (!is_dir($root)) {
            return [];
        }

        $rows = [];
        foreach (scandir($root) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $root . '/' . $item;
            if (!is_dir($path) || preg_match('/^[A-Z][A-Za-z0-9]*$/', $item) !== 1) {
                continue;
            }

            if (is_dir($this->paths->join('Modules/' . $item)) && !is_file($path . '/feature.yaml')) {
                continue;
            }

            $slug = $this->detectSlug($path, $item);
            $rows[] = $this->row(
                slug: $slug,
                name: $item,
                relativePath: 'Features/' . $item,
                isCanonical: true,
            );
        }

        usort($rows, static fn(array $a, array $b): int => strcmp((string) ($a['path'] ?? ''), (string) ($b['path'] ?? '')));

        return $rows;
    }

    private function isExecutableApplicationFeature(string $featurePath): bool
    {
        $manifestPath = $this->paths->join($featurePath . '/feature.json');
        if (!is_file($manifestPath)) {
            return true;
        }

        $decoded = json_decode((string) file_get_contents($manifestPath), true);
        if (!is_array($decoded)) {
            return true;
        }

        if (array_key_exists('executable', $decoded)) {
            return (bool) $decoded['executable'];
        }

        return true;
    }

    /**
     * @return list<array<string,mixed>>
     */
    private function legacyDocsFeatureContextViolations(): array
    {
        $root = $this->paths->join('docs/features');
        if (!is_dir($root)) {
            return [];
        }

        $violations = [];
        foreach (scandir($root) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $root . '/' . $item;
            if (!is_dir($path) || preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $item) !== 1) {
                continue;
            }

            $legacyContextPaths = [
                $item . '.spec.md',
                $item . '.md',
                $item . '.decisions.md',
                'specs',
                'outcomes',
                'plans',
            ];

            foreach ($legacyContextPaths as $legacyContextPath) {
                if (!file_exists($path . '/' . $legacyContextPath)) {
                    continue;
                }

                $violations[] = [
                    'code' => 'DOCS_FEATURES_LEGACY_CONTEXT_PRESENT',
                    'feature' => $item,
                    'path' => 'docs/features/' . $item,
                    'message' => 'Legacy docs/features application context is not part of the current Foundry app layout. App feature context belongs under Features/<Feature>/.',
                    'details' => [
                        'legacy_path' => 'docs/features/' . $item . '/' . $legacyContextPath,
                    ],
                ];

                break;
            }
        }

        usort($violations, static fn(array $a, array $b): int => strcmp((string) $a['path'], (string) $b['path']));

        return $violations;
    }

}
