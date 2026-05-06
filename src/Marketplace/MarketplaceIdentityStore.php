<?php

declare(strict_types=1);

namespace Foundry\Marketplace;

use Foundry\Support\FoundryError;
use Foundry\Support\Json;
use Foundry\Support\Paths;

final class MarketplaceIdentityStore
{
    public function __construct(private readonly Paths $paths) {}

    public function identityPathRelative(): string
    {
        return '.foundry/marketplace/identity.json';
    }

    public function identityPathAbsolute(): string
    {
        return $this->paths->join($this->identityPathRelative());
    }

    /**
     * @param array{user_id:string,access_token:string,token_hint:string} $identity
     */
    public function save(array $identity): void
    {
        $path = $this->identityPathAbsolute();
        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($path, Json::encode($identity, true) . PHP_EOL);
    }

    /**
     * @return array{user_id:string,access_token:string,token_hint:string}|null
     */
    public function read(): ?array
    {
        $path = $this->identityPathAbsolute();
        if (!is_file($path)) {
            return null;
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new FoundryError(
                'MARKETPLACE_IDENTITY_READ_FAILED',
                'filesystem',
                ['path' => $this->identityPathRelative()],
                'Marketplace identity file could not be read.',
            );
        }

        try {
            $payload = Json::decodeAssoc($raw);
        } catch (FoundryError $error) {
            throw new FoundryError(
                'MARKETPLACE_IDENTITY_INVALID_JSON',
                'validation',
                ['path' => $this->identityPathRelative()],
                'Marketplace identity file must contain valid JSON.',
                previous: $error,
            );
        }

        $userId = trim((string) ($payload['user_id'] ?? ''));
        $token = trim((string) ($payload['access_token'] ?? ''));
        $tokenHint = trim((string) ($payload['token_hint'] ?? ''));

        if ($userId === '' || $token === '' || $tokenHint === '') {
            throw new FoundryError(
                'MARKETPLACE_IDENTITY_INVALID_SHAPE',
                'validation',
                ['path' => $this->identityPathRelative()],
                'Marketplace identity file is missing required fields.',
            );
        }

        return [
            'user_id' => $userId,
            'access_token' => $token,
            'token_hint' => $tokenHint,
        ];
    }

    public function clear(): bool
    {
        $path = $this->identityPathAbsolute();
        if (!is_file($path)) {
            return false;
        }

        return @unlink($path);
    }
}

