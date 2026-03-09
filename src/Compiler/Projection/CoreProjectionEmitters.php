<?php
declare(strict_types=1);

namespace Foundry\Compiler\Projection;

use Foundry\Compiler\ApplicationGraph;
use Foundry\Compiler\IR\GraphNode;

final class CoreProjectionEmitters
{
    /**
     * @return array<int,ProjectionEmitter>
     */
    public static function all(): array
    {
        return [
            new GenericProjectionEmitter('routes', 'routes_index.php', 'routes.php', self::routesBuilder()),
            new GenericProjectionEmitter('feature', 'feature_index.php', 'feature_index.php', self::featureBuilder()),
            new GenericProjectionEmitter('schema', 'schema_index.php', 'schema_index.php', self::schemaBuilder()),
            new GenericProjectionEmitter('permission', 'permission_index.php', 'permission_index.php', self::permissionBuilder()),
            new GenericProjectionEmitter('event', 'event_index.php', 'event_index.php', self::eventBuilder()),
            new GenericProjectionEmitter('job', 'job_index.php', 'job_index.php', self::jobBuilder()),
            new GenericProjectionEmitter('cache', 'cache_index.php', 'cache_index.php', self::cacheBuilder()),
            new GenericProjectionEmitter('scheduler', 'scheduler_index.php', 'scheduler_index.php', self::schedulerBuilder()),
            new GenericProjectionEmitter('webhook', 'webhook_index.php', 'webhook_index.php', self::webhookBuilder()),
            new GenericProjectionEmitter('query', 'query_index.php', 'query_index.php', self::queryBuilder()),
        ];
    }

    /**
     * @return callable(ApplicationGraph):array<string,mixed>
     */
    private static function routesBuilder(): callable
    {
        return static function (ApplicationGraph $graph): array {
            $routes = [];
            foreach (self::featurePayloads($graph) as $feature => $payload) {
                $route = is_array($payload['route'] ?? null) ? $payload['route'] : null;
                if ($route === null) {
                    continue;
                }

                $method = strtoupper((string) ($route['method'] ?? 'GET'));
                $path = (string) ($route['path'] ?? '/');
                if ($path === '') {
                    $path = '/';
                }

                $routes[$method . ' ' . $path] = [
                    'feature' => $feature,
                    'kind' => (string) ($payload['kind'] ?? 'http'),
                    'input_schema' => (string) ($payload['input_schema_path'] ?? ''),
                    'output_schema' => (string) ($payload['output_schema_path'] ?? ''),
                ];
            }

            ksort($routes);

            return $routes;
        };
    }

    /**
     * @return callable(ApplicationGraph):array<string,mixed>
     */
    private static function featureBuilder(): callable
    {
        return static function (ApplicationGraph $graph): array {
            $index = [];
            foreach (self::featurePayloads($graph) as $feature => $payload) {
                $index[$feature] = [
                    'kind' => (string) ($payload['kind'] ?? 'http'),
                    'description' => (string) ($payload['description'] ?? ''),
                    'route' => is_array($payload['route'] ?? null) ? $payload['route'] : null,
                    'input_schema' => (string) ($payload['input_schema_path'] ?? ''),
                    'output_schema' => (string) ($payload['output_schema_path'] ?? ''),
                    'auth' => is_array($payload['auth'] ?? null) ? $payload['auth'] : [],
                    'database' => is_array($payload['database'] ?? null) ? $payload['database'] : [],
                    'cache' => is_array($payload['cache'] ?? null) ? $payload['cache'] : [],
                    'events' => is_array($payload['events'] ?? null) ? $payload['events'] : [],
                    'jobs' => is_array($payload['jobs'] ?? null) ? $payload['jobs'] : [],
                    'rate_limit' => is_array($payload['rate_limit'] ?? null) ? $payload['rate_limit'] : [],
                    'tests' => is_array($payload['tests'] ?? null) ? $payload['tests'] : [],
                    'llm' => is_array($payload['llm'] ?? null) ? $payload['llm'] : [],
                    'base_path' => (string) ($payload['base_path'] ?? ('app/features/' . $feature)),
                    'action_class' => (string) ($payload['action_class'] ?? ''),
                ];
            }

            ksort($index);

            return $index;
        };
    }

    /**
     * @return callable(ApplicationGraph):array<string,mixed>
     */
    private static function schemaBuilder(): callable
    {
        return static function (ApplicationGraph $graph): array {
            $index = [];
            foreach (self::featurePayloads($graph) as $feature => $payload) {
                $index[$feature] = [
                    'input' => (string) ($payload['input_schema_path'] ?? ''),
                    'output' => (string) ($payload['output_schema_path'] ?? ''),
                ];
            }

            ksort($index);

            return $index;
        };
    }

    /**
     * @return callable(ApplicationGraph):array<string,mixed>
     */
    private static function permissionBuilder(): callable
    {
        return static function (ApplicationGraph $graph): array {
            $index = [];
            foreach (self::featurePayloads($graph) as $feature => $payload) {
                $auth = is_array($payload['auth'] ?? null) ? $payload['auth'] : [];
                $index[$feature] = [
                    'permissions' => array_values(array_map('strval', (array) ($auth['permissions'] ?? []))),
                ];
            }

            ksort($index);

            return $index;
        };
    }

    /**
     * @return callable(ApplicationGraph):array<string,mixed>
     */
    private static function eventBuilder(): callable
    {
        return static function (ApplicationGraph $graph): array {
            $emit = [];
            $subscribe = [];

            foreach (self::featurePayloads($graph) as $feature => $payload) {
                $events = is_array($payload['events'] ?? null) ? $payload['events'] : [];
                $emitDefs = is_array($events['emit_definitions'] ?? null) ? $events['emit_definitions'] : [];
                foreach ($emitDefs as $eventName => $schema) {
                    if (!is_string($eventName) || $eventName === '') {
                        continue;
                    }

                    $emit[$eventName] = [
                        'feature' => $feature,
                        'schema' => is_array($schema) ? $schema : [],
                    ];
                }

                foreach ((array) ($events['subscribe'] ?? []) as $eventName) {
                    $name = (string) $eventName;
                    if ($name === '') {
                        continue;
                    }

                    $subscribe[$name] ??= [];
                    $subscribe[$name][] = $feature;
                }
            }

            foreach ($subscribe as &$features) {
                sort($features);
                $features = array_values(array_unique($features));
            }
            unset($features);

            ksort($emit);
            ksort($subscribe);

            return ['emit' => $emit, 'subscribe' => $subscribe];
        };
    }

    /**
     * @return callable(ApplicationGraph):array<string,mixed>
     */
    private static function jobBuilder(): callable
    {
        return static function (ApplicationGraph $graph): array {
            $jobs = [];

            foreach (self::featurePayloads($graph) as $feature => $payload) {
                $jobDefs = (array) ($payload['jobs']['definitions'] ?? []);
                foreach ($jobDefs as $jobName => $jobDef) {
                    if (!is_string($jobName) || $jobName === '') {
                        continue;
                    }

                    $row = is_array($jobDef) ? $jobDef : [];
                    $row['feature'] = $feature;
                    $jobs[$jobName] = $row;
                }
            }

            ksort($jobs);

            return $jobs;
        };
    }

    /**
     * @return callable(ApplicationGraph):array<string,mixed>
     */
    private static function cacheBuilder(): callable
    {
        return static function (ApplicationGraph $graph): array {
            $cache = [];

            foreach (self::featurePayloads($graph) as $feature => $payload) {
                $entries = (array) ($payload['cache']['entries'] ?? []);
                foreach ($entries as $cacheKey => $entryDef) {
                    if (!is_string($cacheKey) || $cacheKey === '') {
                        continue;
                    }

                    $row = is_array($entryDef) ? $entryDef : [];
                    $row['feature'] = $feature;
                    $cache[$cacheKey] = $row;
                }
            }

            ksort($cache);

            return $cache;
        };
    }

    /**
     * @return callable(ApplicationGraph):array<string,mixed>
     */
    private static function schedulerBuilder(): callable
    {
        return static function (ApplicationGraph $graph): array {
            $tasks = [];

            foreach (self::featurePayloads($graph) as $feature => $payload) {
                foreach ((array) ($payload['scheduler']['tasks'] ?? []) as $task) {
                    if (!is_array($task)) {
                        continue;
                    }

                    $name = (string) ($task['name'] ?? '');
                    if ($name === '') {
                        continue;
                    }

                    $tasks[$feature . ':' . $name] = [
                        'feature' => $feature,
                        'name' => $name,
                        'cron' => (string) ($task['cron'] ?? ''),
                        'job' => (string) ($task['job'] ?? ''),
                    ];
                }
            }

            ksort($tasks);

            return $tasks;
        };
    }

    /**
     * @return callable(ApplicationGraph):array<string,mixed>
     */
    private static function webhookBuilder(): callable
    {
        return static function (ApplicationGraph $graph): array {
            $webhooks = [
                'incoming' => [],
                'outgoing' => [],
            ];

            foreach (self::featurePayloads($graph) as $feature => $payload) {
                $hooks = is_array($payload['webhooks'] ?? null) ? $payload['webhooks'] : [];
                foreach (['incoming', 'outgoing'] as $direction) {
                    foreach ((array) ($hooks[$direction] ?? []) as $hook) {
                        if (!is_array($hook)) {
                            continue;
                        }

                        $name = (string) ($hook['name'] ?? '');
                        if ($name === '') {
                            continue;
                        }

                        $webhooks[$direction][$name] = array_merge(
                            ['feature' => $feature],
                            $hook,
                        );
                    }
                }
            }

            ksort($webhooks['incoming']);
            ksort($webhooks['outgoing']);

            return $webhooks;
        };
    }

    /**
     * @return callable(ApplicationGraph):array<string,mixed>
     */
    private static function queryBuilder(): callable
    {
        return static function (ApplicationGraph $graph): array {
            $queries = [];

            foreach (self::featurePayloads($graph) as $feature => $payload) {
                foreach ((array) ($payload['queries'] ?? []) as $query) {
                    if (!is_array($query)) {
                        continue;
                    }

                    $name = (string) ($query['name'] ?? '');
                    if ($name === '') {
                        continue;
                    }

                    $queries[$feature . ':' . $name] = [
                        'feature' => $feature,
                        'name' => $name,
                        'sql' => (string) ($query['sql'] ?? ''),
                        'placeholders' => array_values(array_map('strval', (array) ($query['placeholders'] ?? []))),
                    ];
                }
            }

            ksort($queries);

            return $queries;
        };
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    private static function featurePayloads(ApplicationGraph $graph): array
    {
        $features = [];

        foreach ($graph->nodesByType('feature') as $node) {
            $payload = $node->payload();
            $feature = (string) ($payload['feature'] ?? '');
            if ($feature === '') {
                continue;
            }

            $features[$feature] = $payload;
        }

        ksort($features);

        return $features;
    }
}
