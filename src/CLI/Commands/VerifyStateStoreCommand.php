<?php

declare(strict_types=1);

namespace Foundry\CLI\Commands;

use Foundry\CLI\Command;
use Foundry\CLI\CommandContext;
use Foundry\Support\FoundryError;

final class VerifyStateStoreCommand extends Command
{
    #[\Override]
    public function supportedSignatures(): array
    {
        return ['verify state-store'];
    }

    #[\Override]
    public function matches(array $args): bool
    {
        return ($args[0] ?? null) === 'verify' && ($args[1] ?? null) === 'state-store';
    }

    #[\Override]
    public function run(array $args, CommandContext $context): array
    {
        $store = $context->sqliteStateStore();
        $path = $store->relativePath();
        $checks = [];
        $schemaVersion = null;

        $checks[] = ['name' => 'path_resolved', 'status' => 'pass'];

        try {
            $store->ensureDirectoryReady();
            $checks[] = ['name' => 'directory_ready', 'status' => 'pass'];
        } catch (\Throwable $error) {
            $checks[] = [
                'name' => 'directory_ready',
                'status' => 'fail',
                'message' => $this->messageFor($error),
            ];

            return $this->failurePayload($path, $schemaVersion, $checks);
        }

        if (!$store->sqliteAvailable()) {
            $checks[] = [
                'name' => 'sqlite_available',
                'status' => 'fail',
                'message' => 'PDO SQLite driver is unavailable.',
            ];

            return $this->failurePayload($path, $schemaVersion, $checks);
        }

        $checks[] = ['name' => 'sqlite_available', 'status' => 'pass'];

        try {
            $schemaVersion = $store->ensureInitialized();
            $checks[] = ['name' => 'schema_ready', 'status' => 'pass'];
        } catch (\Throwable $error) {
            $checks[] = [
                'name' => 'schema_ready',
                'status' => 'fail',
                'message' => $this->messageFor($error),
            ];

            return $this->failurePayload($path, null, $checks);
        }

        try {
            $store->verifyRoundTrip();
            $checks[] = ['name' => 'round_trip', 'status' => 'pass'];
        } catch (\Throwable $error) {
            $checks[] = [
                'name' => 'round_trip',
                'status' => 'fail',
                'message' => $this->messageFor($error),
            ];

            return $this->failurePayload($path, $schemaVersion, $checks);
        }

        return [
            'status' => 0,
            'message' => 'State-store verification passed.',
            'payload' => [
                'status' => 'pass',
                'store' => 'sqlite',
                'path' => $path,
                'schema_version' => $schemaVersion,
                'checks' => $checks,
            ],
        ];
    }

    /**
     * @param list<array{name:string,status:string,message?:string}> $checks
     * @return array{status:int,message:string,payload:array{status:string,store:string,path:string,schema_version:int|null,checks:list<array{name:string,status:string,message?:string}>}}
     */
    private function failurePayload(string $path, ?int $schemaVersion, array $checks): array
    {
        return [
            'status' => 1,
            'message' => 'State-store verification failed.',
            'payload' => [
                'status' => 'fail',
                'store' => 'sqlite',
                'path' => $path,
                'schema_version' => $schemaVersion,
                'checks' => $checks,
            ],
        ];
    }

    private function messageFor(\Throwable $error): string
    {
        if ($error instanceof FoundryError && $error->getMessage() !== '') {
            return $error->getMessage();
        }

        return $error->getMessage() !== '' ? $error->getMessage() : 'State-store verification failed.';
    }
}
