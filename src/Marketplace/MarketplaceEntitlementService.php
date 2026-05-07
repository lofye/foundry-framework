<?php

declare(strict_types=1);

namespace Foundry\Marketplace;

use Foundry\Support\FoundryError;

final class MarketplaceEntitlementService
{
    public function __construct(
        private readonly MarketplaceEntitlementCache $cache,
        private readonly MarketplaceIdentityStore $identityStore,
        private readonly ?MarketplaceLicenseClient $licenseClient = null,
    ) {}

    /**
     * @return array{status:string,entitlements:list<array<string,mixed>>}
     */
    public function listEntitlements(): array
    {
        $state = $this->cache->readState();
        if ((string) ($state['status'] ?? '') === 'invalid') {
            throw new FoundryError(
                'MARKETPLACE_ENTITLEMENT_CACHE_INVALID',
                'validation',
                ['path' => $this->cache->cachePathRelative()],
                'Marketplace entitlement cache is invalid.',
            );
        }

        return [
            'status' => 'ok',
            'entitlements' => array_values((array) ($state['entitlements'] ?? [])),
        ];
    }

    /**
     * @return array{status:string,activated:bool,license:array{hint:string},entitlements:list<array<string,mixed>>}
     */
    public function activateLicense(string $licenseKey): array
    {
        $normalized = trim($licenseKey);
        if ($normalized === '' || preg_match('/\s/', $normalized) === 1) {
            throw new FoundryError(
                'MARKETPLACE_LICENSE_INVALID',
                'validation',
                [],
                'Marketplace license key is invalid.',
            );
        }

        if ($this->requiresMarketplaceIdentity($normalized)) {
            $identity = $this->identityStore->readState();
            if (!((bool) ($identity['authenticated'] ?? false))) {
                throw new FoundryError(
                    'MARKETPLACE_AUTH_REQUIRED',
                    'authentication',
                    [],
                    'Marketplace authentication is required.',
                );
            }
        }

        $activation = $this->client()->activate($normalized, [
            'user' => $this->identityStore->inspect()['user'] ?? null,
        ]);

        $entitlements = $activation['entitlements'] ?? null;
        if (!is_array($entitlements)) {
            throw new FoundryError(
                'MARKETPLACE_LICENSE_ACTIVATION_FAILED',
                'runtime',
                [],
                'Marketplace license activation failed.',
            );
        }

        $normalizedEntitlements = $this->cache->persist(array_values($entitlements));

        return [
            'status' => 'ok',
            'activated' => true,
            'license' => [
                'hint' => $this->licenseHint($normalized),
            ],
            'entitlements' => $normalizedEntitlements,
        ];
    }

    private function requiresMarketplaceIdentity(string $licenseKey): bool
    {
        return str_starts_with(strtolower($licenseKey), 'acct_');
    }

    private function licenseHint(string $licenseKey): string
    {
        $trimmed = trim($licenseKey);
        $suffix = strlen($trimmed) <= 4 ? $trimmed : substr($trimmed, -4);

        return '***' . $suffix;
    }

    private function client(): MarketplaceLicenseClient
    {
        return $this->licenseClient ?? new MarketplaceDeterministicLicenseClient();
    }
}
