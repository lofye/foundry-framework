<?php

declare(strict_types=1);

namespace Foundry\Marketplace;

use Foundry\Support\Clock;
use Foundry\Support\FoundryError;
use Foundry\Support\Json;
use Foundry\Support\Paths;

final class MarketplaceIdentityStore
{
    public function __construct(
        private readonly Paths $paths,
        private readonly ?Clock $clock = null,
    ) {}

    public function identityPathRelative(): string
    {
        return '.foundry/marketplace/identity.json';
    }

    public function identityPathAbsolute(): string
    {
        return $this->paths->join($this->identityPathRelative());
    }

    public function exists(): bool
    {
        return is_file($this->identityPathAbsolute());
    }

    /**
     * @param array<string,mixed> $credentials
     */
    public function write(array $credentials): void
    {
        $normalized = $this->normalizeCredentialRecord($credentials);
        $path = $this->identityPathAbsolute();
        $directory = dirname($path);

        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new FoundryError(
                'MARKETPLACE_AUTH_STORAGE_UNWRITABLE',
                'filesystem',
                ['path' => $this->identityPathRelative()],
                'Marketplace auth storage directory is not writable.',
            );
        }

        $tmp = $path . '.tmp';
        $encoded = Json::encode($normalized, true) . PHP_EOL;
        if (@file_put_contents($tmp, $encoded) === false) {
            throw new FoundryError(
                'MARKETPLACE_AUTH_STORAGE_UNWRITABLE',
                'filesystem',
                ['path' => $this->identityPathRelative()],
                'Marketplace auth credentials could not be written.',
            );
        }

        if (!@rename($tmp, $path)) {
            @unlink($tmp);
            throw new FoundryError(
                'MARKETPLACE_AUTH_STORAGE_UNWRITABLE',
                'filesystem',
                ['path' => $this->identityPathRelative()],
                'Marketplace auth credentials could not be atomically persisted.',
            );
        }
    }

    /**
     * Backward-compatible alias for older call sites.
     *
     * @param array<string,mixed> $identity
     */
    public function save(array $identity): void
    {
        $this->write($identity);
    }

    /**
     * @return array<string,mixed>|null
     */
    public function read(): ?array
    {
        $path = $this->identityPathAbsolute();
        if (!is_file($path)) {
            return null;
        }

        $raw = @file_get_contents($path);
        if ($raw === false) {
            throw new FoundryError(
                'MARKETPLACE_AUTH_STORAGE_UNREADABLE',
                'filesystem',
                ['path' => $this->identityPathRelative()],
                'Marketplace auth storage file could not be read.',
            );
        }

        try {
            $payload = Json::decodeAssoc($raw);
        } catch (FoundryError $error) {
            throw new FoundryError(
                'MARKETPLACE_AUTH_STATE_INVALID',
                'validation',
                ['path' => $this->identityPathRelative()],
                'Marketplace auth state is invalid.',
                previous: $error,
            );
        }

        return $this->normalizeCredentialRecord($payload);
    }

    public function clear(): bool
    {
        $path = $this->identityPathAbsolute();
        if (!is_file($path)) {
            return false;
        }

        if (!@unlink($path)) {
            throw new FoundryError(
                'MARKETPLACE_AUTH_STORAGE_UNWRITABLE',
                'filesystem',
                ['path' => $this->identityPathRelative()],
                'Marketplace auth credentials could not be cleared.',
            );
        }

        return true;
    }

    /**
     * @return array<string,mixed>
     */
    public function inspect(): array
    {
        $state = $this->readState();
        $credential = $state['credential'];

        return [
            'configured' => $state['configured'],
            'authenticated' => $state['authenticated'],
            'status' => $state['status'],
            'code' => $state['code'],
            'user' => $state['safe_user'],
            'token' => [
                'present' => $credential !== null && trim((string) ($credential['access_token'] ?? '')) !== '',
                'type' => $credential === null ? null : (string) ($credential['token_type'] ?? 'bearer'),
                'expires_at' => $credential['expires_at'] ?? null,
            ],
            'path' => $this->identityPathRelative(),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function verify(): array
    {
        $state = $this->readState();

        if ((string) $state['status'] === 'invalid') {
            return [
                'status' => 'fail',
                'configured' => true,
                'authenticated' => false,
                'code' => $state['code'] ?? 'MARKETPLACE_AUTH_STATE_INVALID',
                'errors' => [[
                    'code' => (string) ($state['code'] ?? 'MARKETPLACE_AUTH_STATE_INVALID'),
                    'message' => (string) ($state['message'] ?? 'Marketplace auth state is invalid.'),
                    'details' => ['path' => $this->identityPathRelative()],
                ]],
            ];
        }

        if ((string) $state['status'] === 'expired') {
            return [
                'status' => 'fail',
                'configured' => true,
                'authenticated' => false,
                'code' => 'MARKETPLACE_AUTH_EXPIRED',
                'errors' => [[
                    'code' => 'MARKETPLACE_AUTH_EXPIRED',
                    'message' => 'Marketplace credentials are expired.',
                    'details' => ['path' => $this->identityPathRelative()],
                ]],
            ];
        }

        if (!$state['configured']) {
            return [
                'status' => 'pass',
                'configured' => false,
                'authenticated' => false,
                'code' => 'MARKETPLACE_AUTH_NOT_AUTHENTICATED',
                'errors' => [],
            ];
        }

        return [
            'status' => 'pass',
            'configured' => true,
            'authenticated' => true,
            'code' => null,
            'errors' => [],
        ];
    }

    /**
     * @return array{
     *   configured:bool,
     *   authenticated:bool,
     *   status:string,
     *   code:string|null,
     *   message:string|null,
     *   safe_user:array<string,mixed>|null,
     *   credential:array<string,mixed>|null
     * }
     */
    public function readState(): array
    {
        try {
            $credential = $this->read();
        } catch (FoundryError $error) {
            return [
                'configured' => $this->exists(),
                'authenticated' => false,
                'status' => 'invalid',
                'code' => $error->errorCode,
                'message' => $error->getMessage(),
                'safe_user' => null,
                'credential' => null,
            ];
        }

        if ($credential === null) {
            return [
                'configured' => false,
                'authenticated' => false,
                'status' => 'unauthenticated',
                'code' => null,
                'message' => null,
                'safe_user' => null,
                'credential' => null,
            ];
        }

        $safeUser = $this->safeUser($credential);
        if ($this->isExpired((string) ($credential['expires_at'] ?? ''))) {
            return [
                'configured' => true,
                'authenticated' => false,
                'status' => 'expired',
                'code' => 'MARKETPLACE_AUTH_EXPIRED',
                'message' => 'Marketplace credentials are expired.',
                'safe_user' => $safeUser,
                'credential' => $credential,
            ];
        }

        return [
            'configured' => true,
            'authenticated' => true,
            'status' => 'authenticated',
            'code' => null,
            'message' => null,
            'safe_user' => $safeUser,
            'credential' => $credential,
        ];
    }

    /**
     * @param array<string,mixed> $credentials
     * @return array{
     *   token_type:string,
     *   access_token:string,
     *   expires_at:string|null,
     *   user:array{id:string,email:string,name:string|null,created_at:string|null}
     * }
     */
    private function normalizeCredentialRecord(array $credentials): array
    {
        $tokenType = strtolower(trim((string) ($credentials['token_type'] ?? 'bearer')));
        $accessToken = trim((string) ($credentials['access_token'] ?? ''));
        $expiresAt = $credentials['expires_at'] ?? null;
        $user = is_array($credentials['user'] ?? null) ? $credentials['user'] : null;

        if ($tokenType === '') {
            $tokenType = 'bearer';
        }

        if ($accessToken === '') {
            throw new FoundryError(
                'MARKETPLACE_AUTH_STATE_INVALID',
                'validation',
                ['path' => $this->identityPathRelative()],
                'Marketplace auth state is invalid.',
            );
        }

        if ($user === null) {
            throw new FoundryError(
                'MARKETPLACE_AUTH_STATE_INVALID',
                'validation',
                ['path' => $this->identityPathRelative()],
                'Marketplace auth state is invalid.',
            );
        }

        $id = trim((string) ($user['id'] ?? ''));
        $email = trim((string) ($user['email'] ?? ''));
        if ($id === '' || $email === '') {
            throw new FoundryError(
                'MARKETPLACE_AUTH_STATE_INVALID',
                'validation',
                ['path' => $this->identityPathRelative()],
                'Marketplace auth state is invalid.',
            );
        }

        $name = $user['name'] ?? null;
        if (!is_string($name) && $name !== null) {
            throw new FoundryError(
                'MARKETPLACE_AUTH_STATE_INVALID',
                'validation',
                ['path' => $this->identityPathRelative()],
                'Marketplace auth state is invalid.',
            );
        }

        if ($expiresAt !== null) {
            $expiresAt = trim((string) $expiresAt);
            if ($expiresAt === '' || !$this->isIso8601($expiresAt)) {
                throw new FoundryError(
                    'MARKETPLACE_AUTH_STATE_INVALID',
                    'validation',
                    ['path' => $this->identityPathRelative()],
                    'Marketplace auth state is invalid.',
                );
            }
        }

        $createdAt = $user['created_at'] ?? null;
        if ($createdAt !== null) {
            $createdAt = trim((string) $createdAt);
            if ($createdAt === '' || !$this->isIso8601($createdAt)) {
                throw new FoundryError(
                    'MARKETPLACE_AUTH_STATE_INVALID',
                    'validation',
                    ['path' => $this->identityPathRelative()],
                    'Marketplace auth state is invalid.',
                );
            }
        }

        return [
            'token_type' => $tokenType,
            'access_token' => $accessToken,
            'expires_at' => $expiresAt,
            'user' => [
                'id' => $id,
                'email' => $email,
                'name' => $name === null ? null : trim($name),
                'created_at' => $createdAt,
            ],
        ];
    }

    /**
     * @param array<string,mixed> $credential
     * @return array{id:string,email:string,name:string|null,created_at:string|null}
     */
    private function safeUser(array $credential): array
    {
        /** @var array<string,mixed> $user */
        $user = (array) ($credential['user'] ?? []);

        return [
            'id' => (string) ($user['id'] ?? ''),
            'email' => (string) ($user['email'] ?? ''),
            'name' => isset($user['name']) ? ($user['name'] === null ? null : (string) $user['name']) : null,
            'created_at' => isset($user['created_at']) ? ($user['created_at'] === null ? null : (string) $user['created_at']) : null,
        ];
    }

    private function isExpired(string $expiresAt): bool
    {
        if ($expiresAt === '') {
            return false;
        }

        $expires = new \DateTimeImmutable($expiresAt);

        return $expires <= $this->clock()->now();
    }

    private function isIso8601(string $value): bool
    {
        try {
            $dt = new \DateTimeImmutable($value);
        } catch (\Throwable) {
            return false;
        }

        return $dt->format(\DateTimeInterface::ATOM) === $value || $dt->format('Y-m-d\TH:i:s\Z') === $value;
    }

    private function clock(): Clock
    {
        return $this->clock ?? new Clock();
    }
}
