<?php

declare(strict_types=1);

namespace Foundry\State;

use Foundry\Support\FoundryError;
use Foundry\Support\Paths;

final class SqliteStateStore
{
    private const string STORE = 'sqlite';
    private const int SCHEMA_VERSION = 1;
    private const string DATABASE_PATH = '.foundry/state/foundry.sqlite';
    private readonly Paths $paths;
    private readonly ?\Closure $pdoFactory;

    /**
     * @param callable(string):\PDO|null $pdoFactory
     */
    public function __construct(
        Paths $paths,
        ?callable $pdoFactory = null,
    ) {
        $this->paths = $paths;
        $this->pdoFactory = $pdoFactory === null ? null : \Closure::fromCallable($pdoFactory);
    }

    public function store(): string
    {
        return self::STORE;
    }

    public function relativePath(): string
    {
        return self::DATABASE_PATH;
    }

    public function absolutePath(): string
    {
        return $this->paths->join(self::DATABASE_PATH);
    }

    public function exists(): bool
    {
        return is_file($this->absolutePath());
    }

    public function sqliteAvailable(): bool
    {
        return in_array('sqlite', \PDO::getAvailableDrivers(), true);
    }

    public function ensureDirectoryReady(): void
    {
        $directory = dirname($this->absolutePath());
        if (is_dir($directory)) {
            return;
        }

        if (file_exists($directory)) {
            throw new FoundryError(
                'STATE_STORE_DIRECTORY_BLOCKED',
                'filesystem',
                ['path' => '.foundry/state'],
                'State-store directory path exists but is not a directory.',
            );
        }

        if (!mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new FoundryError(
                'STATE_STORE_DIRECTORY_CREATE_FAILED',
                'filesystem',
                ['path' => '.foundry/state'],
                'Unable to create state-store directory.',
            );
        }
    }

    public function ensureInitialized(): int
    {
        $pdo = $this->connect(true);

        $pdo->exec('CREATE TABLE IF NOT EXISTS foundry_state_meta (meta_key TEXT PRIMARY KEY, meta_value TEXT NOT NULL)');
        $pdo->exec('CREATE TABLE IF NOT EXISTS foundry_state_values (namespace TEXT NOT NULL, key TEXT NOT NULL, value TEXT NOT NULL, value_type TEXT NOT NULL, PRIMARY KEY(namespace, key))');

        $statement = $pdo->prepare('INSERT OR IGNORE INTO foundry_state_meta(meta_key, meta_value) VALUES (:key, :value)');
        $statement->execute([':key' => 'schema_version', ':value' => (string) self::SCHEMA_VERSION]);

        return self::SCHEMA_VERSION;
    }

    public function schemaVersion(): ?int
    {
        if (!$this->exists()) {
            return null;
        }

        $pdo = $this->connect(false);
        if (!$this->hasRequiredTables($pdo)) {
            return null;
        }

        $statement = $pdo->prepare('SELECT meta_value FROM foundry_state_meta WHERE meta_key = :key LIMIT 1');
        $statement->execute([':key' => 'schema_version']);
        $value = $statement->fetchColumn();
        if ($value === false || !is_numeric((string) $value)) {
            return null;
        }

        return (int) $value;
    }

    public function verifyRoundTrip(): void
    {
        $this->ensureInitialized();
        $pdo = $this->connect(false);

        $namespace = '__foundry_verify__';
        $key = 'round_trip';

        $pdo->beginTransaction();

        try {
            $this->setInternal($pdo, $namespace, $key, ['ok' => true, 'store' => self::STORE]);
            $value = $this->getInternal($pdo, $namespace, $key);

            if (!is_array($value) || ($value['ok'] ?? null) !== true || ($value['store'] ?? null) !== self::STORE) {
                throw new FoundryError(
                    'STATE_STORE_ROUND_TRIP_FAILED',
                    'runtime',
                    ['path' => self::DATABASE_PATH],
                    'State-store round-trip verification failed.',
                );
            }

            $pdo->rollBack();
        } catch (\Throwable $error) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $error;
        }
    }

    public function set(string $namespace, string $key, mixed $value): void
    {
        $this->ensureInitialized();
        $pdo = $this->connect(false);
        $this->setInternal($pdo, $namespace, $key, $value);
    }

    public function get(string $namespace, string $key): mixed
    {
        $pdo = $this->connect(false);

        return $this->getInternal($pdo, $namespace, $key);
    }

    public function has(string $namespace, string $key): bool
    {
        $this->assertNamespaceAndKey($namespace, $key);
        if (!$this->exists()) {
            return false;
        }

        $pdo = $this->connect(false);
        if (!$this->hasRequiredTables($pdo)) {
            return false;
        }

        $statement = $pdo->prepare('SELECT 1 FROM foundry_state_values WHERE namespace = :namespace AND key = :key LIMIT 1');
        $statement->execute([':namespace' => $namespace, ':key' => $key]);

        return $statement->fetchColumn() !== false;
    }

    public function delete(string $namespace, string $key): bool
    {
        $this->assertNamespaceAndKey($namespace, $key);
        if (!$this->exists()) {
            return false;
        }

        $pdo = $this->connect(false);
        if (!$this->hasRequiredTables($pdo)) {
            return false;
        }

        $statement = $pdo->prepare('DELETE FROM foundry_state_values WHERE namespace = :namespace AND key = :key');
        $statement->execute([':namespace' => $namespace, ':key' => $key]);

        return $statement->rowCount() > 0;
    }

    /**
     * @return list<array{namespace:string,key:string,value_type:string}>
     */
    public function listKeys(?string $namespace = null): array
    {
        if (!$this->exists()) {
            return [];
        }

        $pdo = $this->connect(false);
        if (!$this->hasRequiredTables($pdo)) {
            return [];
        }

        if ($namespace === null) {
            $statement = $pdo->query('SELECT namespace, key, value_type FROM foundry_state_values ORDER BY namespace ASC, key ASC');
        } else {
            $this->assertNamespace($namespace);
            $statement = $pdo->prepare('SELECT namespace, key, value_type FROM foundry_state_values WHERE namespace = :namespace ORDER BY namespace ASC, key ASC');
            $statement->execute([':namespace' => $namespace]);
        }

        $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);

        return array_values(array_map(
            static fn(array $row): array => [
                'namespace' => (string) ($row['namespace'] ?? ''),
                'key' => (string) ($row['key'] ?? ''),
                'value_type' => (string) ($row['value_type'] ?? ''),
            ],
            is_array($rows) ? $rows : [],
        ));
    }

    /**
     * @return list<array{namespace:string,keys:int}>
     */
    public function listNamespaces(): array
    {
        if (!$this->exists()) {
            return [];
        }

        $pdo = $this->connect(false);
        if (!$this->hasRequiredTables($pdo)) {
            return [];
        }

        $statement = $pdo->query('SELECT namespace, COUNT(*) AS keys FROM foundry_state_values GROUP BY namespace ORDER BY namespace ASC');
        $rows = $statement->fetchAll(\PDO::FETCH_ASSOC);

        return array_values(array_map(
            static fn(array $row): array => [
                'namespace' => (string) ($row['namespace'] ?? ''),
                'keys' => (int) ($row['keys'] ?? 0),
            ],
            is_array($rows) ? $rows : [],
        ));
    }

    /**
     * @return array{store:string,path:string,exists:bool,schema_version:int|null,namespaces:list<array{namespace:string,keys:int}>,status:string,issues:list<array{code:string,message:string}>}
     */
    public function inspectMetadata(): array
    {
        if (!$this->exists()) {
            return [
                'store' => self::STORE,
                'path' => self::DATABASE_PATH,
                'exists' => false,
                'schema_version' => null,
                'namespaces' => [],
                'status' => 'ok',
                'issues' => [],
            ];
        }

        $pdo = $this->connect(false);
        if (!$this->hasRequiredTables($pdo)) {
            return [
                'store' => self::STORE,
                'path' => self::DATABASE_PATH,
                'exists' => true,
                'schema_version' => null,
                'namespaces' => [],
                'status' => 'schema_invalid',
                'issues' => [[
                    'code' => 'STATE_STORE_SCHEMA_INVALID',
                    'message' => 'State-store schema tables are missing or invalid.',
                ]],
            ];
        }

        return [
            'store' => self::STORE,
            'path' => self::DATABASE_PATH,
            'exists' => true,
            'schema_version' => $this->schemaVersion(),
            'namespaces' => $this->listNamespaces(),
            'status' => 'ok',
            'issues' => [],
        ];
    }

    private function connect(bool $createDirectory): \PDO
    {
        if (!$this->sqliteAvailable()) {
            throw new FoundryError(
                'STATE_STORE_SQLITE_UNAVAILABLE',
                'runtime',
                ['driver' => 'sqlite'],
                'PDO SQLite driver is unavailable.',
            );
        }

        if ($createDirectory) {
            $this->ensureDirectoryReady();
        }

        $dsn = 'sqlite:' . $this->absolutePath();

        try {
            $pdo = is_callable($this->pdoFactory)
                ? ($this->pdoFactory)($dsn)
                : new \PDO($dsn);
        } catch (\Throwable $error) {
            throw new FoundryError(
                'STATE_STORE_CONNECTION_FAILED',
                'runtime',
                ['path' => self::DATABASE_PATH],
                'Unable to open the state-store SQLite database.',
                0,
                $error,
            );
        }

        if (!$pdo instanceof \PDO) {
            throw new FoundryError(
                'STATE_STORE_CONNECTION_INVALID',
                'runtime',
                ['path' => self::DATABASE_PATH],
                'State-store PDO factory did not return a PDO instance.',
            );
        }

        $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);
        $pdo->exec('PRAGMA foreign_keys = ON');

        return $pdo;
    }

    private function hasRequiredTables(\PDO $pdo): bool
    {
        $required = ['foundry_state_meta', 'foundry_state_values'];

        foreach ($required as $table) {
            $statement = $pdo->prepare("SELECT 1 FROM sqlite_master WHERE type = 'table' AND name = :name LIMIT 1");
            $statement->execute([':name' => $table]);

            if ($statement->fetchColumn() === false) {
                return false;
            }
        }

        return true;
    }

    private function setInternal(\PDO $pdo, string $namespace, string $key, mixed $value): void
    {
        $this->assertNamespaceAndKey($namespace, $key);
        [$type, $encoded] = $this->encodeValue($value);

        $statement = $pdo->prepare('INSERT INTO foundry_state_values(namespace, key, value, value_type) VALUES (:namespace, :key, :value, :type) ON CONFLICT(namespace, key) DO UPDATE SET value = excluded.value, value_type = excluded.value_type');
        $statement->execute([
            ':namespace' => $namespace,
            ':key' => $key,
            ':value' => $encoded,
            ':type' => $type,
        ]);
    }

    private function getInternal(\PDO $pdo, string $namespace, string $key): mixed
    {
        $this->assertNamespaceAndKey($namespace, $key);
        if (!$this->hasRequiredTables($pdo)) {
            throw new FoundryError(
                'STATE_STORE_SCHEMA_INVALID',
                'validation',
                ['path' => self::DATABASE_PATH],
                'State-store schema tables are missing or invalid.',
            );
        }

        $statement = $pdo->prepare('SELECT value, value_type FROM foundry_state_values WHERE namespace = :namespace AND key = :key LIMIT 1');
        $statement->execute([':namespace' => $namespace, ':key' => $key]);
        $row = $statement->fetch(\PDO::FETCH_ASSOC);

        if (!is_array($row)) {
            throw new FoundryError(
                'STATE_STORE_KEY_NOT_FOUND',
                'not_found',
                ['namespace' => $namespace, 'key' => $key],
                'State-store key not found.',
            );
        }

        return $this->decodeValue((string) ($row['value_type'] ?? ''), (string) ($row['value'] ?? ''));
    }

    private function assertNamespaceAndKey(string $namespace, string $key): void
    {
        $this->assertNamespace($namespace);
        $this->assertKey($key);
    }

    private function assertNamespace(string $namespace): void
    {
        if (trim($namespace) === '') {
            throw new FoundryError(
                'STATE_STORE_NAMESPACE_INVALID',
                'validation',
                ['namespace' => $namespace],
                'State-store namespace must be a non-empty string.',
            );
        }
    }

    private function assertKey(string $key): void
    {
        if (trim($key) === '') {
            throw new FoundryError(
                'STATE_STORE_KEY_INVALID',
                'validation',
                ['key' => $key],
                'State-store key must be a non-empty string.',
            );
        }
    }

    /**
     * @return array{0:string,1:string}
     */
    private function encodeValue(mixed $value): array
    {
        if (is_string($value)) {
            return ['string', $value];
        }

        if (is_int($value)) {
            return ['int', (string) $value];
        }

        if (is_float($value)) {
            return ['float', json_encode($value, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION)];
        }

        if (is_bool($value)) {
            return ['bool', $value ? '1' : '0'];
        }

        if ($value === null) {
            return ['null', ''];
        }

        if (is_array($value)) {
            return ['json_array', json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION)];
        }

        if (is_object($value)) {
            return ['json_object', json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION)];
        }

        throw new FoundryError(
            'STATE_STORE_VALUE_TYPE_UNSUPPORTED',
            'validation',
            ['type' => get_debug_type($value)],
            'State-store value type is not supported.',
        );
    }

    private function decodeValue(string $valueType, string $encoded): mixed
    {
        return match ($valueType) {
            'string' => $encoded,
            'int' => (int) $encoded,
            'float' => (float) $encoded,
            'bool' => $encoded === '1',
            'null' => null,
            'json_array' => $this->decodeJson($encoded, true),
            'json_object' => $this->decodeJson($encoded, false),
            default => throw new FoundryError(
                'STATE_STORE_VALUE_TYPE_INVALID',
                'validation',
                ['value_type' => $valueType],
                'State-store value type is invalid.',
            ),
        };
    }

    private function decodeJson(string $json, bool $assoc): mixed
    {
        try {
            return json_decode($json, $assoc, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $error) {
            throw new FoundryError(
                'STATE_STORE_JSON_DECODE_FAILED',
                'validation',
                [],
                'State-store JSON payload is invalid.',
                0,
                $error,
            );
        }
    }
}
