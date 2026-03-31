<?php

declare(strict_types=1);

namespace Foundry\Generate;

final class FeaturePlanBuilder
{
    /**
     * @param array<int,string> $requiredTests
     * @return array<int,string>
     */
    public static function predictedFiles(string $feature, array $requiredTests): array
    {
        $base = 'app/features/' . $feature;
        $files = [
            $base . '/feature.yaml',
            $base . '/input.schema.json',
            $base . '/output.schema.json',
            $base . '/action.php',
            $base . '/queries.sql',
            $base . '/permissions.yaml',
            $base . '/cache.yaml',
            $base . '/events.yaml',
            $base . '/jobs.yaml',
            $base . '/prompts.md',
            $base . '/context.manifest.json',
        ];

        foreach ($requiredTests as $type) {
            $files[] = $base . '/tests/' . $feature . '_' . $type . '_test.php';
        }

        $files = array_values(array_unique(array_map('strval', $files)));
        sort($files);

        return $files;
    }

    /**
     * @param array<int,string> $requiredTests
     * @return array<int,array<string,mixed>>
     */
    public static function scaffoldActions(string $feature, array $requiredTests, string $explainNodeId): array
    {
        $actions = [];

        foreach (self::predictedFiles($feature, $requiredTests) as $path) {
            $type = str_contains($path, '/tests/') ? 'add_test' : 'create_file';
            $actions[] = [
                'type' => $type,
                'path' => $path,
                'summary' => 'Scaffold ' . $path,
                'explain_node_id' => $explainNodeId,
                'origin' => 'core',
                'extension' => null,
            ];
        }

        return $actions;
    }
}
