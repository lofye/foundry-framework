<?php

declare(strict_types=1);

namespace Foundry\Marketplace;

final class MarketplaceHttpController
{
    public function __construct(private readonly MarketplaceRepository $repository) {}

    /**
     * @return array{status_code:int,payload:array<string,mixed>}
     */
    public function listPacks(): array
    {
        return [
            'status_code' => 200,
            'payload' => $this->repository->load()->packsPayload(),
        ];
    }

    /**
     * @return array{status_code:int,payload:array<string,mixed>}
     */
    public function getPack(string $name): array
    {
        if (!MarketplaceRepository::validPackName($name)) {
            return $this->error(400, 'PACK_INVALID_NAME', 'Pack name is invalid.', ['name' => $name]);
        }

        $pack = $this->repository->find($name);
        if (!$pack instanceof MarketplacePack) {
            return $this->error(404, 'PACK_NOT_FOUND', 'Pack not found.', ['name' => $name]);
        }

        return [
            'status_code' => 200,
            'payload' => [
                'status' => 'ok',
                'pack' => $pack->detail(),
            ],
        ];
    }

    /**
     * @return array{status_code:int,headers:array<string,string>,body:string}|array{status_code:int,payload:array<string,mixed>}
     */
    public function downloadPack(string $name, string $version): array
    {
        if (!MarketplaceRepository::validPackName($name)) {
            return $this->error(400, 'PACK_INVALID_NAME', 'Pack name is invalid.', ['name' => $name]);
        }

        if ($version === '' || str_contains($version, '/') || str_contains($version, '..')) {
            return $this->error(400, 'PACK_INVALID_VERSION', 'Pack version is invalid.', ['name' => $name, 'version' => $version]);
        }

        $pack = $this->repository->find($name);
        if (!$pack instanceof MarketplacePack) {
            return $this->error(404, 'PACK_NOT_FOUND', 'Pack not found.', ['name' => $name]);
        }

        $packVersion = $this->repository->findVersion($name, $version);
        if (!$packVersion instanceof MarketplacePackVersion) {
            return $this->error(404, 'PACK_VERSION_NOT_FOUND', 'Pack version not found.', ['name' => $name, 'version' => $version]);
        }

        $distributionMetadata = $this->repository->distributionMetadataForPack($name);
        if (!is_array($distributionMetadata)) {
            return $this->error(404, 'PACK_NOT_FOUND', 'Pack not found.', ['name' => $name]);
        }

        try {
            $decision = (new PackEntitlementResolver(
                new MarketplaceEntitlementCache($this->repository->paths()),
            ))->resolve($name, $distributionMetadata);
        } catch (\Throwable) {
            return $this->error(
                403,
                'MARKETPLACE_ENTITLEMENT_CACHE_INVALID',
                'Marketplace entitlement cache is invalid.',
                ['name' => $name],
            );
        }

        if ((bool) ($decision['required'] ?? false) && (string) ($decision['status'] ?? 'unknown') !== 'granted') {
            $code = match ((string) ($decision['status'] ?? 'unknown')) {
                'missing' => 'MARKETPLACE_ENTITLEMENT_MISSING',
                'expired' => 'MARKETPLACE_ENTITLEMENT_EXPIRED',
                default => 'MARKETPLACE_ENTITLEMENT_UNKNOWN',
            };

            return $this->error(
                403,
                $code,
                'Marketplace entitlement is required for this pack.',
                ['name' => $name, 'version' => $version, 'status' => (string) ($decision['status'] ?? 'unknown')],
            );
        }

        $absolute = $this->repository->artifactAbsolutePath($packVersion->artifact);
        if (!is_file($absolute)) {
            return $this->error(410, 'PACK_ARTIFACT_MISSING', 'Pack artifact is missing.', ['name' => $name, 'version' => $version, 'artifact' => $this->repository->storageRootRelative() . '/' . ltrim($packVersion->artifact, '/')]);
        }

        $actual = hash_file('sha256', $absolute);
        if ($actual !== $packVersion->sha256) {
            return $this->error(500, 'PACK_ARTIFACT_CHECKSUM_MISMATCH', 'Pack artifact checksum mismatch.', ['name' => $name, 'version' => $version]);
        }

        $safeKey = MarketplaceRepository::safePackKey($name);

        return [
            'status_code' => 200,
            'headers' => [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => 'attachment; filename="' . $safeKey . '-' . $version . '.zip"',
                'X-Foundry-Pack-Name' => $name,
                'X-Foundry-Pack-Version' => $version,
                'X-Foundry-Pack-Sha256' => $packVersion->sha256,
            ],
            'body' => (string) file_get_contents($absolute),
        ];
    }

    /**
     * @param array<string,mixed> $details
     * @return array{status_code:int,payload:array<string,mixed>}
     */
    private function error(int $statusCode, string $code, string $message, array $details): array
    {
        return [
            'status_code' => $statusCode,
            'payload' => [
                'status' => 'error',
                'error' => [
                    'code' => $code,
                    'message' => $message,
                    'details' => $details,
                ],
            ],
        ];
    }
}
