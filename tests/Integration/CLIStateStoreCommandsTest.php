<?php

declare(strict_types=1);

namespace Foundry\Tests\Integration;

use Foundry\CLI\Application;
use Foundry\State\SqliteStateStore;
use Foundry\Support\Paths;
use Foundry\Tests\Fixtures\TempProject;
use PHPUnit\Framework\TestCase;

final class CLIStateStoreCommandsTest extends TestCase
{
    private TempProject $project;
    private string $cwd;

    protected function setUp(): void
    {
        $this->project = new TempProject();
        $this->cwd = getcwd() ?: '.';
        chdir($this->project->root);
    }

    protected function tearDown(): void
    {
        chdir($this->cwd);
        $this->project->cleanup();
    }

    public function test_verify_state_store_passes_with_deterministic_shape_and_ordered_checks(): void
    {
        $app = new Application();

        $result = $this->runCommand($app, ['foundry', 'verify', 'state-store', '--json']);

        $this->assertSame(0, $result['status']);
        $this->assertSame('pass', $result['payload']['status']);
        $this->assertSame('sqlite', $result['payload']['store']);
        $this->assertSame('.foundry/state/foundry.sqlite', $result['payload']['path']);
        $this->assertSame(1, $result['payload']['schema_version']);
        $this->assertSame([
            'path_resolved',
            'directory_ready',
            'sqlite_available',
            'schema_ready',
            'round_trip',
        ], array_map(static fn(array $row): string => (string) ($row['name'] ?? ''), $result['payload']['checks']));
        $this->assertFileExists($this->project->root . '/.foundry/state/foundry.sqlite');

        $store = new SqliteStateStore(new Paths($this->project->root));
        $this->assertSame([], $store->listKeys('__foundry_verify__'));
    }

    public function test_verify_state_store_returns_deterministic_failure_when_directory_is_blocked(): void
    {
        $app = new Application();
        mkdir($this->project->root . '/.foundry', 0777, true);
        file_put_contents($this->project->root . '/.foundry/state', 'blocked');

        $result = $this->runCommand($app, ['foundry', 'verify', 'state-store', '--json']);

        $this->assertSame(1, $result['status']);
        $this->assertSame('fail', $result['payload']['status']);
        $this->assertSame('sqlite', $result['payload']['store']);
        $this->assertSame('.foundry/state/foundry.sqlite', $result['payload']['path']);
        $this->assertNull($result['payload']['schema_version']);
        $this->assertSame('path_resolved', $result['payload']['checks'][0]['name']);
        $this->assertSame('pass', $result['payload']['checks'][0]['status']);
        $this->assertSame('directory_ready', $result['payload']['checks'][1]['name']);
        $this->assertSame('fail', $result['payload']['checks'][1]['status']);
    }

    public function test_verify_state_store_fails_when_database_path_is_a_directory(): void
    {
        $app = new Application();
        mkdir($this->project->root . '/.foundry/state/foundry.sqlite', 0777, true);

        $result = $this->runCommand($app, ['foundry', 'verify', 'state-store', '--json']);

        $this->assertSame(1, $result['status']);
        $this->assertSame('fail', $result['payload']['status']);
        $this->assertSame('schema_ready', $result['payload']['checks'][3]['name']);
        $this->assertSame('fail', $result['payload']['checks'][3]['status']);
        $this->assertNull($result['payload']['schema_version']);
    }

    public function test_verify_state_store_fails_when_round_trip_cannot_write_to_invalid_schema_table(): void
    {
        $app = new Application();
        mkdir($this->project->root . '/.foundry/state', 0777, true);

        $pdo = new \PDO('sqlite:' . $this->project->root . '/.foundry/state/foundry.sqlite');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE foundry_state_meta (meta_key TEXT PRIMARY KEY, meta_value TEXT NOT NULL)');
        $pdo->exec('CREATE TABLE foundry_state_values (namespace TEXT NOT NULL, key TEXT NOT NULL, value TEXT NOT NULL, PRIMARY KEY(namespace, key))');

        $result = $this->runCommand($app, ['foundry', 'verify', 'state-store', '--json']);

        $this->assertSame(1, $result['status']);
        $this->assertSame('fail', $result['payload']['status']);
        $this->assertSame('round_trip', $result['payload']['checks'][4]['name']);
        $this->assertSame('fail', $result['payload']['checks'][4]['status']);
        $this->assertSame(1, $result['payload']['schema_version']);
    }

    public function test_inspect_state_store_missing_database_does_not_create_files(): void
    {
        $app = new Application();

        $result = $this->runCommand($app, ['foundry', 'inspect', 'state-store', '--json']);

        $this->assertSame(0, $result['status']);
        $this->assertSame('sqlite', $result['payload']['store']);
        $this->assertSame('.foundry/state/foundry.sqlite', $result['payload']['path']);
        $this->assertFalse($result['payload']['exists']);
        $this->assertNull($result['payload']['schema_version']);
        $this->assertSame([], $result['payload']['namespaces']);
        $this->assertFileDoesNotExist($this->project->root . '/.foundry/state/foundry.sqlite');
    }

    public function test_inspect_state_store_reports_sorted_namespace_counts(): void
    {
        $app = new Application();
        $store = new SqliteStateStore(new Paths($this->project->root));
        $store->set('zeta', 'a', 1);
        $store->set('alpha', 'a', 1);
        $store->set('alpha', 'b', 2);

        $result = $this->runCommand($app, ['foundry', 'inspect', 'state-store', '--json']);

        $this->assertSame(0, $result['status']);
        $this->assertTrue($result['payload']['exists']);
        $this->assertSame(1, $result['payload']['schema_version']);
        $this->assertSame([
            ['namespace' => 'alpha', 'keys' => 2],
            ['namespace' => 'zeta', 'keys' => 1],
        ], $result['payload']['namespaces']);
    }

    public function test_inspect_state_store_fails_when_schema_is_invalid(): void
    {
        $app = new Application();
        mkdir($this->project->root . '/.foundry/state', 0777, true);

        $pdo = new \PDO('sqlite:' . $this->project->root . '/.foundry/state/foundry.sqlite');
        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->exec('CREATE TABLE IF NOT EXISTS unrelated_table (id INTEGER PRIMARY KEY)');

        $result = $this->runCommand($app, ['foundry', 'inspect', 'state-store', '--json']);

        $this->assertSame(1, $result['status']);
        $this->assertSame('schema_invalid', $result['payload']['status']);
        $this->assertSame('STATE_STORE_SCHEMA_INVALID', $result['payload']['issues'][0]['code']);
    }

    /**
     * @param array<int,string> $argv
     * @return array{status:int,payload:array<string,mixed>}
     */
    private function runCommand(Application $app, array $argv): array
    {
        ob_start();
        $status = $app->run($argv);
        $output = ob_get_clean() ?: '';

        /** @var array<string,mixed> $payload */
        $payload = json_decode($output, true, 512, JSON_THROW_ON_ERROR);

        return ['status' => $status, 'payload' => $payload];
    }
}
