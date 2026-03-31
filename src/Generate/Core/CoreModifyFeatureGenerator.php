<?php

declare(strict_types=1);

namespace Foundry\Generate\Core;

use Foundry\Explain\ExplainModel;
use Foundry\Generate\GenerationPlan;
use Foundry\Generate\Generator;
use Foundry\Generate\Intent;
use Foundry\Support\Str;
use Foundry\Support\Yaml;

final class CoreModifyFeatureGenerator implements Generator
{
    #[\Override]
    public function supports(ExplainModel $model, Intent $intent): bool
    {
        return $intent->mode === 'modify'
            && (string) ($model->subject['kind'] ?? '') === 'feature'
            && ((string) ($model->subject['origin'] ?? 'core')) === 'core';
    }

    #[\Override]
    public function plan(ExplainModel $model, Intent $intent): GenerationPlan
    {
        $subject = $model->subject;
        $metadata = is_array($subject['metadata'] ?? null) ? $subject['metadata'] : [];
        $manifestPath = trim((string) ($metadata['manifest_path'] ?? ''));
        $basePath = trim((string) ($metadata['base_path'] ?? ''));
        $feature = trim((string) ($metadata['feature'] ?? $subject['label'] ?? ''));
        $manifest = $manifestPath !== '' && is_file($manifestPath)
            ? Yaml::parseFile($manifestPath)
            : [];
        $manifest['description'] = $this->updatedDescription((string) ($manifest['description'] ?? ''), $intent->raw);
        $promptsPath = ($basePath !== '' ? $basePath : 'app/features/' . $feature) . '/prompts.md';
        $promptsContent = $this->updatedPrompts($promptsPath, $feature, $intent);
        $explainNodeId = (string) ($subject['id'] ?? 'feature:' . $feature);

        return new GenerationPlan(
            actions: [
                [
                    'type' => 'update_file',
                    'path' => $manifestPath,
                    'summary' => 'Update feature manifest description for `' . $feature . '`.',
                    'explain_node_id' => $explainNodeId,
                    'origin' => 'core',
                    'extension' => null,
                ],
                [
                    'type' => 'update_docs',
                    'path' => $promptsPath,
                    'summary' => 'Update managed prompt notes for `' . $feature . '`.',
                    'explain_node_id' => $explainNodeId,
                    'origin' => 'core',
                    'extension' => null,
                ],
            ],
            affectedFiles: array_values(array_filter([$manifestPath, $promptsPath])),
            risks: ['Updates feature intent metadata for `' . $feature . '` without changing pack-owned nodes.'],
            validations: ['compile_graph', 'verify_graph', 'verify_contracts', 'verify_feature'],
            origin: 'core',
            generatorId: 'core.feature.modify',
            extension: null,
            metadata: [
                'execution' => [
                    'strategy' => 'modify_feature',
                    'manifest_path' => $manifestPath,
                    'manifest' => $manifest,
                    'prompts_path' => $promptsPath,
                    'prompts_content' => $promptsContent,
                ],
                'feature' => $feature,
            ],
        );
    }

    private function updatedDescription(string $existing, string $intent): string
    {
        $existing = trim($existing);
        $suffix = ' Modification intent: ' . ucfirst(trim($intent)) . '.';

        if ($existing === '') {
            return ucfirst(trim($intent)) . '.';
        }

        $existing = preg_replace('/\s+Modification intent: .*$/', '', $existing) ?? $existing;

        return rtrim($existing, '.') . '.' . $suffix;
    }

    private function updatedPrompts(string $promptsPath, string $feature, Intent $intent): string
    {
        $existing = is_file($promptsPath)
            ? (string) (file_get_contents($promptsPath) ?: '')
            : '# ' . Str::studly($feature) . "\n\n";

        $block = implode("\n", [
            '<!-- foundry:generate:intent:start -->',
            'Latest generate intent: ' . trim($intent->raw),
            'Mode: ' . $intent->mode,
            '<!-- foundry:generate:intent:end -->',
        ]);

        $pattern = '/<!-- foundry:generate:intent:start -->.*?<!-- foundry:generate:intent:end -->/s';
        if (preg_match($pattern, $existing) === 1) {
            $updated = preg_replace($pattern, $block, $existing);

            return is_string($updated) ? $updated : $existing;
        }

        $trimmed = rtrim($existing);

        return $trimmed . "\n\n" . $block . "\n";
    }
}
