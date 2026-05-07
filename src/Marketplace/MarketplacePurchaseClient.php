<?php

declare(strict_types=1);

namespace Foundry\Marketplace;

interface MarketplacePurchaseClient
{
    /**
     * @param array<string,mixed> $metadata
     * @param array<string,mixed>|null $identity
     * @return array<string,mixed>
     */
    public function purchase(string $pack, array $metadata, ?array $identity): array;
}
