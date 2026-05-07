<?php

declare(strict_types=1);

namespace Foundry\Marketplace;

use Foundry\Packs\HostedPackRegistry;
use Foundry\Packs\PackManifest;
use Foundry\Support\FoundryError;
use Foundry\Support\Paths;

final class MarketplacePurchaseService
{
    public function __construct(
        private readonly Paths $paths,
        private readonly ?HostedPackRegistry $registry = null,
        private readonly ?MarketplaceIdentityStore $identityStore = null,
        private readonly ?MarketplaceEntitlementService $entitlements = null,
        private readonly ?MarketplacePurchaseClient $client = null,
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function purchase(string $pack): array
    {
        $pack = trim($pack);
        if (!PackManifest::isValidName($pack)) {
            throw new FoundryError(
                'MARKETPLACE_PURCHASE_PACK_NOT_FOUND',
                'validation',
                ['pack' => $pack],
                'Marketplace pack was not found.',
            );
        }

        $metadata = $this->resolvePackMetadata($pack);
        $distribution = strtolower((string) ($metadata['distribution'] ?? 'free'));
        $required = (bool) ($metadata['entitlement_required'] ?? ($distribution !== 'free'));

        if ($distribution === 'free' || !$required) {
            return [
                'status' => 'not_purchasable',
                'code' => 'MARKETPLACE_PURCHASE_PACK_NOT_PURCHASABLE',
                'pack' => $pack,
                'reason' => 'free',
                'entitlement_refreshed' => false,
            ];
        }

        $decision = (new PackEntitlementResolver(new MarketplaceEntitlementCache($this->paths)))->resolve(
            $pack,
            [
                'distribution' => $distribution,
                'entitlement_required' => $required,
                'price' => is_array($metadata['price'] ?? null) ? $metadata['price'] : null,
            ],
            true,
        );

        if ((string) ($decision['status'] ?? '') === 'granted') {
            return [
                'status' => 'already_entitled',
                'code' => 'MARKETPLACE_PURCHASE_ALREADY_ENTITLED',
                'pack' => $pack,
                'entitlement_refreshed' => false,
            ];
        }

        $identity = $this->identityStore()->readState();
        if (!((bool) ($identity['authenticated'] ?? false))) {
            throw new FoundryError(
                'MARKETPLACE_PURCHASE_AUTH_REQUIRED',
                'authentication',
                ['pack' => $pack],
                'Marketplace authentication is required for purchases.',
            );
        }

        $purchase = $this->client()->purchase(
            $pack,
            $metadata,
            is_array($identity['safe_user'] ?? null) ? $identity['safe_user'] : null,
        );

        $status = (string) ($purchase['status'] ?? 'error');

        if ($status === 'pending') {
            $checkoutUrl = trim((string) ($purchase['checkout_url'] ?? ''));
            if ($checkoutUrl === '') {
                throw new FoundryError(
                    'MARKETPLACE_PURCHASE_FAILED',
                    'runtime',
                    ['pack' => $pack],
                    'Marketplace purchase failed.',
                );
            }

            return [
                'status' => 'pending',
                'pack' => $pack,
                'checkout_url' => $checkoutUrl,
                'entitlement_refreshed' => false,
            ];
        }

        if ($status === 'success') {
            $incoming = $purchase['entitlements'] ?? [];
            if (!is_array($incoming)) {
                throw new FoundryError(
                    'MARKETPLACE_PURCHASE_FAILED',
                    'runtime',
                    ['pack' => $pack],
                    'Marketplace purchase failed.',
                );
            }

            try {
                $persisted = $this->entitlements()->persistEntitlements(array_values($incoming));
            } catch (FoundryError $error) {
                return [
                    'status' => 'partial',
                    'code' => 'MARKETPLACE_PURCHASE_ENTITLEMENT_REFRESH_FAILED',
                    'pack' => $pack,
                    'entitlement_refreshed' => false,
                ];
            }

            return [
                'status' => 'success',
                'pack' => $pack,
                'entitlement_refreshed' => true,
                'entitlements' => $persisted,
            ];
        }

        throw new FoundryError(
            'MARKETPLACE_PURCHASE_FAILED',
            'runtime',
            ['pack' => $pack],
            'Marketplace purchase failed.',
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function inspectCapability(): array
    {
        return [
            'enabled' => true,
            'client' => 'deterministic',
            'requires_auth_for_paid' => true,
            'supports_browser_handoff' => true,
            'live_mode_required' => false,
        ];
    }

    /**
     * @return array{status:string,errors:list<array<string,mixed>>}
     */
    public function verifyCapability(): array
    {
        return [
            'status' => 'pass',
            'errors' => [],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function resolvePackMetadata(string $pack): array
    {
        try {
            $entry = $this->registry()->resolveLatest($pack);
        } catch (FoundryError $error) {
            $code = $error->errorCode;
            if ($code === 'PACK_REGISTRY_PACK_NOT_FOUND' || $code === 'PACK_REGISTRY_VERSION_NOT_FOUND') {
                throw new FoundryError(
                    'MARKETPLACE_PURCHASE_PACK_NOT_FOUND',
                    'not_found',
                    ['pack' => $pack],
                    'Marketplace pack was not found.',
                    previous: $error,
                );
            }

            if ($code === 'PACK_REGISTRY_UNAVAILABLE') {
                throw new FoundryError(
                    'MARKETPLACE_PURCHASE_MARKETPLACE_UNAVAILABLE',
                    'network',
                    ['pack' => $pack],
                    'Marketplace purchase service is unavailable.',
                    previous: $error,
                );
            }

            throw new FoundryError(
                'MARKETPLACE_PURCHASE_FAILED',
                'runtime',
                ['pack' => $pack],
                'Marketplace purchase failed.',
                previous: $error,
            );
        }

        return [
            'distribution' => strtolower((string) ($entry['distribution'] ?? 'free')),
            'entitlement_required' => (bool) ($entry['entitlement_required'] ?? (strtolower((string) ($entry['distribution'] ?? 'free')) !== 'free')),
            'price' => is_array($entry['price'] ?? null) ? $entry['price'] : null,
        ];
    }

    private function registry(): HostedPackRegistry
    {
        return $this->registry ?? new HostedPackRegistry($this->paths);
    }

    private function identityStore(): MarketplaceIdentityStore
    {
        return $this->identityStore ?? new MarketplaceIdentityStore($this->paths);
    }

    private function entitlements(): MarketplaceEntitlementService
    {
        return $this->entitlements ?? new MarketplaceEntitlementService(
            new MarketplaceEntitlementCache($this->paths),
            $this->identityStore(),
        );
    }

    private function client(): MarketplacePurchaseClient
    {
        return $this->client ?? new MarketplaceDeterministicPurchaseClient();
    }
}
