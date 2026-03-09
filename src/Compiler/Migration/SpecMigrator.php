<?php
declare(strict_types=1);

namespace Foundry\Compiler\Migration;

use Foundry\Compiler\Diagnostics\DiagnosticBag;
use Foundry\Support\Paths;
use Foundry\Support\Yaml;

final class SpecMigrator
{
    /**
     * @param array<int,MigrationRule> $rules
     */
    public function __construct(
        private readonly Paths $paths,
        private readonly ManifestVersionResolver $resolver,
        private readonly array $rules,
    ) {
    }

    public function migrate(bool $write = false, ?DiagnosticBag $diagnostics = null): SpecMigrationResult
    {
        $changes = [];
        $inlineDiagnostics = [];

        $featureDirs = glob($this->paths->features() . '/*', GLOB_ONLYDIR) ?: [];
        sort($featureDirs);

        foreach ($featureDirs as $dir) {
            $manifestPath = $dir . '/feature.yaml';
            if (!is_file($manifestPath)) {
                continue;
            }

            $relativePath = $this->relativePath($manifestPath);

            $document = Yaml::parseFile($manifestPath);
            $currentVersion = $this->resolver->resolveFeatureVersion($document);

            if ($this->resolver->isFeatureOutdated($document)) {
                $message = sprintf(
                    'Outdated feature manifest version %d detected; expected %d.',
                    $currentVersion,
                    $this->resolver->currentFeatureVersion(),
                );
                $inlineDiagnostics[] = [
                    'code' => 'FDY3001_OUTDATED_FEATURE_MANIFEST',
                    'severity' => 'warning',
                    'category' => 'migrations',
                    'message' => $message,
                    'source_path' => $relativePath,
                ];

                if ($diagnostics !== null) {
                    $diagnostics->warning('FDY3001_OUTDATED_FEATURE_MANIFEST', 'migrations', $message, sourcePath: $relativePath, pass: 'migrate_specs');
                }
            }

            $appliedRuleIds = [];
            $migrated = $document;
            foreach ($this->rules as $rule) {
                if (!$rule->applies($relativePath, $migrated)) {
                    continue;
                }

                $migrated = $rule->migrate($relativePath, $migrated);
                $appliedRuleIds[] = $rule->id();
            }

            if ($appliedRuleIds === []) {
                continue;
            }

            $changes[] = [
                'path' => $relativePath,
                'rules' => $appliedRuleIds,
                'from_version' => $currentVersion,
                'to_version' => $this->resolver->resolveFeatureVersion($migrated),
                'write' => $write,
            ];

            if ($write) {
                file_put_contents($manifestPath, Yaml::dump($migrated));
            }
        }

        return new SpecMigrationResult(
            written: $write,
            changes: $changes,
            diagnostics: $inlineDiagnostics,
        );
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function inspect(): array
    {
        $rows = [];
        foreach ($this->rules as $rule) {
            $rows[] = [
                'id' => $rule->id(),
                'description' => $rule->description(),
                'source_type' => $rule->sourceType(),
            ];
        }

        usort($rows, static fn (array $a, array $b): int => strcmp((string) $a['id'], (string) $b['id']));

        return $rows;
    }

    private function relativePath(string $absolutePath): string
    {
        $root = rtrim($this->paths->root(), '/') . '/';

        return str_starts_with($absolutePath, $root)
            ? substr($absolutePath, strlen($root))
            : $absolutePath;
    }
}
