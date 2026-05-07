<?php

declare(strict_types=1);

namespace Foundry\Marketplace;

interface MarketplaceLicenseClient
{
    /**
     * @param array<string,mixed> $context
     * @return array{entitlements:list<array<string,mixed>>}
     */
    public function activate(string $licenseKey, array $context = []): array;
}
