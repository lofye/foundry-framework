<?php

declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\AI\AIManager;
use Foundry\AI\StaticAIProvider;
use Foundry\Auth\AuthorizationEngine;
use Foundry\Auth\HeaderTokenAuthenticator;
use Foundry\Auth\PermissionRegistry;
use Foundry\Cache\ArrayCacheStore;
use Foundry\Cache\CacheManager;
use Foundry\Cache\CacheRegistry;
use Foundry\DB\Connection;
use Foundry\DB\PdoQueryExecutor;
use Foundry\DB\QueryRegistry;
use Foundry\DB\TransactionManager;
use Foundry\Events\DefaultEventDispatcher;
use Foundry\Events\EventRegistry;
use Foundry\Feature\DefaultFeatureServices;
use Foundry\Feature\FeatureExecutor;
use Foundry\Feature\FeatureLoader;
use Foundry\Http\RequestContext;
use Foundry\Observability\AuditRecorder;
use Foundry\Observability\TraceContext;
use Foundry\Observability\TraceRecorder;
use Foundry\Queue\DefaultJobDispatcher;
use Foundry\Queue\JobRegistry;
use Foundry\Queue\SyncQueueDriver;
use Foundry\Schema\JsonSchemaValidator;
use Foundry\Storage\LocalStorageDriver;
use Foundry\Support\FoundryError;
use Foundry\Support\Paths;
use Foundry\Tests\Fixtures\TempProject;
use PHPUnit\Framework\TestCase;

final class FeatureExecutorEdgeCasesTest extends TestCase
{
    private TempProject $project;

    protected function setUp(): void
    {
        $this->project = new TempProject();
    }

    protected function tearDown(): void
    {
        $this->project->cleanup();
    }

    public function test_route_not_found_throws(): void
    {
        file_put_contents($this->project->root . '/app/generated/feature_index.php', '<?php return [];');
        file_put_contents($this->project->root . '/app/generated/routes.php', '<?php return [];');

        $executor = $this->makeExecutor($this->project->root, ['posts.create']);

        $this->expectException(FoundryError::class);
        $executor->executeHttp(new RequestContext('GET', '/missing'));
    }

    public function test_authorization_denied_throws(): void
    {
        $this->writeFeature('secure_feature', '/secure', true, <<<'PHP'
<?php
declare(strict_types=1);
namespace App\Features\SecureFeature;
use Foundry\Feature\FeatureAction;
use Foundry\Feature\FeatureServices;
use Foundry\Auth\AuthContext;
use Foundry\Http\RequestContext;
final class Action implements FeatureAction { public function handle(array $input, RequestContext $request, AuthContext $auth, FeatureServices $services): array { return []; } }
PHP);

        $executor = $this->makeExecutor($this->project->root, []);

        $this->expectException(FoundryError::class);
        $executor->executeHttp(new RequestContext('GET', '/secure', ['x-user-id' => 'u1']));
    }

    public function test_output_schema_violation_throws(): void
    {
        $this->writeFeature('bad_output', '/bad-output', false, <<<'PHP'
<?php
declare(strict_types=1);
namespace App\Features\BadOutput;
use Foundry\Feature\FeatureAction;
use Foundry\Feature\FeatureServices;
use Foundry\Auth\AuthContext;
use Foundry\Http\RequestContext;
final class Action implements FeatureAction { public function handle(array $input, RequestContext $request, AuthContext $auth, FeatureServices $services): array { return []; } }
PHP);

        $executor = $this->makeExecutor($this->project->root, ['posts.create']);

        $this->expectException(FoundryError::class);
        $executor->executeHttp(new RequestContext('GET', '/bad-output'));
    }

    public function test_missing_action_class_throws(): void
    {
        $this->writeFeature(
            'missing_action',
            '/missing-action',
            false,
            '',
            actionClass: 'App\\Features\\MissingAction\\MissingAction',
            writeAction: false,
        );

        $executor = $this->makeExecutor($this->project->root, ['posts.create']);

        $this->expectException(FoundryError::class);
        $this->expectExceptionMessage('Feature action class not found');

        $executor->executeHttp(new RequestContext('GET', '/missing-action'));
    }

    public function test_action_class_must_implement_feature_action_contract(): void
    {
        $this->writeFeature(
            'wrong_contract',
            '/wrong-contract',
            false,
            '',
            actionClass: NotAFeatureAction::class,
            writeAction: false,
        );

        $executor = $this->makeExecutor($this->project->root, ['posts.create']);

        $this->expectException(FoundryError::class);
        $this->expectExceptionMessage('Action must implement FeatureAction');

        $executor->executeHttp(new RequestContext('GET', '/wrong-contract'));
    }

    public function test_empty_action_class_uses_canonical_feature_action_fallback(): void
    {
        $this->writeFeature('fallback_action', '/fallback-action', false, <<<'PHP'
<?php
declare(strict_types=1);
namespace App\Features\FallbackAction;
use Foundry\Feature\FeatureAction;
use Foundry\Feature\FeatureServices;
use Foundry\Auth\AuthContext;
use Foundry\Http\RequestContext;
final class Action implements FeatureAction { public function handle(array $input, RequestContext $request, AuthContext $auth, FeatureServices $services): array { return ['id' => 'ok']; } }
PHP, actionClass: '');

        $executor = $this->makeExecutor($this->project->root, ['posts.create']);

        $this->assertSame(['id' => 'ok'], $executor->executeHttp(new RequestContext('GET', '/fallback-action')));
    }

    public function test_absolute_schema_paths_are_used_without_joining_workspace_root(): void
    {
        $absoluteOutputSchema = $this->project->root . '/absolute-output.schema.json';
        file_put_contents($absoluteOutputSchema, '{"$schema":"https://json-schema.org/draft/2020-12/schema","type":"object","required":["id"],"properties":{"id":{"type":"string"}}}');
        $this->writeFeature('absolute_schema', '/absolute-schema', false, <<<'PHP'
<?php
declare(strict_types=1);
namespace App\Features\AbsoluteSchema;
use Foundry\Feature\FeatureAction;
use Foundry\Feature\FeatureServices;
use Foundry\Auth\AuthContext;
use Foundry\Http\RequestContext;
final class Action implements FeatureAction { public function handle(array $input, RequestContext $request, AuthContext $auth, FeatureServices $services): array { return ['id' => 'absolute']; } }
PHP, outputSchema: $absoluteOutputSchema);

        $executor = $this->makeExecutor($this->project->root, ['posts.create']);

        $this->assertSame(['id' => 'absolute'], $executor->executeHttp(new RequestContext('GET', '/absolute-schema')));
    }

    private function makeExecutor(string $root, array $permissions): FeatureExecutor
    {
        $perm = new PermissionRegistry();
        foreach ($permissions as $permission) {
            $perm->register($permission);
        }

        $authorization = new AuthorizationEngine($perm, ['bearer' => new HeaderTokenAuthenticator('x-user-id')]);

        $pdo = new \PDO('sqlite::memory:');
        $db = new PdoQueryExecutor(new Connection($pdo), new QueryRegistry());

        $traceContext = new TraceContext('trace-fixed');
        $trace = new TraceRecorder($traceContext);

        $services = new DefaultFeatureServices(
            $db,
            new CacheManager(new ArrayCacheStore(), new CacheRegistry()),
            new DefaultJobDispatcher(new JobRegistry(), new SyncQueueDriver(), $trace),
            new DefaultEventDispatcher(new EventRegistry(), $trace),
            new LocalStorageDriver($root . '/tmp-storage'),
            $traceContext,
            new AIManager(['static' => new StaticAIProvider('static', ['content' => 'ok'])]),
        );

        return new FeatureExecutor(
            new FeatureLoader(Paths::fromCwd($root)),
            $authorization,
            new JsonSchemaValidator(),
            new TransactionManager(new Connection($pdo)),
            $services,
            $trace,
            new AuditRecorder(),
            Paths::fromCwd($root),
        );
    }

    private function writeFeature(
        string $name,
        string $path,
        bool $authRequired,
        string $actionCode,
        ?string $actionClass = null,
        bool $writeAction = true,
        ?string $outputSchema = null,
    ): void
    {
        $studly = str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $name)));
        $featureDir = $this->project->root . '/Features/' . $studly;
        mkdir($featureDir . '/src', 0777, true);

        if ($writeAction) {
            file_put_contents($featureDir . '/src/Action.php', $actionCode);
        }

        file_put_contents($featureDir . '/input.schema.json', '{"$schema":"https://json-schema.org/draft/2020-12/schema","type":"object","additionalProperties":false,"properties":{}}');
        file_put_contents($featureDir . '/output.schema.json', '{"$schema":"https://json-schema.org/draft/2020-12/schema","type":"object","additionalProperties":false,"required":["id"],"properties":{"id":{"type":"string"}}}');

        $inputSchema = 'Features/' . $studly . '/input.schema.json';
        $outputSchema ??= 'Features/' . $studly . '/output.schema.json';
        $basePath = 'Features/' . $studly;
        $actionClassLiteral = $actionClass === null
            ? 'App\\\\Features\\\\' . $studly . '\\\\Action'
            : str_replace('\\', '\\\\', $actionClass);
        $permissionsLiteral = $authRequired ? "['posts.create']" : '[]';

        file_put_contents($this->project->root . '/app/generated/feature_index.php', "<?php return ['{$name}' => ['kind' => 'http', 'description' => 'x', 'route' => ['method' => 'GET', 'path' => '{$path}'], 'input_schema' => '{$inputSchema}', 'output_schema' => '{$outputSchema}', 'auth' => ['required' => " . ($authRequired ? 'true' : 'false') . ", 'strategies' => ['bearer'], 'permissions' => {$permissionsLiteral}], 'database' => ['transactions' => 'required'], 'cache' => [], 'events' => [], 'jobs' => [], 'rate_limit' => [], 'tests' => [], 'llm' => [], 'base_path' => '{$basePath}', 'action_class' => '{$actionClassLiteral}']];");

        file_put_contents($this->project->root . '/app/generated/routes.php', "<?php return ['GET {$path}' => ['feature' => '{$name}', 'kind' => 'http', 'input_schema' => '{$inputSchema}', 'output_schema' => '{$outputSchema}']];");
    }
}

final class NotAFeatureAction {}
