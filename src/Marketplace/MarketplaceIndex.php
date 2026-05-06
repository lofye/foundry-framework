<?php

declare(strict_types=1);

namespace Foundry\Marketplace;

final readonly class MarketplaceIndex
{
    /**
     * @param list<MarketplacePack> $packs
     */
    public function __construct(public array $packs) {}

    /**
     * @return array<string,mixed>
     */
    public function packsPayload(): array
    {
        return [
            'status' => 'ok',
            'packs' => array_values(array_map(
                static fn(MarketplacePack $pack): array => $pack->summary(),
                $this->packs,
            )),
        ];
    }
}

