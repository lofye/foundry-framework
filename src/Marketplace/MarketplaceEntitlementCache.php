<?php

declare(strict_types=1);

namespace Foundry\Marketplace;

use Foundry\Support\Clock;
use Foundry\Support\FoundryError;
use Foundry\Support\Json;
use Foundry\Support\Paths;

final class MarketplaceEntitlementCache
{
    public function __construct(
        private readonly Paths $paths,
        private readonly ?Clock $clock = null,
    ) {}

    public function cachePathRelative(): string
    {
        return '.foundry/marketplace/entitlements.json';
    }

    public function cachePathAbsolute(): string
    {
        return $this->paths->join($this->cachePathRelative());
    }

    /**
     * @param list<array<string,mixed>> $entitlements
     * @return list<array<string,mixed>>
     */
    public function persist(array $entitlements): array
    {
        $normalized = $this->normalizeEntitlements($entitlements);
        $path = $this->cachePathAbsolute();
        $directory = dirname($path);

        if (!is_dir($directory) && !@mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new FoundryError(
                'MARKETPLACE_ENTITLEMENT_CACHE_INVALID',
                'filesystem',
                ['path' => $this->cachePathRelative()],
                'Marketplace entitlement cache directory is not writable.',
            );
        }

        $payload = [
            'entitlements' => $normalized,
            'updated_at' => $this->clock()->nowIso8601(),
        ];

        $tmp = $path . '.tmp';
        $encoded = Json::encode($payload, true) . PHP_EOL;
        if (@file_put_contents($tmp, $encoded) === false || !@rename($tmp, $path)) {
            @unlink($tmp);
            throw new FoundryError(
                'MARKETPLACE_ENTITLEMENT_CACHE_INVALID',
                'filesystem',
                ['path' => $this->cachePathRelative()],
                'Marketplace entitlement cache could not be written.',
            );
        }

        return $normalized;
    }

    /**
     * @return array<string,mixed>
     */
    public function inspect(): array
    {
        $state = $this->readState();

        return [
            'configured' => (bool) ($state['configured'] ?? false),
            'status' => (string) ($state['status'] ?? 'invalid'),
            'code' => $state['code'] ?? null,
            'path' => $this->cachePathRelative(),
            'count' => count((array) ($state['entitlements'] ?? [])),
            'entitlements' => array_values((array) ($state['entitlements'] ?? [])),
        ];
    }

    /**
     * @return array{status:string,code:?string,errors:list<array<string,mixed>>}
     */
    public function verify(): array
    {
        $state = $this->readState();
        if ((string) ($state['status'] ?? '') === 'invalid') {
            return [
                'status' => 'fail',
                'code' => 'MARKETPLACE_ENTITLEMENT_CACHE_INVALID',
                'errors' => [[
                    'code' => 'MARKETPLACE_ENTITLEMENT_CACHE_INVALID',
                    'message' => (string) ($state['message'] ?? 'Marketplace entitlement cache is invalid.'),
                    'details' => ['path' => $this->cachePathRelative()],
                ]],
            ];
        }

        return [
            'status' => 'pass',
            'code' => null,
            'errors' => [],
        ];
    }

    /**
     * @return array{configured:bool,status:string,code:?string,message:?string,entitlements:list<array<string,mixed>>}
     */
    public function readState(): array
    {
        $path = $this->cachePathAbsolute();
        if (!is_file($path)) {
            return [
                'configured' => false,
                'status' => 'missing',
                'code' => null,
                'message' => null,
                'entitlements' => [],
            ];
        }

        $raw = @file_get_contents($path);
        if (!is_string($raw)) {
            return [
                'configured' => true,
                'status' => 'invalid',
                'code' => 'MARKETPLACE_ENTITLEMENT_CACHE_INVALID',
                'message' => 'Marketplace entitlement cache could not be read.',
                'entitlements' => [],
            ];
        }

        try {
            $decoded = Json::decodeAssoc($raw);
            $rows = $decoded['entitlements'] ?? [];
            if (!is_array($rows)) {
                throw new FoundryError('MARKETPLACE_ENTITLEMENT_CACHE_INVALID', 'validation', ['path' => $this->cachePathRelative()], 'Marketplace entitlement cache is invalid.');
            }

            return [
                'configured' => true,
                'status' => 'ok',
                'code' => null,
                'message' => null,
                'entitlements' => $this->normalizeEntitlements($rows),
            ];
        } catch (\Throwable $error) {
            return [
                'configured' => true,
                'status' => 'invalid',
                'code' => 'MARKETPLACE_ENTITLEMENT_CACHE_INVALID',
                'message' => 'Marketplace entitlement cache is invalid.',
                'entitlements' => [],
            ];
        }
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function entitlements(): array
    {
        return array_values((array) ($this->readState()['entitlements'] ?? []));
    }

    /**
     * @param array<mixed> $rows
     * @return list<array<string,mixed>>
     */
    private function normalizeEntitlements(array $rows): array
    {
        $normalized = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                throw new FoundryError(
                    'MARKETPLACE_ENTITLEMENT_CACHE_INVALID',
                    'validation',
                    ['path' => $this->cachePathRelative()],
                    'Marketplace entitlement cache is invalid.',
                );
            }

            $pack = trim((string) ($row['pack'] ?? ''));
            $type = strtolower(trim((string) ($row['type'] ?? '')));
            $status = strtolower(trim((string) ($row['status'] ?? '')));
            $expiresAt = $row['expires_at'] ?? null;
            $source = $row['source'] ?? null;
            $grantedAt = $row['granted_at'] ?? null;

            if (!MarketplaceRepository::validPackName($pack)) {
                throw new FoundryError(
                    'MARKETPLACE_ENTITLEMENT_CACHE_INVALID',
                    'validation',
                    ['path' => $this->cachePathRelative(), 'pack' => $pack],
                    'Marketplace entitlement cache is invalid.',
                );
            }

            if (!in_array($type, ['free', 'licensed', 'premium'], true)) {
                throw new FoundryError(
                    'MARKETPLACE_ENTITLEMENT_CACHE_INVALID',
                    'validation',
                    ['path' => $this->cachePathRelative(), 'type' => $type],
                    'Marketplace entitlement cache is invalid.',
                );
            }

            if (!in_array($status, ['granted', 'missing', 'expired', 'unknown'], true)) {
                throw new FoundryError(
                    'MARKETPLACE_ENTITLEMENT_CACHE_INVALID',
                    'validation',
                    ['path' => $this->cachePathRelative(), 'status' => $status],
                    'Marketplace entitlement cache is invalid.',
                );
            }

            $expiresAt = $this->nullableIso8601($expiresAt);
            $grantedAt = $this->nullableIso8601($grantedAt);
            $source = $source === null ? null : trim((string) $source);

            if ($status === 'granted' && $this->isExpired($expiresAt)) {
                $status = 'expired';
            }

            $normalized[] = [
                'pack' => $pack,
                'type' => $type,
                'status' => $status,
                'expires_at' => $expiresAt,
                'source' => $source,
                'granted_at' => $grantedAt,
            ];
        }

        usort($normalized, static function (array $a, array $b): int {
            $aKey = implode('|', [(string) ($a['pack'] ?? ''), (string) ($a['type'] ?? ''), (string) ($a['status'] ?? '')]);
            $bKey = implode('|', [(string) ($b['pack'] ?? ''), (string) ($b['type'] ?? ''), (string) ($b['status'] ?? '')]);

            return strcmp($aKey, $bKey);
        });

        return $normalized;
    }

    private function nullableIso8601(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }

        if (!$this->isIso8601($normalized)) {
            throw new FoundryError(
                'MARKETPLACE_ENTITLEMENT_CACHE_INVALID',
                'validation',
                ['path' => $this->cachePathRelative()],
                'Marketplace entitlement cache is invalid.',
            );
        }

        return $normalized;
    }

    private function isExpired(?string $expiresAt): bool
    {
        if ($expiresAt === null || $expiresAt === '') {
            return false;
        }

        return (new \DateTimeImmutable($expiresAt)) <= $this->clock()->now();
    }

    private function isIso8601(string $value): bool
    {
        try {
            $dt = new \DateTimeImmutable($value);
        } catch (\Throwable) {
            return false;
        }

        return $dt->format(\DateTimeInterface::ATOM) === $value || $dt->format('Y-m-d\\TH:i:s\\Z') === $value;
    }

    private function clock(): Clock
    {
        return $this->clock ?? new Clock();
    }
}
