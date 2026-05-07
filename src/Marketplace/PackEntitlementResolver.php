<?php

declare(strict_types=1);

namespace Foundry\Marketplace;

use Foundry\Support\FoundryError;

final class PackEntitlementResolver
{
    public function __construct(private readonly MarketplaceEntitlementCache $cache) {}

    /**
     * @param array<string,mixed> $distributionMetadata
     * @return array{distribution:string,entitlement_required:bool,price:array<string,string>|null}
     */
    public function validateDistributionMetadata(array $distributionMetadata, string $pack): array
    {
        $distribution = strtolower(trim((string) ($distributionMetadata['distribution'] ?? '')));
        if (!in_array($distribution, ['free', 'licensed', 'premium'], true)) {
            throw new FoundryError(
                'MARKETPLACE_DISTRIBUTION_METADATA_INVALID',
                'validation',
                ['pack' => $pack],
                'Marketplace distribution metadata is invalid.',
            );
        }

        $entitlementRequiredRaw = $distributionMetadata['entitlement_required'] ?? null;
        $entitlementRequired = $distribution !== 'free';
        if ($entitlementRequiredRaw !== null) {
            if (!is_bool($entitlementRequiredRaw)) {
                throw new FoundryError(
                    'MARKETPLACE_DISTRIBUTION_METADATA_INVALID',
                    'validation',
                    ['pack' => $pack],
                    'Marketplace distribution metadata is invalid.',
                );
            }
            $entitlementRequired = $entitlementRequiredRaw;
        }

        if ($distribution === 'free' && $entitlementRequired) {
            throw new FoundryError(
                'MARKETPLACE_DISTRIBUTION_METADATA_INVALID',
                'validation',
                ['pack' => $pack],
                'Free packs cannot require entitlements.',
            );
        }

        if ($distribution === 'premium' && !$entitlementRequired) {
            throw new FoundryError(
                'MARKETPLACE_DISTRIBUTION_METADATA_INVALID',
                'validation',
                ['pack' => $pack],
                'Premium packs must require entitlements.',
            );
        }

        $price = $this->normalizePrice($distributionMetadata['price'] ?? null, $pack);

        return [
            'distribution' => $distribution,
            'entitlement_required' => $entitlementRequired,
            'price' => $price,
        ];
    }

    /**
     * @param array<string,mixed> $distributionMetadata
     * @return array{pack:string,required:bool,status:string,tier:string,source:string,offline:bool,expires_at:?string}
     */
    public function resolve(string $pack, array $distributionMetadata, bool $offline = false): array
    {
        $metadata = $this->validateDistributionMetadata($distributionMetadata, $pack);
        $tier = (string) $metadata['distribution'];
        $required = (bool) $metadata['entitlement_required'];

        if (!$required) {
            return [
                'pack' => $pack,
                'required' => false,
                'status' => 'granted',
                'tier' => $tier,
                'source' => 'metadata',
                'offline' => $offline,
                'expires_at' => null,
            ];
        }

        $state = $this->cache->readState();
        if ((string) ($state['status'] ?? '') === 'invalid') {
            throw new FoundryError(
                'MARKETPLACE_ENTITLEMENT_CACHE_INVALID',
                'validation',
                ['path' => $this->cache->cachePathRelative()],
                'Marketplace entitlement cache is invalid.',
            );
        }

        $entitlement = $this->bestEntitlementForPack($pack, (array) ($state['entitlements'] ?? []));
        if ($entitlement === null) {
            return [
                'pack' => $pack,
                'required' => true,
                'status' => 'missing',
                'tier' => $tier,
                'source' => 'marketplace',
                'offline' => $offline,
                'expires_at' => null,
            ];
        }

        $status = (string) ($entitlement['status'] ?? 'unknown');
        if ($status === 'granted' && $tier === 'premium' && $this->isExpired($entitlement['expires_at'] ?? null)) {
            $status = 'expired';
        }

        if (!in_array($status, ['granted', 'missing', 'expired', 'unknown'], true)) {
            $status = 'unknown';
        }

        return [
            'pack' => $pack,
            'required' => true,
            'status' => $status,
            'tier' => $tier,
            'source' => (string) (($entitlement['source'] ?? 'marketplace') ?: 'marketplace'),
            'offline' => $offline,
            'expires_at' => is_string($entitlement['expires_at'] ?? null) ? $entitlement['expires_at'] : null,
        ];
    }

    /**
     * @param array<string,mixed> $price
     * @return array<string,string>|null
     */
    private function normalizePrice(mixed $price, string $pack): ?array
    {
        if ($price === null) {
            return null;
        }

        if (!is_array($price)) {
            throw new FoundryError(
                'MARKETPLACE_DISTRIBUTION_METADATA_INVALID',
                'validation',
                ['pack' => $pack],
                'Marketplace distribution metadata is invalid.',
            );
        }

        $currency = strtoupper(trim((string) ($price['currency'] ?? '')));
        $amount = trim((string) ($price['amount'] ?? ''));

        if (preg_match('/^[A-Z]{3}$/', $currency) !== 1 || preg_match('/^\d+\.\d{2}$/', $amount) !== 1) {
            throw new FoundryError(
                'MARKETPLACE_DISTRIBUTION_METADATA_INVALID',
                'validation',
                ['pack' => $pack],
                'Marketplace distribution metadata is invalid.',
            );
        }

        return ['currency' => $currency, 'amount' => $amount];
    }

    /**
     * @param array<int,array<string,mixed>> $entitlements
     * @return array<string,mixed>|null
     */
    private function bestEntitlementForPack(string $pack, array $entitlements): ?array
    {
        $matches = array_values(array_filter(
            $entitlements,
            static fn(array $row): bool => (string) ($row['pack'] ?? '') === $pack,
        ));

        if ($matches === []) {
            return null;
        }

        usort($matches, static function (array $a, array $b): int {
            $aKey = implode('|', [(string) ($a['pack'] ?? ''), (string) ($a['type'] ?? ''), (string) ($a['status'] ?? '')]);
            $bKey = implode('|', [(string) ($b['pack'] ?? ''), (string) ($b['type'] ?? ''), (string) ($b['status'] ?? '')]);

            return strcmp($aKey, $bKey);
        });

        return $matches[0];
    }

    private function isExpired(mixed $value): bool
    {
        if (!is_string($value) || trim($value) === '') {
            return false;
        }

        return (new \DateTimeImmutable($value)) <= new \DateTimeImmutable('now');
    }
}
