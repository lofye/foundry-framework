<?php

declare(strict_types=1);

namespace Foundry\Explain;

use Foundry\Generate\GenerateEngine;
use Foundry\Generate\PlanRecordStore;
use Foundry\Support\ApiSurfaceRegistry;
use Foundry\Support\FoundryError;
use Foundry\Support\Paths;

final class PlanExplanationService
{
    /**
     * @param null|\Closure(string):array<string,mixed> $replay
     */
    public function __construct(
        private readonly Paths $paths,
        private readonly ?ApiSurfaceRegistry $apiSurfaceRegistry = null,
        private readonly ?\Closure $replay = null,
    ) {}

    /**
     * @return array<string,mixed>
     */
    public function explain(string $planId): array
    {
        $planId = trim($planId);
        if ($planId === '') {
            throw new FoundryError(
                'PLAN_EXPLAIN_ID_REQUIRED',
                'validation',
                [],
                'Plan id required.',
            );
        }

        try {
            $record = (new PlanRecordStore($this->paths))->load($planId);
        } catch (FoundryError $error) {
            return $this->failure($planId, 'invalid', $error);
        }

        if (!is_array($record)) {
            return $this->failure($planId, 'missing', new FoundryError(
                'PLAN_RECORD_NOT_FOUND',
                'not_found',
                ['plan_id' => $planId],
                'Persisted plan record not found.',
            ));
        }

        $replay = null;
        $replayError = null;
        try {
            $replay = $this->runReplay($planId);
        } catch (FoundryError $error) {
            $replayError = $error;
        }

        $plan = $this->selectedPlan($record);
        $errorDetails = $replayError instanceof FoundryError ? $this->errorDetails($replayError) : [];
        $entitlements = $this->normalizeEntitlements(
            $replay['entitlements']
                ?? $errorDetails['current_entitlements']
                ?? $errorDetails['entitlements']
                ?? $record['generation_context_packet']['entitlements']
                ?? $record['metadata']['entitlements']
                ?? [],
        );
        $packRequirements = $this->normalizePackRequirements(
            $replay['pack_requirements']
                ?? $errorDetails['current_pack_requirements']
                ?? $errorDetails['pack_requirements']
                ?? $record['generation_context_packet']['pack_requirements']
                ?? $plan['metadata']['pack_requirements']
                ?? [],
        );
        $executionState = $this->normalizeExecutionState(
            (string) (
                $replay['execution_state']
                ?? $errorDetails['current_execution_state']
                ?? $errorDetails['execution_state']
                ?? $record['metadata']['execution_state']
                ?? 'unknown'
            ),
            $entitlements,
            $packRequirements,
            $replayError instanceof FoundryError ? $replayError->errorCode : null,
        );
        $validation = $this->validation($executionState, $replayError);
        $readiness = $this->readiness($executionState, $validation, $packRequirements, $record, $planId);
        $status = $this->explainStatus($record, $plan, $replayError);

        return [
            'plan_id' => $planId,
            'status' => $status,
            'intent' => (string) ($record['intent'] ?? ''),
            'mode' => (string) ($record['mode'] ?? ''),
            'execution_state' => $executionState,
            'readiness' => $readiness,
            'packs' => $this->packs($packRequirements, $record),
            'changes' => $this->changes($plan),
            'validation' => $validation,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function runReplay(string $planId): array
    {
        if ($this->replay instanceof \Closure) {
            return ($this->replay)($planId);
        }

        return (new GenerateEngine(
            $this->paths,
            apiSurfaceRegistry: $this->apiSurfaceRegistry,
        ))->replay($planId, strict: true, dryRun: true);
    }

    /**
     * @param array<string,mixed> $record
     * @return array<string,mixed>
     */
    private function selectedPlan(array $record): array
    {
        if (is_array($record['plan_final'] ?? null)) {
            return $record['plan_final'];
        }

        if (is_array($record['plan_original'] ?? null)) {
            return $record['plan_original'];
        }

        return [];
    }

    /**
     * @return array<string,mixed>
     */
    private function failure(string $planId, string $status, FoundryError $error): array
    {
        $validation = [
            'status' => 'invalid',
            'errors' => [$this->validationError($error)],
            'warnings' => [],
        ];

        return [
            'plan_id' => $planId,
            'status' => $status,
            'intent' => '',
            'mode' => '',
            'execution_state' => 'invalid',
            'readiness' => [
                'status' => 'invalid',
                'reasons' => [[
                    'code' => (string) $error->errorCode,
                    'message' => $error->getMessage(),
                    'pack' => null,
                ]],
                'next_actions' => [[
                    'type' => 'regenerate_plan',
                    'plan_id' => $planId,
                ]],
            ],
            'packs' => [],
            'changes' => [],
            'validation' => $validation,
        ];
    }

    /**
     * @param array<string,mixed> $record
     * @param array<string,mixed> $plan
     */
    private function explainStatus(array $record, array $plan, ?FoundryError $error): string
    {
        if ($plan === [] && $error instanceof FoundryError) {
            return 'invalid';
        }

        if ($plan === [] && !is_array($record['plan_final'] ?? null) && !is_array($record['plan_original'] ?? null)) {
            return 'invalid';
        }

        return 'explainable';
    }

    /**
     * @param array<int,array<string,mixed>> $packRequirements
     * @param array<string,mixed> $record
     * @return array<int,array<string,mixed>>
     */
    private function packs(array $packRequirements, array $record): array
    {
        $explicit = $this->explicitPackHints($record);
        $rows = [];

        foreach ($packRequirements as $row) {
            $name = (string) ($row['pack'] ?? '');
            if ($name === '') {
                continue;
            }

            $reason = trim((string) ($row['reason'] ?? ''));
            if ($reason === '') {
                $reason = in_array($name, $explicit, true)
                    ? 'Pack was requested explicitly.'
                    : 'Pack requirement was inferred by Generate.';
            }

            $entitlement = is_array($row['entitlement'] ?? null) ? $row['entitlement'] : [];
            $rows[] = [
                'name' => $name,
                'source' => (string) ($row['source'] ?? 'unknown'),
                'version' => $row['version'] ?? null,
                'distribution' => (string) ($row['distribution'] ?? 'unknown'),
                'reason' => $reason,
                'entitlement' => [
                    'required' => (bool) ($entitlement['required'] ?? false),
                    'status' => (string) ($entitlement['status'] ?? 'unknown'),
                    'tier' => (string) ($entitlement['tier'] ?? 'unknown'),
                    'expires_at' => is_string($entitlement['expires_at'] ?? null) ? $entitlement['expires_at'] : null,
                ],
                'executable' => (bool) ($row['executable'] ?? false),
            ];
        }

        usort($rows, static fn(array $left, array $right): int => strcmp((string) $left['name'], (string) $right['name']));

        return $rows;
    }

    /**
     * @param array<string,mixed> $record
     * @return array<int,string>
     */
    private function explicitPackHints(array $record): array
    {
        $intent = is_array($record['metadata']['requested_intent'] ?? null)
            ? $record['metadata']['requested_intent']
            : [];
        $hints = $this->sortedStrings($intent['pack_hints'] ?? []);
        $context = is_array($record['generation_context_packet'] ?? null)
            ? $record['generation_context_packet']
            : [];

        foreach ($this->sortedStrings($context['pack_hints'] ?? []) as $pack) {
            $hints[] = $pack;
        }

        $hints = array_values(array_unique($hints));
        sort($hints);

        return $hints;
    }

    /**
     * @param array<string,mixed> $plan
     * @return array<int,array<string,mixed>>
     */
    private function changes(array $plan): array
    {
        $rows = [];
        foreach (array_values(array_filter((array) ($plan['actions'] ?? []), 'is_array')) as $action) {
            $path = trim((string) ($action['path'] ?? ''));
            if ($path === '') {
                continue;
            }

            $rows[] = [
                'path' => $path,
                'action' => trim((string) ($action['type'] ?? '')),
            ];
        }

        usort($rows, static fn(array $left, array $right): int => [
            (string) ($left['path'] ?? ''),
            (string) ($left['action'] ?? ''),
        ] <=> [
            (string) ($right['path'] ?? ''),
            (string) ($right['action'] ?? ''),
        ]);

        return $rows;
    }

    /**
     * @param array<string,mixed> $validation
     * @param array<int,array<string,mixed>> $packRequirements
     * @param array<string,mixed> $record
     * @return array{status:string,reasons:array<int,array<string,mixed>>,next_actions:array<int,array<string,mixed>>}
     */
    private function readiness(string $executionState, array $validation, array $packRequirements, array $record, string $planId): array
    {
        $status = match ($executionState) {
            'executable' => 'ready',
            'stale' => 'stale',
            'invalid' => 'invalid',
            'blocked_missing_entitlement',
            'blocked_expired_entitlement',
            'blocked_unknown_entitlement',
            'blocked_pack_unavailable',
            'blocked_conflict' => 'blocked',
            default => 'unknown',
        };

        $reasons = $this->readinessReasons($executionState, $validation, $packRequirements);
        $nextActions = $this->nextActions($status, $executionState, $packRequirements, $record, $planId);

        usort($reasons, static fn(array $left, array $right): int => [
            (string) ($left['code'] ?? ''),
            (string) ($left['pack'] ?? ''),
            (string) ($left['message'] ?? ''),
        ] <=> [
            (string) ($right['code'] ?? ''),
            (string) ($right['pack'] ?? ''),
            (string) ($right['message'] ?? ''),
        ]);

        usort($nextActions, static fn(array $left, array $right): int => [
            (string) ($left['type'] ?? ''),
            (string) ($left['pack'] ?? ''),
            (string) ($left['command'] ?? ''),
        ] <=> [
            (string) ($right['type'] ?? ''),
            (string) ($right['pack'] ?? ''),
            (string) ($right['command'] ?? ''),
        ]);

        return [
            'status' => $status,
            'reasons' => $reasons,
            'next_actions' => $nextActions,
        ];
    }

    /**
     * @param array<string,mixed> $validation
     * @param array<int,array<string,mixed>> $packRequirements
     * @return array<int,array<string,mixed>>
     */
    private function readinessReasons(string $executionState, array $validation, array $packRequirements): array
    {
        $reasons = [];
        foreach ($packRequirements as $row) {
            $pack = (string) ($row['pack'] ?? '');
            $status = (string) ($row['entitlement']['status'] ?? '');
            $code = (string) ($row['code'] ?? '');

            if ($executionState === 'blocked_pack_unavailable' || $code === 'MARKETPLACE_PACK_NOT_AVAILABLE') {
                $reasons[] = ['code' => 'PACK_UNAVAILABLE', 'message' => 'Marketplace pack is not available.', 'pack' => $pack];
            } elseif ($status === 'expired' || $executionState === 'blocked_expired_entitlement') {
                $reasons[] = ['code' => 'EXPIRED_ENTITLEMENT', 'message' => 'Marketplace entitlement is expired.', 'pack' => $pack];
            } elseif ($status === 'missing' || $executionState === 'blocked_missing_entitlement') {
                $reasons[] = ['code' => 'MISSING_ENTITLEMENT', 'message' => 'Marketplace entitlement is missing.', 'pack' => $pack];
            } elseif ($status === 'unknown' || $executionState === 'blocked_unknown_entitlement') {
                $reasons[] = ['code' => 'UNKNOWN_ENTITLEMENT', 'message' => 'Marketplace entitlement state is unknown.', 'pack' => $pack];
            } elseif ($status === 'invalid' || $executionState === 'invalid') {
                $reasons[] = ['code' => 'INVALID_ENTITLEMENT', 'message' => 'Marketplace entitlement metadata is invalid.', 'pack' => $pack];
            }
        }

        if ($executionState === 'stale') {
            $reasons[] = ['code' => 'PLAN_STALE', 'message' => 'Plan is stale against the current workspace state.', 'pack' => null];
        }

        if ($executionState === 'blocked_conflict') {
            $reasons[] = ['code' => 'PLAN_CONFLICT', 'message' => 'Plan conflicts with the current workspace state.', 'pack' => null];
        }

        if ($executionState === 'invalid' && $reasons === []) {
            foreach ((array) ($validation['errors'] ?? []) as $error) {
                if (!is_array($error)) {
                    continue;
                }
                $reasons[] = [
                    'code' => (string) ($error['code'] ?? 'PLAN_INVALID'),
                    'message' => (string) ($error['message'] ?? 'Plan is invalid.'),
                    'pack' => $error['pack'] ?? null,
                ];
            }
        }

        return $this->dedupeRows($reasons, ['code', 'pack', 'message']);
    }

    /**
     * @param array<int,array<string,mixed>> $packRequirements
     * @param array<string,mixed> $record
     * @return array<int,array<string,mixed>>
     */
    private function nextActions(string $status, string $executionState, array $packRequirements, array $record, string $planId): array
    {
        if ($this->completedRecord($record)) {
            return [['type' => 'none']];
        }

        if ($status === 'ready') {
            return [[
                'type' => 'apply_plan',
                'tool' => 'apply_plan',
                'plan_id' => $planId,
            ]];
        }

        if ($executionState === 'stale') {
            return [['type' => 'regenerate_plan', 'plan_id' => $planId]];
        }

        if ($executionState === 'invalid') {
            return [['type' => 'regenerate_plan', 'plan_id' => $planId]];
        }

        if ($status === 'unknown') {
            return [[
                'type' => 'validate_plan',
                'tool' => 'validate_plan',
                'plan_id' => $planId,
            ]];
        }

        $actions = [];
        foreach ($packRequirements as $row) {
            $pack = (string) ($row['pack'] ?? '');
            if ($pack === '') {
                continue;
            }
            $entitlementStatus = (string) ($row['entitlement']['status'] ?? '');
            if (in_array($entitlementStatus, ['missing', 'expired'], true)) {
                $actions[] = [
                    'type' => 'resolve_entitlement',
                    'pack' => $pack,
                    'command' => 'php bin/foundry pack purchase ' . $pack . ' --json',
                ];
            }
            if (in_array($entitlementStatus, ['unknown', 'invalid'], true) || $executionState === 'blocked_pack_unavailable') {
                $actions[] = [
                    'type' => 'inspect_marketplace',
                    'pack' => $pack,
                    'command' => 'php bin/foundry pack info ' . $pack . ' --json',
                ];
            }
        }

        if ($actions === []) {
            $actions[] = ['type' => 'validate_plan', 'tool' => 'validate_plan', 'plan_id' => $planId];
        }

        return $this->dedupeRows($actions, ['type', 'pack', 'command', 'tool', 'plan_id']);
    }

    /**
     * @param array<string,mixed> $record
     */
    private function completedRecord(array $record): bool
    {
        $status = (string) ($record['status'] ?? '');
        if (!in_array($status, ['success', 'completed', 'applied', 'replayed'], true)) {
            return false;
        }

        return array_values(array_filter((array) ($record['actions_executed'] ?? []), 'is_array')) !== [];
    }

    /**
     * @return array{status:string,errors:array<int,array<string,mixed>>,warnings:array<int,array<string,mixed>>}
     */
    private function validation(string $executionState, ?FoundryError $error): array
    {
        $status = match ($executionState) {
            'executable' => 'valid',
            'stale' => 'stale',
            'blocked_missing_entitlement',
            'blocked_expired_entitlement',
            'blocked_unknown_entitlement',
            'blocked_pack_unavailable',
            'blocked_conflict' => 'blocked',
            default => 'invalid',
        };

        return [
            'status' => $status,
            'errors' => $error instanceof FoundryError ? [$this->validationError($error)] : [],
            'warnings' => [],
        ];
    }

    /**
     * @return array{code:string,message:string,details:array<string,mixed>}
     */
    private function validationError(FoundryError $error): array
    {
        return [
            'code' => (string) $error->errorCode,
            'message' => $error->getMessage(),
            'details' => $this->sanitize(is_array($error->details ?? null) ? $error->details : []),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function errorDetails(FoundryError $error): array
    {
        $details = is_array($error->details ?? null) ? $error->details : [];
        $cliPayload = is_array($details['payload'] ?? null) ? $details['payload'] : [];
        $cliError = is_array($cliPayload['error'] ?? null) ? $cliPayload['error'] : [];
        $cliDetails = is_array($cliError['details'] ?? null) ? $cliError['details'] : [];

        return $cliDetails !== [] ? $cliDetails : $details;
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
                'reason' => is_string($raw['reason'] ?? null) ? trim((string) $raw['reason']) : null,
                'entitlement_required' => (bool) ($raw['entitlement_required'] ?? (($distribution !== 'free' && $distribution !== 'local'))),
                'entitlement' => [
                    'required' => (bool) ($entitlement['required'] ?? ($distribution !== 'free' && $distribution !== 'local')),
                    'status' => $entitlementStatus,
                    'tier' => trim((string) ($entitlement['tier'] ?? $distribution)),
                    'expires_at' => is_string($entitlement['expires_at'] ?? null) ? $entitlement['expires_at'] : null,
                ],
                'executable' => (bool) ($raw['executable'] ?? ($entitlementStatus === 'granted' || $entitlementStatus === 'not_required')),
                'code' => is_string($raw['code'] ?? null) ? (string) $raw['code'] : null,
            ];
        }

        $rows = array_values($rows);
        usort($rows, static fn(array $left, array $right): int => strcmp((string) $left['pack'], (string) $right['pack']));

        return $rows;
    }

    /**
     * @param array<string,mixed> $entitlements
     * @param array<int,array<string,mixed>> $packRequirements
     */
    private function normalizeExecutionState(string $state, array $entitlements, array $packRequirements, ?string $errorCode): string
    {
        $state = trim($state);
        if ($errorCode === 'PLAN_REPLAY_STRICT_DRIFT' || $state === 'stale') {
            return 'stale';
        }

        if (in_array($errorCode, [
            'PLAN_RECORD_NOT_FOUND',
            'PLAN_RECORD_INVALID',
            'PLAN_RECORD_DUPLICATE_ID',
            'PLAN_REPLAY_PLAN_UNAVAILABLE',
            'GENERATE_PLAN_INVALID',
        ], true)) {
            return 'invalid';
        }

        $invalid = $this->sortedStrings($entitlements['invalid'] ?? []);
        if ($invalid !== [] || (string) ($entitlements['status'] ?? '') === 'invalid'
            || in_array($errorCode, ['MARKETPLACE_DISTRIBUTION_METADATA_INVALID', 'ENTITLEMENT_VALIDATION_FAILED'], true)) {
            return 'invalid';
        }

        if ($this->packRequirementHasCode($packRequirements, 'MARKETPLACE_PACK_NOT_AVAILABLE') || $errorCode === 'MARKETPLACE_PACK_NOT_AVAILABLE') {
            return 'blocked_pack_unavailable';
        }

        if ($this->sortedStrings($entitlements['expired'] ?? []) !== []) {
            return 'blocked_expired_entitlement';
        }

        if ($this->sortedStrings($entitlements['missing'] ?? []) !== []) {
            return 'blocked_missing_entitlement';
        }

        if ($this->sortedStrings($entitlements['unknown'] ?? []) !== []) {
            return 'blocked_unknown_entitlement';
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

        return 'unknown';
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     */
    private function packRequirementHasCode(array $rows, string $code): bool
    {
        foreach ($rows as $row) {
            if ((string) ($row['code'] ?? '') === $code) {
                return true;
            }
        }

        return false;
    }

    /**
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

    /**
     * @param array<int,array<string,mixed>> $rows
     * @param array<int,string> $keys
     * @return array<int,array<string,mixed>>
     */
    private function dedupeRows(array $rows, array $keys): array
    {
        $seen = [];
        $deduped = [];
        foreach ($rows as $row) {
            $key = implode("\0", array_map(static fn(string $field): string => (string) ($row[$field] ?? ''), $keys));
            if (isset($seen[$key])) {
                continue;
            }

            $seen[$key] = true;
            $deduped[] = $row;
        }

        return $deduped;
    }

    private function sanitize(mixed $value): mixed
    {
        if (!is_array($value)) {
            return $value;
        }

        $sanitized = [];
        foreach ($value as $key => $item) {
            $keyString = strtolower((string) $key);
            if (str_contains($keyString, 'token') || str_contains($keyString, 'secret') || str_contains($keyString, 'license_key')) {
                $sanitized[$key] = '[redacted]';
                continue;
            }

            $sanitized[$key] = $this->sanitize($item);
        }

        return $sanitized;
    }
}
