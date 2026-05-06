<?php

declare(strict_types=1);

namespace Foundry\Marketplace;

use Foundry\Support\FoundryError;
use Foundry\Support\Paths;

final class MarketplaceRepository
{
    public function __construct(private readonly Paths $paths) {}

    public function paths(): Paths
    {
        return $this->paths;
    }

    public function storageRootRelative(): string
    {
        return '.foundry/marketplace';
    }

    public function indexPathRelative(): string
    {
        return '.foundry/marketplace/packs.json';
    }

    /**
     * @return array<string,mixed>
     */
    public function inspect(): array
    {
        $index = $this->load();
        $auth = (new MarketplaceIdentityStore($this->paths))->inspect();
        $packs = [];
        $versionTotal = 0;
        $artifactTotal = 0;

        foreach ($index->packs as $pack) {
            $versionCount = count($pack->versions);
            $artifactCount = 0;
            foreach ($pack->versions as $version) {
                if (is_file($this->artifactAbsolutePath($version->artifact))) {
                    $artifactCount++;
                    $artifactTotal++;
                }
            }

            $versionTotal += $versionCount;
            $packs[] = [
                'name' => $pack->name,
                'latest_version' => $pack->latestVersion,
                'version_count' => $versionCount,
                'artifact_count' => $artifactCount,
            ];
        }

        return [
            'status' => 'ok',
            'storage' => [
                'root' => $this->storageRootRelative(),
                'index' => $this->indexPathRelative(),
            ],
            'auth' => $auth,
            'packs' => $packs,
            'totals' => [
                'packs' => count($packs),
                'versions' => $versionTotal,
                'artifacts' => $artifactTotal,
            ],
        ];
    }

    public function load(): MarketplaceIndex
    {
        $absolute = $this->paths->join($this->indexPathRelative());
        if (!is_file($absolute)) {
            return new MarketplaceIndex([]);
        }

        $decoded = json_decode((string) file_get_contents($absolute), true);
        if (!is_array($decoded)) {
            throw new FoundryError('MARKETPLACE_INDEX_INVALID_JSON', 'validation', ['index' => $this->indexPathRelative()], 'Marketplace index must be valid JSON.');
        }

        $packs = $decoded['packs'] ?? [];
        if (!is_array($packs)) {
            throw new FoundryError('MARKETPLACE_INDEX_INVALID_SHAPE', 'validation', ['index' => $this->indexPathRelative()], 'Marketplace index must contain a packs array.');
        }

        $rows = [];
        $seenPackNames = [];
        foreach ($packs as $row) {
            if (!is_array($row)) {
                throw new FoundryError('MARKETPLACE_PACK_INVALID_SHAPE', 'validation', [], 'Marketplace pack entries must be objects.');
            }

            $pack = $this->parsePack($row);
            if (isset($seenPackNames[$pack->name])) {
                throw new FoundryError('MARKETPLACE_PACK_DUPLICATE_NAME', 'validation', ['name' => $pack->name], 'Marketplace pack names must be unique.');
            }

            $seenPackNames[$pack->name] = true;
            $rows[] = $pack;
        }

        usort($rows, static fn(MarketplacePack $a, MarketplacePack $b): int => strcmp($a->name, $b->name));

        return new MarketplaceIndex($rows);
    }

    public function find(string $name): ?MarketplacePack
    {
        foreach ($this->load()->packs as $pack) {
            if ($pack->name === $name) {
                return $pack;
            }
        }

        return null;
    }

    public function findVersion(string $name, string $version): ?MarketplacePackVersion
    {
        $pack = $this->find($name);
        if (!$pack instanceof MarketplacePack) {
            return null;
        }

        foreach ($pack->versions as $candidate) {
            if ($candidate->version === $version) {
                return $candidate;
            }
        }

        return null;
    }

    public function artifactAbsolutePath(string $artifact): string
    {
        $root = $this->paths->join($this->storageRootRelative());
        $candidate = $root . '/' . ltrim($artifact, '/');
        $normalizedRoot = rtrim(str_replace('\\', '/', $root), '/');
        $normalizedCandidate = str_replace('\\', '/', $candidate);

        if (!str_starts_with($normalizedCandidate, $normalizedRoot . '/')) {
            throw new FoundryError('MARKETPLACE_ARTIFACT_PATH_TRAVERSAL', 'validation', ['artifact' => $artifact], 'Marketplace artifact path escapes storage root.');
        }

        return $candidate;
    }

    public static function safePackKey(string $name): string
    {
        return str_replace('/', '__', $name);
    }

    public static function validPackName(string $name): bool
    {
        if ($name === '' || str_starts_with($name, '/') || str_ends_with($name, '/')) {
            return false;
        }

        if (str_contains($name, '//') || str_contains($name, '..') || str_contains($name, ' ')) {
            return false;
        }

        if ($name !== strtolower($name)) {
            return false;
        }

        return preg_match('/^[a-z0-9._\/-]+$/', $name) === 1;
    }

    private function validArtifactRelativePath(string $path): bool
    {
        if ($path === '' || str_starts_with($path, '/') || str_contains($path, '..')) {
            return false;
        }

        if (!str_starts_with($path, 'artifacts/')) {
            return false;
        }

        return !str_contains($path, '\\');
    }

    private function validUtcTimestamp(string $value): bool
    {
        return preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/', $value) === 1;
    }

    private function validSha256(string $value): bool
    {
        return preg_match('/^[a-f0-9]{64}$/', $value) === 1;
    }

    /**
     * @param array<string,mixed> $row
     */
    private function parsePack(array $row): MarketplacePack
    {
        $name = (string) ($row['name'] ?? '');
        if (!self::validPackName($name)) {
            throw new FoundryError('MARKETPLACE_PACK_INVALID_NAME', 'validation', ['name' => $name], 'Marketplace pack name is invalid.');
        }

        $versionsRaw = $row['versions'] ?? [];
        if (!is_array($versionsRaw)) {
            throw new FoundryError('MARKETPLACE_PACK_INVALID_VERSIONS', 'validation', ['name' => $name], 'Marketplace pack versions must be an array.');
        }

        $seenVersions = [];
        $versions = [];
        foreach ($versionsRaw as $versionRow) {
            if (!is_array($versionRow)) {
                throw new FoundryError('MARKETPLACE_VERSION_INVALID_SHAPE', 'validation', ['name' => $name], 'Marketplace pack version entries must be objects.');
            }

            $version = (string) ($versionRow['version'] ?? '');
            if ($version === '' || isset($seenVersions[$version])) {
                throw new FoundryError('MARKETPLACE_PACK_DUPLICATE_VERSION', 'validation', ['name' => $name, 'version' => $version], 'Marketplace pack versions must be unique.');
            }

            $artifact = (string) ($versionRow['artifact'] ?? '');
            if (!$this->validArtifactRelativePath($artifact)) {
                throw new FoundryError('MARKETPLACE_ARTIFACT_PATH_INVALID', 'validation', ['name' => $name, 'version' => $version, 'artifact' => $artifact], 'Marketplace artifact path is invalid.');
            }

            $sha256 = strtolower((string) ($versionRow['sha256'] ?? ''));
            if (!$this->validSha256($sha256)) {
                throw new FoundryError('MARKETPLACE_ARTIFACT_SHA256_INVALID', 'validation', ['name' => $name, 'version' => $version], 'Marketplace artifact checksum must be a lowercase sha256 hex string.');
            }

            $publishedAt = (string) ($versionRow['published_at'] ?? '');
            if (!$this->validUtcTimestamp($publishedAt)) {
                throw new FoundryError('MARKETPLACE_PUBLISHED_AT_INVALID', 'validation', ['name' => $name, 'version' => $version], 'Marketplace published_at must be an ISO-8601 UTC timestamp.');
            }

            $metadata = is_array($versionRow['metadata'] ?? null) ? $versionRow['metadata'] : [];
            $tags = array_values(array_unique(array_map('strval', (array) ($metadata['tags'] ?? []))));
            sort($tags);

            $versions[] = new MarketplacePackVersion(
                version: $version,
                requiresFoundry: (string) ($versionRow['requires_foundry'] ?? ''),
                artifact: $artifact,
                sha256: $sha256,
                publishedAt: $publishedAt,
                homepage: $this->nullableString($metadata['homepage'] ?? null),
                license: $this->nullableString($metadata['license'] ?? null),
                tags: $tags,
            );
            $seenVersions[$version] = true;
        }

        usort($versions, fn(MarketplacePackVersion $a, MarketplacePackVersion $b): int => $this->compareVersions($a->version, $b->version));

        $latestVersion = (string) ($row['latest_version'] ?? '');
        if (!isset($seenVersions[$latestVersion])) {
            throw new FoundryError('MARKETPLACE_LATEST_VERSION_INVALID', 'validation', ['name' => $name, 'latest_version' => $latestVersion], 'Marketplace latest_version must match one of the declared versions.');
        }

        $metadata = is_array($row['metadata'] ?? null) ? $row['metadata'] : [];
        $tags = array_values(array_unique(array_map('strval', (array) ($metadata['tags'] ?? []))));
        sort($tags);

        return new MarketplacePack(
            name: $name,
            displayName: (string) ($row['display_name'] ?? ''),
            description: (string) ($row['description'] ?? ''),
            vendor: (string) ($row['vendor'] ?? ''),
            latestVersion: $latestVersion,
            versions: $versions,
            homepage: $this->nullableString($metadata['homepage'] ?? null),
            license: $this->nullableString($metadata['license'] ?? null),
            tags: $tags,
        );
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return (string) $value;
    }

    private function compareVersions(string $left, string $right): int
    {
        $semver = '/^\d+\.\d+\.\d+(?:[-+][0-9A-Za-z.-]+)?$/';
        if (preg_match($semver, $left) === 1 && preg_match($semver, $right) === 1) {
            return version_compare($right, $left);
        }

        return strcmp($right, $left);
    }
}
