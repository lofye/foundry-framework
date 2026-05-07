<?php

declare(strict_types=1);

namespace Foundry\Generate;

use Foundry\Marketplace\PackEntitlementResolver;
use Foundry\Packs\HostedPackRegistry;
use Foundry\Support\FoundryError;
use Foundry\Compiler\Extensions\PackRegistry;

final class PackRequirementResolver
{
    public function __construct(
        private readonly ?HostedPackRegistry $hostedRegistry = null,
        private readonly ?PackEntitlementResolver $entitlementResolver = null,
    ) {}

    /**
     * @return array{
     *   missing_capabilities:array<int,string>,
     *   suggested_packs:array<int,string>,
     *   pack_requirements:array<int,array<string,mixed>>,
     *   entitlements:array<string,mixed>,
     *   execution_state:string,
     *   errors:array<int,array<string,mixed>>
     * }
     */
    public function resolve(Intent $intent, PackRegistry $packs): array
    {
        $missingCapabilities = [];
        $suggestedPacks = [];
        $packRequirements = [];
        $errors = [];

        $packHints = array_values(array_unique(array_map('strval', $intent->packHints)));
        sort($packHints);

        foreach ($packHints as $pack) {
            if ($packs->has($pack)) {
                $packRequirements[] = [
                    'pack' => $pack,
                    'source' => 'local',
                    'distribution' => 'local',
                    'entitlement' => [
                        'required' => false,
                        'status' => 'not_required',
                        'tier' => 'local',
                    ],
                    'executable' => true,
                ];
                continue;
            }

            $missingCapabilities[] = 'pack:' . $pack;
            $suggestedPacks[] = $pack;
            $marketplace = $this->resolveMarketplaceRequirement($pack);
            $packRequirements[] = $marketplace['requirement'];
            if (is_array($marketplace['error'] ?? null)) {
                $errors[] = $marketplace['error'];
            }
        }

        $missingCapabilities = array_values(array_unique(array_map('strval', $missingCapabilities)));
        sort($missingCapabilities);
        $suggestedPacks = array_values(array_unique(array_map('strval', $suggestedPacks)));
        sort($suggestedPacks);
        usort($packRequirements, static fn(array $a, array $b): int => strcmp((string) ($a['pack'] ?? ''), (string) ($b['pack'] ?? '')));
        usort($errors, static fn(array $a, array $b): int => strcmp((string) ($a['pack'] ?? ''), (string) ($b['pack'] ?? '')));

        $entitlements = $this->entitlementSummary($packRequirements);
        $executionState = $this->executionState($entitlements, $errors);

        return [
            'missing_capabilities' => $missingCapabilities,
            'suggested_packs' => $suggestedPacks,
            'pack_requirements' => $packRequirements,
            'entitlements' => $entitlements,
            'execution_state' => $executionState,
            'errors' => $errors,
        ];
    }

    /**
     * @return array{requirement:array<string,mixed>,error:?array<string,mixed>}
     */
    private function resolveMarketplaceRequirement(string $pack): array
    {
        if (!$this->hostedRegistry instanceof HostedPackRegistry || !$this->entitlementResolver instanceof PackEntitlementResolver) {
            return [
                'requirement' => $this->unknownRequirement($pack, 'MARKETPLACE_PACK_NOT_AVAILABLE'),
                'error' => [
                    'code' => 'MARKETPLACE_PACK_NOT_AVAILABLE',
                    'pack' => $pack,
                    'message' => 'Marketplace pack is not available.',
                ],
            ];
        }

        try {
            $metadata = $this->hostedRegistry->resolveLatest($pack);
        } catch (FoundryError $error) {
            return [
                'requirement' => $this->unknownRequirement($pack, 'MARKETPLACE_PACK_NOT_AVAILABLE'),
                'error' => [
                    'code' => 'MARKETPLACE_PACK_NOT_AVAILABLE',
                    'pack' => $pack,
                    'source_error' => $error->errorCode,
                    'message' => 'Marketplace pack is not available.',
                ],
            ];
        }

        try {
            $decision = $this->entitlementResolver->resolve(
                $pack,
                [
                    'distribution' => $metadata['distribution'] ?? 'free',
                    'entitlement_required' => $metadata['entitlement_required'] ?? null,
                    'price' => $metadata['price'] ?? null,
                ],
                true,
            );
        } catch (FoundryError $error) {
            return [
                'requirement' => $this->unknownRequirement($pack, 'ENTITLEMENT_VALIDATION_FAILED'),
                'error' => [
                    'code' => 'ENTITLEMENT_VALIDATION_FAILED',
                    'pack' => $pack,
                    'source_error' => $error->errorCode,
                    'message' => 'Entitlement validation failed.',
                ],
            ];
        }

        $required = (bool) ($decision['required'] ?? false);
        $status = (string) ($decision['status'] ?? 'unknown');
        $tier = (string) ($decision['tier'] ?? 'unknown');
        $normalizedStatus = $required ? $status : 'not_required';

        return [
            'requirement' => [
                'pack' => $pack,
                'source' => 'marketplace',
                'distribution' => $tier,
                'entitlement' => [
                    'required' => $required,
                    'status' => $normalizedStatus,
                    'tier' => $tier,
                    'expires_at' => is_string($decision['expires_at'] ?? null) ? $decision['expires_at'] : null,
                ],
                'executable' => !$required || $status === 'granted',
            ],
            'error' => null,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function unknownRequirement(string $pack, string $code): array
    {
        return [
            'pack' => $pack,
            'source' => 'marketplace',
            'distribution' => 'unknown',
            'entitlement' => [
                'required' => true,
                'status' => 'unknown',
                'tier' => 'unknown',
            ],
            'executable' => false,
            'code' => $code,
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $requirements
     * @return array{
     *   status:string,
     *   required:array<int,string>,
     *   granted:array<int,string>,
     *   missing:array<int,string>,
     *   expired:array<int,string>,
     *   unknown:array<int,string>
     * }
     */
    private function entitlementSummary(array $requirements): array
    {
        $required = [];
        $granted = [];
        $missing = [];
        $expired = [];
        $unknown = [];

        foreach ($requirements as $requirement) {
            $pack = (string) ($requirement['pack'] ?? '');
            $entitlement = is_array($requirement['entitlement'] ?? null) ? $requirement['entitlement'] : [];
            $isRequired = ($entitlement['required'] ?? false) === true;
            $status = (string) ($entitlement['status'] ?? 'unknown');

            if (!$isRequired || $pack === '') {
                continue;
            }

            $required[] = $pack;

            if ($status === 'granted') {
                $granted[] = $pack;
                continue;
            }

            if ($status === 'missing') {
                $missing[] = $pack;
                continue;
            }

            if ($status === 'expired') {
                $expired[] = $pack;
                continue;
            }

            $unknown[] = $pack;
        }

        $required = $this->sortedUnique($required);
        $granted = $this->sortedUnique($granted);
        $missing = $this->sortedUnique($missing);
        $expired = $this->sortedUnique($expired);
        $unknown = $this->sortedUnique($unknown);

        return [
            'status' => ($missing === [] && $expired === [] && $unknown === []) ? 'complete' : 'incomplete',
            'required' => $required,
            'granted' => $granted,
            'missing' => $missing,
            'expired' => $expired,
            'unknown' => $unknown,
        ];
    }

    /**
     * @param array<string,mixed> $entitlements
     * @param array<int,array<string,mixed>> $errors
     */
    private function executionState(array $entitlements, array $errors): string
    {
        if ($errors !== []) {
            return 'invalid';
        }

        $missing = array_values(array_map('strval', (array) ($entitlements['missing'] ?? [])));
        if ($missing !== []) {
            return 'blocked_missing_entitlement';
        }

        $expired = array_values(array_map('strval', (array) ($entitlements['expired'] ?? [])));
        if ($expired !== []) {
            return 'blocked_expired_entitlement';
        }

        $unknown = array_values(array_map('strval', (array) ($entitlements['unknown'] ?? [])));
        if ($unknown !== []) {
            return 'invalid';
        }

        return 'executable';
    }

    /**
     * @param array<int,string> $values
     * @return array<int,string>
     */
    private function sortedUnique(array $values): array
    {
        $values = array_values(array_unique(array_map('strval', $values)));
        sort($values);

        return $values;
    }
}
