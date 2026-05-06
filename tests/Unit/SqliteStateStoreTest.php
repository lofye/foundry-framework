<?php

declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\State\SqliteStateStore;
use Foundry\Support\FoundryError;
use Foundry\Support\Paths;
use Foundry\Tests\Fixtures\TempProject;
use PHPUnit\Framework\TestCase;

final class SqliteStateStoreTest extends TestCase
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

    public function test_path_resolution_is_root_aware_and_does_not_depend_on_process_cwd(): void
    {
        $other = new TempProject();
        $cwd = getcwd() ?: '.';

        try {
            $store = new SqliteStateStore(new Paths($this->project->root));
            chdir($other->root);

            $store->set('tests', 'root-aware', 'ok');

            $this->assertSame('ok', $store->get('tests', 'root-aware'));
            $this->assertFileExists($this->project->root . '/.foundry/state/foundry.sqlite');
            $this->assertFileDoesNotExist($other->root . '/.foundry/state/foundry.sqlite');
        } finally {
            chdir($cwd);
            $other->cleanup();
        }
    }

    public function test_isolates_multiple_workspace_roots(): void
    {
        $other = new TempProject();

        try {
            $first = new SqliteStateStore(new Paths($this->project->root));
            $second = new SqliteStateStore(new Paths($other->root));

            $first->set('alpha', 'key', 'first');
            $second->set('alpha', 'key', 'second');

            $this->assertSame('first', $first->get('alpha', 'key'));
            $this->assertSame('second', $second->get('alpha', 'key'));
            $this->assertFileExists($this->project->root . '/.foundry/state/foundry.sqlite');
            $this->assertFileExists($other->root . '/.foundry/state/foundry.sqlite');
        } finally {
            $other->cleanup();
        }
    }

    public function test_schema_initialization_is_idempotent_and_preserves_values(): void
    {
        $store = $this->store();

        $this->assertSame(1, $store->ensureInitialized());
        $store->set('meta', 'version', 1);

        $this->assertSame(1, $store->ensureInitialized());
        $this->assertSame(1, $store->schemaVersion());
        $this->assertSame(1, $store->get('meta', 'version'));
    }

    public function test_store_identity_and_empty_state_queries_are_deterministic(): void
    {
        $store = $this->store();

        $this->assertSame('sqlite', $store->store());
        $this->assertSame('.foundry/state/foundry.sqlite', $store->relativePath());
        $this->assertStringEndsWith('/.foundry/state/foundry.sqlite', $store->absolutePath());
        $this->assertFalse($store->exists());
        $this->assertNull($store->schemaVersion());
        $this->assertSame([], $store->listKeys());
        $this->assertSame([], $store->listNamespaces());
        $this->assertFalse($store->delete('missing', 'key'));
    }

    public function test_typed_values_round_trip_with_type_preservation(): void
    {
        $store = $this->store();

        $store->set('types', 'string', 'value');
        $store->set('types', 'int', 42);
        $store->set('types', 'float', 1.5);
        $store->set('types', 'bool_true', true);
        $store->set('types', 'bool_false', false);
        $store->set('types', 'null', null);
        $store->set('types', 'array', ['a' => 1, 'b' => ['x' => true]]);
        $store->set('types', 'object', (object) ['name' => 'foundry']);

        $this->assertSame('value', $store->get('types', 'string'));
        $this->assertSame(42, $store->get('types', 'int'));
        $this->assertSame(1.5, $store->get('types', 'float'));
        $this->assertTrue($store->get('types', 'bool_true'));
        $this->assertFalse($store->get('types', 'bool_false'));
        $this->assertNull($store->get('types', 'null'));
        $this->assertSame(['a' => 1, 'b' => ['x' => true]], $store->get('types', 'array'));

        $object = $store->get('types', 'object');
        $this->assertInstanceOf(\stdClass::class, $object);
        $this->assertSame('foundry', $object->name);
    }

    public function test_listings_are_deterministic_and_sorted(): void
    {
        $store = $this->store();

        $store->set('zeta', 'b', 1);
        $store->set('alpha', 'z', 2);
        $store->set('alpha', 'a', 3);

        $this->assertSame([
            ['namespace' => 'alpha', 'keys' => 2],
            ['namespace' => 'zeta', 'keys' => 1],
        ], $store->listNamespaces());

        $this->assertSame([
            ['namespace' => 'alpha', 'key' => 'a', 'value_type' => 'int'],
            ['namespace' => 'alpha', 'key' => 'z', 'value_type' => 'int'],
            ['namespace' => 'zeta', 'key' => 'b', 'value_type' => 'int'],
        ], $store->listKeys());
    }

    public function test_list_keys_supports_namespace_filter_and_rejects_invalid_namespace(): void
    {
        $store = $this->store();
        $store->set('alpha', 'a', 1);
        $store->set('alpha', 'b', 2);
        $store->set('beta', 'c', 3);

        $this->assertSame([
            ['namespace' => 'alpha', 'key' => 'a', 'value_type' => 'int'],
            ['namespace' => 'alpha', 'key' => 'b', 'value_type' => 'int'],
        ], $store->listKeys('alpha'));

        try {
            $store->listKeys('  ');
            self::fail('Expected invalid namespace failure.');
        } catch (FoundryError $error) {
            $this->assertSame('STATE_STORE_NAMESPACE_INVALID', $error->errorCode);
        }
    }

    public function test_invalid_namespace_or_key_fails_deterministically(): void
    {
        $store = $this->store();

        try {
            $store->set('', 'key', 'value');
            self::fail('Expected invalid namespace failure.');
        } catch (FoundryError $error) {
            $this->assertSame('STATE_STORE_NAMESPACE_INVALID', $error->errorCode);
        }

        try {
            $store->set('namespace', '   ', 'value');
            self::fail('Expected invalid key failure.');
        } catch (FoundryError $error) {
            $this->assertSame('STATE_STORE_KEY_INVALID', $error->errorCode);
        }
    }

    public function test_has_and_delete_behave_deterministically(): void
    {
        $store = $this->store();

        $this->assertFalse($store->has('items', 'first'));
        $store->set('items', 'first', 'value');
        $this->assertTrue($store->has('items', 'first'));
        $this->assertTrue($store->delete('items', 'first'));
        $this->assertFalse($store->delete('items', 'first'));
        $this->assertFalse($store->has('items', 'first'));
    }

    public function test_has_and_delete_return_false_when_schema_is_invalid(): void
    {
        mkdir($this->project->root . '/.foundry/state', 0777, true);
        $pdo = new \PDO('sqlite:' . $this->project->root . '/.foundry/state/foundry.sqlite');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE unrelated_table (id INTEGER PRIMARY KEY)');

        $store = $this->store();
        $this->assertFalse($store->has('invalid', 'key'));
        $this->assertFalse($store->delete('invalid', 'key'));
    }

    public function test_inspect_metadata_reports_schema_invalid_for_unexpected_database_shape(): void
    {
        mkdir($this->project->root . '/.foundry/state', 0777, true);
        $pdo = new \PDO('sqlite:' . $this->project->root . '/.foundry/state/foundry.sqlite');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE unrelated_table (id INTEGER PRIMARY KEY)');

        $metadata = $this->store()->inspectMetadata();

        $this->assertSame('schema_invalid', $metadata['status']);
        $this->assertTrue($metadata['exists']);
        $this->assertNull($metadata['schema_version']);
        $this->assertSame('STATE_STORE_SCHEMA_INVALID', $metadata['issues'][0]['code']);
    }

    public function test_get_throws_not_found_for_missing_key(): void
    {
        $store = $this->store();
        $store->set('known', 'key', 'value');

        try {
            $store->get('known', 'missing');
            self::fail('Expected missing key failure.');
        } catch (FoundryError $error) {
            $this->assertSame('STATE_STORE_KEY_NOT_FOUND', $error->errorCode);
        }
    }

    public function test_get_throws_schema_invalid_when_required_tables_are_missing(): void
    {
        mkdir($this->project->root . '/.foundry/state', 0777, true);
        $pdo = new \PDO('sqlite:' . $this->project->root . '/.foundry/state/foundry.sqlite');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE unrelated_table (id INTEGER PRIMARY KEY)');

        try {
            $this->store()->get('any', 'key');
            self::fail('Expected schema invalid failure.');
        } catch (FoundryError $error) {
            $this->assertSame('STATE_STORE_SCHEMA_INVALID', $error->errorCode);
        }
    }

    public function test_set_rejects_unsupported_value_type(): void
    {
        $resource = fopen('php://memory', 'rb');
        self::assertIsResource($resource);

        try {
            $this->store()->set('types', 'resource', $resource);
            self::fail('Expected unsupported type failure.');
        } catch (FoundryError $error) {
            $this->assertSame('STATE_STORE_VALUE_TYPE_UNSUPPORTED', $error->errorCode);
        } finally {
            fclose($resource);
        }
    }

    public function test_invalid_json_payload_throws_decode_failure(): void
    {
        $store = $this->store();
        $store->set('types', 'json', ['ok' => true]);

        $pdo = new \PDO('sqlite:' . $this->project->root . '/.foundry/state/foundry.sqlite');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $statement = $pdo->prepare('UPDATE foundry_state_values SET value_type = :type, value = :value WHERE namespace = :namespace AND key = :key');
        $statement->execute([
            ':type' => 'json_array',
            ':value' => '{',
            ':namespace' => 'types',
            ':key' => 'json',
        ]);

        try {
            $store->get('types', 'json');
            self::fail('Expected JSON decode failure.');
        } catch (FoundryError $error) {
            $this->assertSame('STATE_STORE_JSON_DECODE_FAILED', $error->errorCode);
        }
    }

    public function test_pdo_factory_must_return_pdo_instance(): void
    {
        $store = new SqliteStateStore(
            new Paths($this->project->root),
            static fn(string $dsn): mixed => new \stdClass(),
        );

        try {
            $store->ensureInitialized();
            self::fail('Expected invalid connection factory failure.');
        } catch (FoundryError $error) {
            $this->assertSame('STATE_STORE_CONNECTION_INVALID', $error->errorCode);
        }
    }

    public function test_pdo_factory_exceptions_are_wrapped(): void
    {
        $store = new SqliteStateStore(
            new Paths($this->project->root),
            static function (string $dsn): \PDO {
                throw new \RuntimeException('factory failure');
            },
        );

        try {
            $store->ensureInitialized();
            self::fail('Expected wrapped connection failure.');
        } catch (FoundryError $error) {
            $this->assertSame('STATE_STORE_CONNECTION_FAILED', $error->errorCode);
        }
    }

    public function test_schema_version_returns_null_for_non_numeric_meta_value(): void
    {
        $store = $this->store();
        $store->ensureInitialized();

        $pdo = new \PDO('sqlite:' . $this->project->root . '/.foundry/state/foundry.sqlite');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $statement = $pdo->prepare('UPDATE foundry_state_meta SET meta_value = :value WHERE meta_key = :key');
        $statement->execute([':value' => 'not-a-number', ':key' => 'schema_version']);

        $this->assertNull($store->schemaVersion());
    }

    private function store(): SqliteStateStore
    {
        return new SqliteStateStore(new Paths($this->project->root));
    }
}
