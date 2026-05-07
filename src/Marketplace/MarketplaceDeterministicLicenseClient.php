<?php

declare(strict_types=1);

namespace Foundry\Marketplace;

use Foundry\Support\Clock;
use Foundry\Support\FoundryError;

final class MarketplaceDeterministicLicenseClient implements MarketplaceLicenseClient
{
    public function __construct(private readonly ?Clock $clock = null) {}

    /**
     * @param array<string,mixed> $context
     * @return array{entitlements:list<array<string,mixed>>}
     */
    public function activate(string $licenseKey, array $context = []): array
    {
        $normalized = strtoupper(trim($licenseKey));
        if ($normalized === '' || preg_match('/\s/', $normalized) === 1) {
            throw new FoundryError(
                'MARKETPLACE_LICENSE_INVALID',
                'validation',
                [],
                'Marketplace license key is invalid.',
            );
        }

        if (preg_match('/^[A-Z0-9_-]{8,}$/', str_replace('-', '', $normalized)) !== 1) {
            throw new FoundryError(
                'MARKETPLACE_LICENSE_INVALID',
                'validation',
                [],
                'Marketplace license key is invalid.',
            );
        }

        if (str_contains($normalized, 'INVALID')) {
            throw new FoundryError(
                'MARKETPLACE_LICENSE_INVALID',
                'validation',
                [],
                'Marketplace license key is invalid.',
            );
        }

        if (str_contains($normalized, 'FAIL')) {
            throw new FoundryError(
                'MARKETPLACE_LICENSE_ACTIVATION_FAILED',
                'runtime',
                [],
                'Marketplace license activation failed.',
            );
        }

        return [
            'entitlements' => [[
                'pack' => 'vendor/premium-pack',
                'type' => 'premium',
                'status' => 'granted',
                'expires_at' => null,
                'source' => 'marketplace',
                'granted_at' => $this->clock()->nowIso8601(),
            ]],
        ];
    }

    private function clock(): Clock
    {
        return $this->clock ?? new Clock();
    }
}
