<?php

declare(strict_types=1);

namespace Foundry\Marketplace;

use Foundry\Support\FoundryError;

final class MarketplaceDeterministicPurchaseClient implements MarketplacePurchaseClient
{
    /**
     * @param array<string,mixed> $metadata
     * @param array<string,mixed>|null $identity
     * @return array<string,mixed>
     */
    public function purchase(string $pack, array $metadata, ?array $identity): array
    {
        if (str_contains($pack, 'unavailable')) {
            throw new FoundryError(
                'MARKETPLACE_PURCHASE_MARKETPLACE_UNAVAILABLE',
                'network',
                ['pack' => $pack],
                'Marketplace purchase service is unavailable.',
            );
        }

        if (str_contains($pack, 'fail')) {
            throw new FoundryError(
                'MARKETPLACE_PURCHASE_FAILED',
                'runtime',
                ['pack' => $pack],
                'Marketplace purchase failed.',
            );
        }

        if (str_contains($pack, 'complete')) {
            return [
                'status' => 'success',
                'entitlements' => [[
                    'pack' => $pack,
                    'type' => (string) ($metadata['distribution'] ?? 'premium'),
                    'status' => 'granted',
                    'expires_at' => null,
                    'source' => 'marketplace',
                    'granted_at' => null,
                ]],
            ];
        }

        return [
            'status' => 'pending',
            'checkout_url' => 'https://marketplace.example/checkout/' . rawurlencode(str_replace('/', '-', $pack)),
        ];
    }
}
