<?php

declare(strict_types=1);

namespace Foundry\Monetization;

final class FeatureFlags
{
    public const TIER_FREE = 'free';
    public const TIER_PRO = 'pro';

    public const PRO_DEEP_DIAGNOSTICS = 'feature.pro.deep_diagnostics';
    public const PRO_EXPLAIN_PLUS = 'feature.pro.explain_plus';
    public const PRO_GRAPH_DIFF = 'feature.pro.graph_diff';
    public const PRO_TRACE = 'feature.pro.trace';
    public const PRO_GENERATE = 'feature.pro.generate';
    public const HOSTED_SYNC = 'feature.hosted.sync';

    /**
     * @return list<string>
     */
    public static function pro(): array
    {
        return [
            self::PRO_DEEP_DIAGNOSTICS,
            self::PRO_EXPLAIN_PLUS,
            self::PRO_GRAPH_DIFF,
            self::PRO_TRACE,
            self::PRO_GENERATE,
        ];
    }

    /**
     * @return list<string>
     */
    public static function licensed(): array
    {
        return self::pro();
    }

    /**
     * @return array<string,list<string>>
     */
    public static function requiredTiers(): array
    {
        return [
            self::PRO_DEEP_DIAGNOSTICS => [self::TIER_PRO],
            self::PRO_EXPLAIN_PLUS => [self::TIER_PRO],
            self::PRO_GRAPH_DIFF => [self::TIER_PRO],
            self::PRO_TRACE => [self::TIER_PRO],
            self::PRO_GENERATE => [self::TIER_PRO],
            self::HOSTED_SYNC => [self::TIER_PRO],
        ];
    }

    /**
     * @return list<string>
     */
    public static function enabledForTier(string $tier): array
    {
        $enabled = [];

        foreach (self::requiredTiers() as $feature => $tiers) {
            if (in_array($tier, $tiers, true)) {
                $enabled[] = $feature;
            }
        }

        sort($enabled);

        return $enabled;
    }
}
