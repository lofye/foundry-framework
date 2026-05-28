<?php

declare(strict_types=1);

namespace Foundry\Generate;

use Foundry\Compiler\Extensions\PackRegistry;
use Foundry\Marketplace\PackEntitlementResolver;
use Foundry\Packs\HostedPackRegistry;
use Foundry\Support\FoundryError;

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
                $local = $packs->get($pack);
                $packRequirements[] = [
                    'pack' => $pack,
                    'source' => 'local',
                    'version' => $local?->version,
                    'distribution' => 'local',
                    'entitlement_required' => false,
                    'entitlement' => [
                        'required' => false,
                        'status' => 'not_required',
                        'tier' => 'local',
                        'expires_at' => null,
                    ],
                    'executable' => true,
                    'message' => 'Pack is already installed locally.',
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
                'requirement' => $this->invalidRequirement($pack),
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
                'version' => is_string($metadata['version'] ?? null) ? trim((string) $metadata['version']) : null,
                'distribution' => $tier,
                'entitlement_required' => $required,
                'entitlement' => [
                    'required' => $required,
                    'status' => $normalizedStatus,
                    'tier' => $tier,
                    'expires_at' => is_string($decision['expires_at'] ?? null) ? $decision['expires_at'] : null,
                ],
                'executable' => !$required || $status === 'granted',
                'message' => $this->requirementMessage($normalizedStatus),
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
            'version' => null,
            'distribution' => 'unknown',
            'entitlement_required' => true,
            'entitlement' => [
                'required' => true,
                'status' => 'unknown',
                'tier' => 'unknown',
                'expires_at' => null,
            ],
            'executable' => false,
            'message' => 'Marketplace pack is not available.',
            'code' => $code,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function invalidRequirement(string $pack): array
    {
        return [
            'pack' => $pack,
            'source' => 'marketplace',
            'version' => null,
            'distribution' => 'unknown',
            'entitlement_required' => true,
            'entitlement' => [
                'required' => true,
                'status' => 'invalid',
                'tier' => 'unknown',
                'expires_at' => null,
            ],
            'executable' => false,
            'message' => 'Marketplace entitlement metadata is invalid.',
            'code' => 'ENTITLEMENT_VALIDATION_FAILED',
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
     *   unknown:array<int,string>,
     *   invalid:array<int,string>
     * }
     */
    private function entitlementSummary(array $requirements): array
    {
        $required = [];
        $granted = [];
        $missing = [];
        $expired = [];
        $unknown = [];
        $invalid = [];

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

            if ($status === 'invalid') {
                $invalid[] = $pack;
                continue;
            }

            $unknown[] = $pack;
        }

        $required = $this->sortedUnique($required);
        $granted = $this->sortedUnique($granted);
        $missing = $this->sortedUnique($missing);
        $expired = $this->sortedUnique($expired);
        $unknown = $this->sortedUnique($unknown);
        $invalid = $this->sortedUnique($invalid);

        $status = 'not_required';
        if ($required !== []) {
            if ($invalid !== []) {
                $status = 'invalid';
            } elseif ($unknown !== []) {
                $status = 'unknown';
            } elseif ($missing !== [] || $expired !== []) {
                $status = 'incomplete';
            } else {
                $status = 'complete';
            }
        }

        return [
            'status' => $status,
            'required' => $required,
            'granted' => $granted,
            'missing' => $missing,
            'expired' => $expired,
            'unknown' => $unknown,
            'invalid' => $invalid,
        ];
    }

    /**
     * @param array<string,mixed> $entitlements
     * @param array<int,array<string,mixed>> $errors
     */
    private function executionState(array $entitlements, array $errors): string
    {
        if ((string) ($entitlements['status'] ?? '') === 'invalid'
            || array_values(array_map('strval', (array) ($entitlements['invalid'] ?? []))) !== []) {
            return 'invalid';
        }

        if ($this->errorHasCode($errors, 'MARKETPLACE_PACK_NOT_AVAILABLE')) {
            return 'blocked_pack_unavailable';
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
            return 'blocked_unknown_entitlement';
        }

        if ($errors !== []) {
            return 'invalid';
        }

        return 'executable';
    }

    /**
     * @param array<int,array<string,mixed>> $errors
     */
    private function errorHasCode(array $errors, string $code): bool
    {
        foreach ($errors as $error) {
            if ((string) ($error['code'] ?? '') === $code) {
                return true;
            }
        }

        return false;
    }

    private function requirementMessage(string $status): string
    {
        return match ($status) {
            'not_required' => 'Marketplace entitlement is not required.',
            'granted' => 'Marketplace entitlement is granted.',
            'missing' => 'Marketplace entitlement is missing.',
            'expired' => 'Marketplace entitlement is expired.',
            'invalid' => 'Marketplace entitlement metadata is invalid.',
            default => 'Marketplace entitlement state is unknown.',
        };
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
