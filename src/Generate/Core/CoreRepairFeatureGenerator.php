<?php

declare(strict_types=1);

namespace Foundry\Generate\Core;

use Foundry\Explain\ExplainModel;
use Foundry\Generate\GenerationPlan;
use Foundry\Generate\Generator;
use Foundry\Generate\Intent;
use Foundry\Support\Yaml;

final class CoreRepairFeatureGenerator implements Generator
{
    #[\Override]
    public function supports(ExplainModel $model, Intent $intent): bool
    {
        return $intent->mode === 'repair'
            && (string) ($model->subject['kind'] ?? '') === 'feature'
            && ((string) ($model->subject['origin'] ?? 'core')) === 'core';
    }

    #[\Override]
    public function plan(ExplainModel $model, Intent $intent): GenerationPlan
    {
        $subject = $model->subject;
        $metadata = is_array($subject['metadata'] ?? null) ? $subject['metadata'] : [];
        $feature = trim((string) ($metadata['feature'] ?? $subject['label'] ?? ''));
        $basePath = trim((string) ($metadata['base_path'] ?? 'app/features/' . $feature));
        $manifestPath = trim((string) ($metadata['manifest_path'] ?? ($basePath . '/feature.yaml')));
        $manifest = is_file($manifestPath) ? Yaml::parseFile($manifestPath) : [];
        $requiredTests = array_values(array_map('strval', (array) ($metadata['tests']['required'] ?? $manifest['tests']['required'] ?? [])));
        sort($requiredTests);

        $missingTests = [];
        foreach ($requiredTests as $type) {
            $path = $basePath . '/tests/' . $feature . '_' . $type . '_test.php';
            if (!is_file($path)) {
                $missingTests[] = $type;
            }
        }

        $contextManifestPath = $basePath . '/context.manifest.json';
        $restoreContextManifest = !is_file($contextManifestPath);
        $actions = [];

        foreach ($missingTests as $type) {
            $actions[] = [
                'type' => 'add_test',
                'path' => $basePath . '/tests/' . $feature . '_' . $type . '_test.php',
                'summary' => 'Restore missing `' . $type . '` test for `' . $feature . '`.',
                'explain_node_id' => (string) ($subject['id'] ?? 'feature:' . $feature),
                'origin' => 'core',
                'extension' => null,
            ];
        }

        if ($restoreContextManifest) {
            $actions[] = [
                'type' => 'create_file',
                'path' => $contextManifestPath,
                'summary' => 'Restore context manifest for `' . $feature . '`.',
                'explain_node_id' => (string) ($subject['id'] ?? 'feature:' . $feature),
                'origin' => 'core',
                'extension' => null,
            ];
        }

        $affectedFiles = array_values(array_map(
            static fn(array $action): string => (string) ($action['path'] ?? ''),
            $actions,
        ));
        sort($affectedFiles);

        return new GenerationPlan(
            actions: $actions,
            affectedFiles: $affectedFiles,
            risks: $actions === [] ? [] : ['Restores missing generated support files for `' . $feature . '`.'],
            validations: ['compile_graph', 'verify_graph', 'verify_contracts', 'verify_feature'],
            origin: 'core',
            generatorId: 'core.feature.repair',
            extension: null,
            metadata: [
                'execution' => [
                    'strategy' => 'repair_feature',
                    'feature' => $feature,
                    'base_path' => $basePath,
                    'manifest' => $manifest,
                    'missing_tests' => $missingTests,
                    'restore_context_manifest' => $restoreContextManifest,
                ],
                'feature' => $feature,
            ],
        );
    }
}
