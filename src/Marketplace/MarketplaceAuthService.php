<?php

declare(strict_types=1);

namespace Foundry\Marketplace;

use Foundry\Support\Clock;
use Foundry\Support\FoundryError;

final class MarketplaceAuthService
{
    public function __construct(
        private readonly MarketplaceIdentityStore $store,
        private readonly ?Clock $clock = null,
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function login(
        string $userId,
        string $token,
        ?string $email = null,
        ?string $name = null,
        ?string $expiresAt = null,
    ): array
    {
        $userId = trim($userId);
        $token = trim($token);
        $email = trim((string) $email);
        $name = $name === null ? null : trim($name);
        $expiresAt = $expiresAt === null ? null : trim($expiresAt);

        if ($userId === '') {
            throw new FoundryError(
                'MARKETPLACE_AUTH_FAILED',
                'validation',
                [],
                'Marketplace login requires a user id.',
            );
        }

        if (preg_match('/^[A-Za-z0-9._@-]+$/', $userId) !== 1) {
            throw new FoundryError(
                'MARKETPLACE_AUTH_FAILED',
                'validation',
                ['user_id' => $userId],
                'Marketplace user id is invalid.',
            );
        }

        if ($token === '') {
            throw new FoundryError(
                'MARKETPLACE_AUTH_FAILED',
                'validation',
                [],
                'Marketplace login requires an access token.',
            );
        }

        if (preg_match('/\s/', $token) === 1) {
            throw new FoundryError(
                'MARKETPLACE_AUTH_FAILED',
                'validation',
                [],
                'Marketplace access token is invalid.',
            );
        }

        if ($email === '') {
            $email = str_contains($userId, '@') ? $userId : $userId . '@marketplace.local';
        }

        if (preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $email) !== 1) {
            throw new FoundryError(
                'MARKETPLACE_AUTH_FAILED',
                'validation',
                ['email' => $email],
                'Marketplace email is invalid.',
            );
        }

        if ($expiresAt !== null && $expiresAt !== '' && !$this->isIso8601($expiresAt)) {
            throw new FoundryError(
                'MARKETPLACE_AUTH_FAILED',
                'validation',
                ['expires_at' => $expiresAt],
                'Marketplace credential expiry must be ISO-8601 when provided.',
            );
        }

        $this->store->write([
            'token_type' => 'bearer',
            'access_token' => $token,
            'expires_at' => $expiresAt === '' ? null : $expiresAt,
            'user' => [
                'id' => $userId,
                'email' => $email,
                'name' => $name === '' ? null : $name,
                'created_at' => $this->clock()->nowIso8601(),
            ],
        ]);

        $state = $this->whoami();
        $state['status'] = 'ok';

        return $state;
    }

    /**
     * @return array<string,mixed>
     */
    public function logout(): array
    {
        $hadSession = $this->store->clear();

        return [
            'status' => 'ok',
            'authenticated' => false,
            'user' => null,
            'logout' => [
                'had_session' => $hadSession,
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    public function whoami(): array
    {
        $state = $this->store->readState();
        if ((string) $state['status'] === 'invalid') {
            return [
                'status' => 'error',
                'code' => 'MARKETPLACE_AUTH_STATE_INVALID',
                'authenticated' => false,
                'user' => null,
            ];
        }

        if ((string) $state['status'] === 'unauthenticated') {
            return [
                'authenticated' => false,
                'user' => null,
            ];
        }

        if ((string) $state['status'] === 'expired') {
            return [
                'authenticated' => false,
                'reason' => 'expired',
                'code' => 'MARKETPLACE_AUTH_EXPIRED',
                'user' => $this->minimalExpiredUser($state['safe_user']),
            ];
        }

        return [
            'authenticated' => true,
            'user' => $state['safe_user'],
        ];
    }

    /**
     * @param array<string,mixed> $body
     * @return array<string,mixed>
     */
    public function authenticatedRequest(string $method, string $path, array $body = []): array
    {
        $identity = $this->requireAuthenticatedIdentity();
        $method = strtoupper(trim($method));
        $path = trim($path);

        if (preg_match('/^[A-Z]+$/', $method) !== 1) {
            throw new FoundryError(
                'MARKETPLACE_REQUEST_METHOD_INVALID',
                'validation',
                ['method' => $method],
                'Marketplace request method is invalid.',
            );
        }

        if ($path === '' || !str_starts_with($path, '/')) {
            throw new FoundryError(
                'MARKETPLACE_REQUEST_PATH_INVALID',
                'validation',
                ['path' => $path],
                'Marketplace request path is invalid.',
            );
        }

        return [
            'status' => 'ok',
            'request' => [
                'method' => $method,
                'path' => $path,
                'headers' => [
                    'Authorization' => ucfirst((string) ($identity['token_type'] ?? 'bearer')) . ' ' . (string) $identity['access_token'],
                    'X-Foundry-Marketplace-User' => (string) (($identity['user']['id'] ?? '')),
                ],
                'body' => $body,
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function requireAuthenticatedIdentity(): array
    {
        $state = $this->store->readState();
        if ((string) $state['status'] === 'expired') {
            throw new FoundryError(
                'MARKETPLACE_AUTH_EXPIRED',
                'authentication',
                [],
                'Marketplace credentials are expired.',
            );
        }

        if ((string) $state['status'] === 'invalid') {
            throw new FoundryError(
                'MARKETPLACE_AUTH_STATE_INVALID',
                'authentication',
                [],
                'Marketplace auth state is invalid.',
            );
        }

        if (!$state['authenticated'] || !is_array($state['credential'])) {
            throw new FoundryError(
                'MARKETPLACE_AUTH_NOT_AUTHENTICATED',
                'authentication',
                [],
                'Marketplace authentication is required.',
            );
        }

        return $state['credential'];
    }

    /**
     * @param array<string,mixed>|null $safeUser
     * @return array{id:string,email:string}|null
     */
    private function minimalExpiredUser(?array $safeUser): ?array
    {
        if ($safeUser === null) {
            return null;
        }

        return [
            'id' => (string) ($safeUser['id'] ?? ''),
            'email' => (string) ($safeUser['email'] ?? ''),
        ];
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
