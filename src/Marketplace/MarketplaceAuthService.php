<?php

declare(strict_types=1);

namespace Foundry\Marketplace;

use Foundry\Support\FoundryError;

final class MarketplaceAuthService
{
    public function __construct(private readonly MarketplaceIdentityStore $store) {}

    /**
     * @return array<string,mixed>
     */
    public function login(string $userId, string $token): array
    {
        $userId = trim($userId);
        $token = trim($token);

        if ($userId === '') {
            throw new FoundryError(
                'MARKETPLACE_LOGIN_USER_REQUIRED',
                'validation',
                [],
                'Marketplace login requires a user id.',
            );
        }

        if (preg_match('/^[A-Za-z0-9._@-]+$/', $userId) !== 1) {
            throw new FoundryError(
                'MARKETPLACE_LOGIN_USER_INVALID',
                'validation',
                ['user_id' => $userId],
                'Marketplace user id is invalid.',
            );
        }

        if ($token === '') {
            throw new FoundryError(
                'MARKETPLACE_LOGIN_TOKEN_REQUIRED',
                'validation',
                [],
                'Marketplace login requires an access token.',
            );
        }

        if (preg_match('/\s/', $token) === 1) {
            throw new FoundryError(
                'MARKETPLACE_LOGIN_TOKEN_INVALID',
                'validation',
                [],
                'Marketplace access token is invalid.',
            );
        }

        $this->store->save([
            'user_id' => $userId,
            'access_token' => $token,
            'token_hint' => $this->tokenHint($token),
        ]);

        return $this->whoami();
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
            'identity' => [
                'user_id' => null,
                'token_hint' => null,
            ],
            'storage' => [
                'path' => $this->store->identityPathRelative(),
                'exists' => false,
            ],
            'api' => [
                'authenticated_request_available' => false,
            ],
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
        $identity = $this->store->read();
        $authenticated = is_array($identity);

        return [
            'status' => 'ok',
            'authenticated' => $authenticated,
            'identity' => [
                'user_id' => $authenticated ? (string) $identity['user_id'] : null,
                'token_hint' => $authenticated ? (string) $identity['token_hint'] : null,
            ],
            'storage' => [
                'path' => $this->store->identityPathRelative(),
                'exists' => $authenticated,
            ],
            'api' => [
                'authenticated_request_available' => $authenticated,
            ],
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
                    'Authorization' => 'Bearer ' . (string) $identity['access_token'],
                    'X-Foundry-Marketplace-User' => (string) $identity['user_id'],
                ],
                'body' => $body,
            ],
        ];
    }

    /**
     * @return array{user_id:string,access_token:string,token_hint:string}
     */
    private function requireAuthenticatedIdentity(): array
    {
        $identity = $this->store->read();
        if (!is_array($identity)) {
            throw new FoundryError(
                'MARKETPLACE_AUTHENTICATION_REQUIRED',
                'authentication',
                [],
                'Marketplace authentication is required.',
            );
        }

        return $identity;
    }

    private function tokenHint(string $token): string
    {
        if (strlen($token) <= 8) {
            return str_repeat('*', strlen($token));
        }

        return substr($token, 0, 4) . '...' . substr($token, -4);
    }
}

