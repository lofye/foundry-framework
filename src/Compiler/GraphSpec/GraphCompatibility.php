<?php

declare(strict_types=1);

namespace Foundry\Compiler\GraphSpec;

final class GraphCompatibility
{
    /**
     * @param array<int,int> $versions
     * @return array<int,int>
     */
    public static function normalizeVersions(array $versions, int $currentGraphVersion): array
    {
        $normalized = array_values(array_filter(
            array_values(array_map('intval', $versions)),
            static fn(int $version): bool => $version > 0,
        ));

        sort($normalized);
        $normalized = array_values(array_unique($normalized));

        if ($normalized === []) {
            return [$currentGraphVersion];
        }

        if ($currentGraphVersion > 1 && $normalized === [1]) {
            $normalized[] = $currentGraphVersion;
        }

        sort($normalized);

        return array_values(array_unique($normalized));
    }

    /**
     * @param array<int,int> $versions
     */
    public static function supportsGraphVersion(array $versions, int $graphVersion): bool
    {
        return in_array($graphVersion, self::normalizeVersions($versions, $graphVersion), true);
    }
}
