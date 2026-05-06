<?php

declare(strict_types=1);

namespace Foundry\Marketplace;

use Foundry\Support\FoundryError;

final class MarketplaceVerifier
{
    public function __construct(private readonly MarketplaceRepository $repository) {}

    /**
     * @return array{status:string,checked:array{packs:int,versions:int,artifacts:int},auth:array<string,mixed>,errors:list<array<string,mixed>>}
     */
    public function verify(): array
    {
        $auth = (new MarketplaceIdentityStore($this->repository->paths()))->verify();

        try {
            $index = $this->repository->load();
        } catch (FoundryError $error) {
            $errors = [[
                'code' => $error->errorCode,
                'message' => $error->getMessage(),
                'details' => $error->details,
            ]];
            $errors = array_merge($errors, (array) ($auth['errors'] ?? []));

            return [
                'status' => $errors === [] ? 'pass' : 'fail',
                'checked' => ['packs' => 0, 'versions' => 0, 'artifacts' => 0],
                'auth' => $auth,
                'errors' => $errors,
            ];
        }

        $packCount = count($index->packs);
        $versionCount = 0;
        $artifactCount = 0;
        $errors = [];

        foreach ($index->packs as $pack) {
            foreach ($pack->versions as $version) {
                $versionCount++;
                $artifactPath = $this->repository->storageRootRelative() . '/' . ltrim($version->artifact, '/');

                $absolute = $this->repository->artifactAbsolutePath($version->artifact);
                if (!is_file($absolute)) {
                    $errors[] = [
                        'code' => 'PACK_ARTIFACT_MISSING',
                        'message' => 'Pack artifact is missing.',
                        'details' => [
                            'name' => $pack->name,
                            'version' => $version->version,
                            'artifact' => $artifactPath,
                        ],
                    ];
                    continue;
                }

                $artifactCount++;
                $actual = hash_file('sha256', $absolute);
                if ($actual !== $version->sha256) {
                    $errors[] = [
                        'code' => 'PACK_ARTIFACT_CHECKSUM_MISMATCH',
                        'message' => 'Pack artifact checksum mismatch.',
                        'details' => [
                            'name' => $pack->name,
                            'version' => $version->version,
                            'artifact' => $artifactPath,
                        ],
                    ];
                }
            }
        }

        foreach ((array) ($auth['errors'] ?? []) as $authError) {
            if (is_array($authError)) {
                $errors[] = $authError;
            }
        }

        usort($errors, static function (array $a, array $b): int {
            $aKey = implode('|', [
                (string) ($a['code'] ?? ''),
                (string) (($a['details']['name'] ?? '')),
                (string) (($a['details']['version'] ?? '')),
            ]);
            $bKey = implode('|', [
                (string) ($b['code'] ?? ''),
                (string) (($b['details']['name'] ?? '')),
                (string) (($b['details']['version'] ?? '')),
            ]);

            return strcmp($aKey, $bKey);
        });

        return [
            'status' => ($errors === [] && (string) ($auth['status'] ?? 'fail') === 'pass') ? 'pass' : 'fail',
            'checked' => [
                'packs' => $packCount,
                'versions' => $versionCount,
                'artifacts' => $artifactCount,
            ],
            'auth' => $auth,
            'errors' => $errors,
        ];
    }
}
