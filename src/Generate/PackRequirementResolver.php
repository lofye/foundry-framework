<?php

declare(strict_types=1);

namespace Foundry\Generate;

use Foundry\Compiler\Extensions\PackRegistry;

final class PackRequirementResolver
{
    /**
     * @return array{missing_capabilities:array<int,string>,suggested_packs:array<int,string>}
     */
    public function resolve(Intent $intent, PackRegistry $packs): array
    {
        $missingCapabilities = [];
        $suggestedPacks = [];

        foreach ($intent->packHints as $pack) {
            if ($packs->has($pack)) {
                continue;
            }

            $missingCapabilities[] = 'pack:' . $pack;
            $suggestedPacks[] = $pack;
        }

        $missingCapabilities = array_values(array_unique(array_map('strval', $missingCapabilities)));
        sort($missingCapabilities);
        $suggestedPacks = array_values(array_unique(array_map('strval', $suggestedPacks)));
        sort($suggestedPacks);

        return [
            'missing_capabilities' => $missingCapabilities,
            'suggested_packs' => $suggestedPacks,
        ];
    }
}
