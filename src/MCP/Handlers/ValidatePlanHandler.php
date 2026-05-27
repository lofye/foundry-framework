<?php

declare(strict_types=1);

namespace Foundry\MCP\Handlers;

use Foundry\Compiler\Extensions\ExtensionRegistry;
use Foundry\Generate\GenerationPlan;
use Foundry\Generate\Intent;
use Foundry\Generate\PackRequirementResolver;
use Foundry\Generate\PlanValidator;
use Foundry\Marketplace\MarketplaceEntitlementCache;
use Foundry\Marketplace\PackEntitlementResolver;
use Foundry\MCP\CliReadBridge;
use Foundry\MCP\ToolHandler;
use Foundry\Packs\PackManager;
use Foundry\Support\FoundryError;
use Foundry\Support\Paths;

final class ValidatePlanHandler implements ToolHandler
{
    public function __construct(private readonly CliReadBridge $bridge) {}

    public function handle(array $input): array
    {
        $planId = trim((string) ($input['plan_id'] ?? ''));
        $hasPlanId = $planId !== '';
        $inlinePlan = is_array($input['plan'] ?? null) ? $input['plan'] : null;
        $hasInlinePlan = is_array($inlinePlan);

        if (($hasPlanId ? 1 : 0) + ($hasInlinePlan ? 1 : 0) !== 1) {
            throw new FoundryError(
                'MCP_INPUT_INVALID',
                'validation',
                ['tool' => 'validate_plan'],
                'Exactly one of `plan_id` or `plan` is required.',
            );
        }

        if ($hasPlanId) {
            return $this->validatePlanId($planId);
        }

        return $this->validateInlinePlan($inlinePlan ?? [], $input);
    }

    /**
     * @return array<string,mixed>
     */
    private function validatePlanId(string $planId): array
    {
        try {
            $payload = $this->bridge->run(['plan:replay', $planId, '--strict', '--dry-run']);
        } catch (FoundryError $error) {
            return $this->payloadFromReplayError($planId, $error);
        }

        $entitlements = $this->normalizeEntitlements($payload['entitlements'] ?? []);
        $packRequirements = $this->normalizePackRequirements($payload['pack_requirements'] ?? []);
        $executionState = $this->normalizeExecutionState(
            (string) ($payload['execution_state'] ?? 'executable'),
            $entitlements,
            $packRequirements,
            null,
        );
        $status = $this->validationStatus($executionState);

        return [
            'status' => $status,
            'plan_id' => $planId,
            'execution_state' => $executionState,
            'validation' => [
                'status' => $status,
                'errors' => [],
                'warnings' => [],
            ],
            'entitlements' => $entitlements,
            'pack_requirements' => $packRequirements,
        ];
    }

    /**
     * @param array<string,mixed> $plan
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    private function validateInlinePlan(array $plan, array $input): array
    {
        try {
            $generationPlan = GenerationPlan::fromArray($plan);
            $intent = $this->inlineIntent($plan, $input);
            (new PlanValidator())->validate($generationPlan, $intent);
        } catch (FoundryError $error) {
            return $this->invalidInlinePayload($error);
        }

        $requirements = $this->inlineRequirements($plan, $input);
        $entitlements = $requirements['entitlements'];
        $packRequirements = $requirements['pack_requirements'];
        $executionState = $requirements['execution_state'];
        $status = $this->validationStatus($executionState);

        return [
            'status' => $status,
            'plan_id' => null,
            'execution_state' => $executionState,
            'validation' => [
                'status' => $status,
                'errors' => $requirements['errors'],
                'warnings' => [],
            ],
            'entitlements' => $entitlements,
            'pack_requirements' => $packRequirements,
        ];
    }

    /**
     * @param array<string,mixed> $plan
     * @param array<string,mixed> $input
     */
    private function inlineIntent(array $plan, array $input): Intent
    {
        $requestedIntent = is_array($plan['metadata']['requested_intent'] ?? null)
            ? $plan['metadata']['requested_intent']
            : [];
        $packHints = $this->packHints($plan, $input);
        $mode = trim((string) ($requestedIntent['mode'] ?? $input['mode'] ?? 'modify'));
        if (!in_array($mode, Intent::supportedModes(), true)) {
            $mode = 'modify';
        }

        return new Intent(
            raw: trim((string) ($requestedIntent['raw'] ?? $input['intent'] ?? 'MCP inline validation')),
            mode: $mode,
            allowRisky: true,
            packHints: $packHints,
        );
    }

    /**
     * @param array<string,mixed> $plan
     * @param array<string,mixed> $input
     * @return array{
     *   execution_state:string,
     *   entitlements:array<string,mixed>,
     *   pack_requirements:array<int,array<string,mixed>>,
     *   errors:array<int,array<string,mixed>>
     * }
     */
    private function inlineRequirements(array $plan, array $input): array
    {
        $packHints = $this->packHints($plan, $input);
        if ($packHints !== []) {
            $paths = Paths::fromCwd();
            $requirements = (new PackRequirementResolver(
                hostedRegistry: (new PackManager($paths))->hostedRegistry(),
                entitlementResolver: new PackEntitlementResolver(new MarketplaceEntitlementCache($paths)),
            ))->resolve(
                new Intent(raw: 'MCP inline plan validation', mode: 'modify', packHints: $packHints),
                ExtensionRegistry::forPaths($paths)->packRegistry(),
            );

            $entitlements = $this->normalizeEntitlements($requirements['entitlements'] ?? []);
            $packRequirements = $this->normalizePackRequirements($requirements['pack_requirements'] ?? []);
            $errors = $this->normalizeErrors($requirements['errors'] ?? []);
            $executionState = $this->normalizeExecutionState(
                (string) ($requirements['execution_state'] ?? 'invalid'),
                $entitlements,
                $packRequirements,
                null,
            );

            return [
                'execution_state' => $executionState,
                'entitlements' => $entitlements,
                'pack_requirements' => $packRequirements,
                'errors' => $errors,
            ];
        }

        $entitlements = $this->normalizeEntitlements(
            $input['entitlements']
                ?? $plan['metadata']['entitlements']
                ?? [],
        );
        $packRequirements = $this->normalizePackRequirements(
            $input['pack_requirements']
                ?? $plan['metadata']['pack_requirements']
                ?? [],
        );
        $executionState = $this->normalizeExecutionState(
            (string) ($input['execution_state'] ?? $plan['metadata']['execution_state'] ?? 'executable'),
            $entitlements,
            $packRequirements,
            null,
        );

        return [
            'execution_state' => $executionState,
            'entitlements' => $entitlements,
            'pack_requirements' => $packRequirements,
            'errors' => [],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function payloadFromReplayError(string $planId, FoundryError $error): array
    {
        $details = is_array($error->details ?? null) ? $error->details : [];
        $cliPayload = is_array($details['payload'] ?? null) ? $details['payload'] : [];
        $cliError = is_array($cliPayload['error'] ?? null) ? $cliPayload['error'] : [];
        $errorDetails = is_array($cliError['details'] ?? null) ? $cliError['details'] : [];
        $code = (string) $error->errorCode;
        $entitlements = $this->normalizeEntitlements(
            $errorDetails['current_entitlements']
                ?? $errorDetails['entitlements']
                ?? $details['current_entitlements']
                ?? $details['entitlements']
                ?? [],
        );
        $packRequirements = $this->normalizePackRequirements(
            $errorDetails['current_pack_requirements']
                ?? $errorDetails['pack_requirements']
                ?? $details['current_pack_requirements']
                ?? $details['pack_requirements']
                ?? [],
        );
        $rawState = $code === 'PLAN_REPLAY_STRICT_DRIFT'
            ? 'stale'
            : (string) (
                $errorDetails['current_execution_state']
                ?? $errorDetails['execution_state']
                ?? $details['current_execution_state']
                ?? $details['execution_state']
                ?? 'invalid'
            );
        $executionState = $this->normalizeExecutionState($rawState, $entitlements, $packRequirements, $code);
        $status = $this->statusFromErrorCode($code, $executionState);

        return [
            'status' => $status,
            'plan_id' => $planId,
            'execution_state' => $executionState,
            'validation' => [
                'status' => $status,
                'errors' => [[
                    'code' => $code,
                    'message' => $error->getMessage(),
                    'details' => $errorDetails !== [] ? $errorDetails : $details,
                ]],
                'warnings' => [],
            ],
            'entitlements' => $entitlements,
            'pack_requirements' => $packRequirements,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function invalidInlinePayload(FoundryError $error): array
    {
        $details = is_array($error->details ?? null) ? $error->details : [];

        return [
            'status' => 'invalid',
            'plan_id' => null,
            'execution_state' => 'invalid',
            'validation' => [
                'status' => 'invalid',
                'errors' => [[
                    'code' => (string) $error->errorCode,
                    'message' => $error->getMessage(),
                    'details' => $details,
                ]],
                'warnings' => [],
            ],
            'entitlements' => $this->normalizeEntitlements([]),
            'pack_requirements' => [],
        ];
    }

    /**
     * @param array<string,mixed> $plan
     * @param array<string,mixed> $input
     * @return array<int,string>
     */
    private function packHints(array $plan, array $input): array
    {
        $hints = [];

        foreach ($this->packsFromInput($input['packs'] ?? null) as $pack) {
            $hints[] = $pack;
        }
        foreach ($this->packsFromInput($plan['metadata']['pack_hints'] ?? null) as $pack) {
            $hints[] = $pack;
        }
        foreach ($this->packsFromInput($plan['metadata']['packs'] ?? null) as $pack) {
            $hints[] = $pack;
        }

        $inputEntitlements = is_array($input['entitlements'] ?? null) ? $input['entitlements'] : [];
        $planEntitlements = is_array($plan['metadata']['entitlements'] ?? null) ? $plan['metadata']['entitlements'] : [];
        foreach ((array) ($inputEntitlements['required'] ?? []) as $pack) {
            $hints[] = (string) $pack;
        }
        foreach ((array) ($planEntitlements['required'] ?? []) as $pack) {
            $hints[] = (string) $pack;
        }

        foreach (array_values(array_filter((array) ($input['pack_requirements'] ?? []), 'is_array')) as $row) {
            $hints[] = (string) ($row['pack'] ?? '');
        }
        foreach (array_values(array_filter((array) ($plan['metadata']['pack_requirements'] ?? []), 'is_array')) as $row) {
            $hints[] = (string) ($row['pack'] ?? '');
        }

        $hints = array_values(array_filter(array_map(
            static fn(string $pack): string => trim($pack),
            $hints,
        ), static fn(string $pack): bool => $pack !== ''));
        $hints = array_values(array_unique($hints));
        sort($hints);

        return $hints;
    }

    /**
     * @return array<int,string>
     */
    private function packsFromInput(mixed $value): array
    {
        if (is_string($value)) {
            $rows = array_map('trim', explode(',', $value));
        } elseif (is_array($value)) {
            $rows = array_map(static fn(mixed $pack): string => trim((string) $pack), $value);
        } else {
            return [];
        }

        $rows = array_values(array_filter($rows, static fn(string $row): bool => $row !== ''));
        $rows = array_values(array_unique($rows));
        sort($rows);

        return $rows;
    }

    /**
     * @param array<string,mixed>|mixed $value
     * @return array<string,mixed>
     */
    private function normalizeEntitlements(mixed $value): array
    {
        $entitlements = is_array($value) ? $value : [];
        $required = $this->sortedStrings($entitlements['required'] ?? []);
        $granted = $this->sortedStrings($entitlements['granted'] ?? []);
        $missing = $this->sortedStrings($entitlements['missing'] ?? []);
        $expired = $this->sortedStrings($entitlements['expired'] ?? []);
        $unknown = $this->sortedStrings($entitlements['unknown'] ?? []);
        $invalid = $this->sortedStrings($entitlements['invalid'] ?? []);

        $status = trim((string) ($entitlements['status'] ?? ''));
        if (!in_array($status, ['complete', 'incomplete', 'unknown', 'invalid', 'not_required'], true)) {
            if ($required === []) {
                $status = 'not_required';
            } elseif ($invalid !== []) {
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
     * @param mixed $value
     * @return array<int,array<string,mixed>>
     */
    private function normalizePackRequirements(mixed $value): array
    {
        $rows = [];
        foreach (array_values(array_filter((array) $value, 'is_array')) as $raw) {
            $pack = trim((string) ($raw['pack'] ?? ''));
            if ($pack === '') {
                continue;
            }
            $source = trim((string) ($raw['source'] ?? 'unknown'));
            if (!in_array($source, ['local', 'marketplace', 'unknown'], true)) {
                $source = 'unknown';
            }
            $distribution = trim((string) ($raw['distribution'] ?? ($source === 'local' ? 'local' : 'unknown')));
            if (!in_array($distribution, ['local', 'free', 'licensed', 'premium', 'unknown'], true)) {
                $distribution = 'unknown';
            }
            $entitlement = is_array($raw['entitlement'] ?? null) ? $raw['entitlement'] : [];
            $entitlementStatus = trim((string) ($entitlement['status'] ?? ($distribution === 'free' || $distribution === 'local' ? 'not_required' : 'unknown')));
            if (!in_array($entitlementStatus, ['not_required', 'granted', 'missing', 'expired', 'unknown', 'invalid'], true)) {
                $entitlementStatus = 'invalid';
            }
            $rows[$pack] = [
                'pack' => $pack,
                'source' => $source,
                'version' => is_string($raw['version'] ?? null) ? trim((string) $raw['version']) : null,
                'distribution' => $distribution,
                'entitlement_required' => (bool) ($raw['entitlement_required'] ?? (($distribution !== 'free' && $distribution !== 'local'))),
                'entitlement' => [
                    'required' => (bool) ($entitlement['required'] ?? ($distribution !== 'free' && $distribution !== 'local')),
                    'status' => $entitlementStatus,
                    'tier' => trim((string) ($entitlement['tier'] ?? $distribution)),
                    'expires_at' => is_string($entitlement['expires_at'] ?? null) ? $entitlement['expires_at'] : null,
                ],
                'executable' => (bool) ($raw['executable'] ?? ($entitlementStatus === 'granted' || $entitlementStatus === 'not_required')),
                'message' => is_string($raw['message'] ?? null) ? (string) $raw['message'] : null,
                'code' => is_string($raw['code'] ?? null) ? (string) $raw['code'] : null,
            ];
        }

        $rows = array_values($rows);
        usort($rows, static fn(array $left, array $right): int => strcmp((string) $left['pack'], (string) $right['pack']));

        return $rows;
    }

    /**
     * @param mixed $value
     * @return array<int,array<string,mixed>>
     */
    private function normalizeErrors(mixed $value): array
    {
        $rows = array_values(array_filter((array) $value, 'is_array'));
        usort($rows, static fn(array $left, array $right): int => [
            (string) ($left['code'] ?? ''),
            (string) ($left['pack'] ?? ''),
            (string) ($left['message'] ?? ''),
        ] <=> [
            (string) ($right['code'] ?? ''),
            (string) ($right['pack'] ?? ''),
            (string) ($right['message'] ?? ''),
        ]);

        return $rows;
    }

    /**
     * @param array<string,mixed> $entitlements
     * @param array<int,array<string,mixed>> $packRequirements
     */
    private function normalizeExecutionState(
        string $state,
        array $entitlements,
        array $packRequirements,
        ?string $errorCode,
    ): string {
        $state = trim($state);
        $invalid = $this->sortedStrings($entitlements['invalid'] ?? []);
        if ($invalid !== [] || (string) ($entitlements['status'] ?? '') === 'invalid' || $errorCode === 'MARKETPLACE_DISTRIBUTION_METADATA_INVALID') {
            return 'invalid';
        }

        if ($this->packRequirementHasCode($packRequirements, 'MARKETPLACE_PACK_NOT_AVAILABLE') || $errorCode === 'MARKETPLACE_PACK_NOT_AVAILABLE') {
            return 'blocked_pack_unavailable';
        }

        $expired = $this->sortedStrings($entitlements['expired'] ?? []);
        if ($expired !== []) {
            return 'blocked_expired_entitlement';
        }

        $missing = $this->sortedStrings($entitlements['missing'] ?? []);
        if ($missing !== []) {
            return 'blocked_missing_entitlement';
        }

        $unknown = $this->sortedStrings($entitlements['unknown'] ?? []);
        if ($unknown !== []) {
            return 'blocked_unknown_entitlement';
        }

        if ($errorCode === 'PLAN_REPLAY_STRICT_DRIFT' || $state === 'stale') {
            return 'stale';
        }

        if ($errorCode === 'PLAN_REPLAY_PRECONDITION_FAILED' || $state === 'blocked_conflict') {
            return 'blocked_conflict';
        }

        if (in_array($state, [
            'executable',
            'blocked_missing_entitlement',
            'blocked_expired_entitlement',
            'blocked_unknown_entitlement',
            'blocked_pack_unavailable',
            'blocked_conflict',
            'stale',
            'invalid',
        ], true)) {
            return $state;
        }

        return 'invalid';
    }

    private function validationStatus(string $executionState): string
    {
        return match ($executionState) {
            'executable' => 'valid',
            'stale' => 'stale',
            'blocked_missing_entitlement',
            'blocked_expired_entitlement',
            'blocked_unknown_entitlement',
            'blocked_pack_unavailable',
            'blocked_conflict' => 'blocked',
            default => 'invalid',
        };
    }

    private function statusFromErrorCode(string $errorCode, string $executionState): string
    {
        if ($errorCode === 'PLAN_REPLAY_STRICT_DRIFT') {
            return 'stale';
        }

        if (in_array($errorCode, [
            'PLAN_RECORD_NOT_FOUND',
            'PLAN_RECORD_INVALID',
            'PLAN_RECORD_DUPLICATE_ID',
            'PLAN_REPLAY_PLAN_UNAVAILABLE',
            'GENERATE_PLAN_INVALID',
            'PLAN_REPLAY_ID_REQUIRED',
        ], true)) {
            return 'invalid';
        }

        $validationStatus = $this->validationStatus($executionState);
        if ($validationStatus === 'blocked') {
            return 'blocked';
        }

        if ($validationStatus === 'stale') {
            return 'stale';
        }

        return 'invalid';
    }

    /**
     * @param array<int,array<string,mixed>> $packRequirements
     */
    private function packRequirementHasCode(array $packRequirements, string $code): bool
    {
        foreach ($packRequirements as $row) {
            if ((string) ($row['code'] ?? '') === $code) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param mixed $value
     * @return array<int,string>
     */
    private function sortedStrings(mixed $value): array
    {
        $rows = array_values(array_filter(array_map(
            static fn(mixed $item): string => trim((string) $item),
            (array) $value,
        ), static fn(string $row): bool => $row !== ''));
        $rows = array_values(array_unique($rows));
        sort($rows);

        return $rows;
    }
}
