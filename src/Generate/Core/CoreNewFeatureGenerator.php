<?php

declare(strict_types=1);

namespace Foundry\Generate\Core;

use Foundry\Explain\ExplainModel;
use Foundry\Generate\FeaturePlanBuilder;
use Foundry\Generate\GenerationPlan;
use Foundry\Generate\Generator;
use Foundry\Generate\Intent;
use Foundry\Support\Str;

final class CoreNewFeatureGenerator implements Generator
{
    #[\Override]
    public function supports(ExplainModel $model, Intent $intent): bool
    {
        return $intent->mode === 'new';
    }

    #[\Override]
    public function plan(ExplainModel $model, Intent $intent): GenerationPlan
    {
        $subject = $model->subject;
        $metadata = is_array($subject['metadata'] ?? null) ? $subject['metadata'] : [];
        $explainNodeId = (string) ($subject['id'] ?? 'system:root');
        [$singular, $plural, $baseRoute] = $this->resourceContext($subject, $metadata, $intent);
        $action = $this->actionToken($intent, $singular, $plural);
        $feature = Str::toSnakeCase($action . '_' . $singular);
        $requiredTests = ['contract', 'feature', 'auth'];
        $definition = [
            'feature' => $feature,
            'description' => ucfirst($action) . ' a ' . str_replace('_', ' ', $singular) . '.',
            'kind' => 'http',
            'owners' => ['platform'],
            'route' => [
                'method' => $this->routeMethod($intent),
                'path' => $this->routePath($action, $plural, $baseRoute),
            ],
            'input' => [
                'fields' => [
                    'id' => ['type' => 'string', 'required' => true],
                ],
            ],
            'output' => [
                'fields' => [
                    'id' => ['type' => 'string', 'required' => true],
                    'status' => ['type' => 'string', 'required' => true],
                ],
            ],
            'auth' => [
                'required' => true,
                'strategies' => array_values((array) ($metadata['auth']['strategies'] ?? ['bearer'])),
                'permissions' => [$plural . '.' . $action],
            ],
            'database' => [
                'reads' => [$plural],
                'writes' => [$plural],
                'transactions' => 'required',
                'queries' => [$feature],
            ],
            'cache' => [
                'reads' => [],
                'writes' => [],
                'invalidate' => [$plural . ':list'],
            ],
            'events' => [
                'emit' => [$singular . '.' . $this->pastTense($action)],
                'subscribe' => [],
            ],
            'jobs' => [
                'dispatch' => [],
            ],
            'tests' => [
                'required' => $requiredTests,
            ],
            'llm' => [
                'editable' => true,
                'risk_level' => 'medium',
                'notes_file' => 'prompts.md',
            ],
        ];

        return new GenerationPlan(
            actions: FeaturePlanBuilder::scaffoldActions($feature, $requiredTests, $explainNodeId),
            affectedFiles: FeaturePlanBuilder::predictedFiles($feature, $requiredTests),
            risks: ['Creates a new feature scaffold and compile artifacts for `' . $feature . '`.'],
            validations: ['compile_graph', 'verify_graph', 'verify_contracts', 'verify_feature'],
            origin: 'core',
            generatorId: 'core.feature.new',
            extension: null,
            metadata: [
                'execution' => [
                    'strategy' => 'feature_definition',
                    'feature_definition' => $definition,
                ],
                'feature' => $feature,
                'context_subject' => [
                    'id' => $subject['id'] ?? 'system:root',
                    'kind' => $subject['kind'] ?? 'system',
                ],
            ],
        );
    }

    /**
     * @param array<string,mixed> $subject
     * @param array<string,mixed> $metadata
     * @return array{0:string,1:string,2:?string}
     */
    private function resourceContext(array $subject, array $metadata, Intent $intent): array
    {
        $baseRoute = trim((string) ($metadata['route']['path'] ?? ''));
        $resource = '';

        if ($baseRoute !== '') {
            $segments = array_values(array_filter(explode('/', $baseRoute), static fn(string $segment): bool => $segment !== '' && !str_starts_with($segment, '{')));
            if ($segments !== []) {
                $resource = (string) array_pop($segments);
            }
        }

        if ($resource === '') {
            $feature = trim((string) ($metadata['feature'] ?? $subject['label'] ?? ''));
            if ($feature !== '') {
                $parts = array_values(array_filter(explode('_', Str::toSnakeCase($feature))));
                if ($parts !== []) {
                    $resource = (string) array_pop($parts);
                }
            }
        }

        if ($resource === '') {
            $tokens = $intent->tokens();
            $resource = (string) array_pop($tokens);
        }

        $plural = $this->pluralize(Str::toSnakeCase($resource !== '' ? $resource : 'item'));
        $singular = $this->singularize($plural);

        return [$singular, $plural, $baseRoute !== '' ? $baseRoute : null];
    }

    private function actionToken(Intent $intent, string $singular, string $plural): string
    {
        $stopWords = [
            'a',
            'an',
            'add',
            'allow',
            'and',
            'build',
            'create',
            'feature',
            'for',
            'new',
            'repair',
            'support',
            'the',
            'to',
            'update',
            'with',
        ];

        foreach ($intent->tokens() as $token) {
            if (in_array($token, $stopWords, true) || in_array($token, [$singular, $plural], true)) {
                continue;
            }

            return Str::toSnakeCase($token);
        }

        return 'create';
    }

    private function routeMethod(Intent $intent): string
    {
        $tokens = $intent->tokens();

        return array_intersect($tokens, ['list', 'view', 'read', 'show']) !== [] ? 'GET' : 'POST';
    }

    private function routePath(string $action, string $plural, ?string $baseRoute): string
    {
        $route = $baseRoute !== null && $baseRoute !== '' ? rtrim($baseRoute, '/') : '/' . $plural;
        if (!str_contains($route, '/{id}') && !str_ends_with($route, '/' . $action)) {
            $route .= '/{id}';
        }

        if (!str_ends_with($route, '/' . $action)) {
            $route .= '/' . $action;
        }

        return $route;
    }

    private function pluralize(string $value): string
    {
        if (str_ends_with($value, 's')) {
            return $value;
        }

        if (str_ends_with($value, 'y')) {
            return substr($value, 0, -1) . 'ies';
        }

        return $value . 's';
    }

    private function singularize(string $value): string
    {
        if (str_ends_with($value, 'ies')) {
            return substr($value, 0, -3) . 'y';
        }

        if (str_ends_with($value, 's') && strlen($value) > 1) {
            return substr($value, 0, -1);
        }

        return $value;
    }

    private function pastTense(string $verb): string
    {
        if (str_ends_with($verb, 'e')) {
            return $verb . 'd';
        }

        return $verb . 'ed';
    }
}
