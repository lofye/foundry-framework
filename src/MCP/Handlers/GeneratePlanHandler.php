<?php

declare(strict_types=1);

namespace Foundry\MCP\Handlers;

use Foundry\MCP\CliReadBridge;
use Foundry\MCP\ToolHandler;
use Foundry\Support\FoundryError;

final class GeneratePlanHandler implements ToolHandler
{
    public function __construct(private readonly CliReadBridge $bridge) {}

    public function handle(array $input): array
    {
        $intent = trim((string) ($input['intent'] ?? ''));
        if ($intent === '') {
            throw new FoundryError('MCP_INPUT_INVALID', 'validation', ['tool' => 'generate_plan', 'field' => 'intent'], 'Input `intent` is required.');
        }

        $mode = trim((string) ($input['mode'] ?? 'new'));
        if (!in_array($mode, ['new', 'modify', 'repair'], true)) {
            throw new FoundryError('MCP_INPUT_INVALID', 'validation', ['tool' => 'generate_plan', 'field' => 'mode', 'mode' => $mode], 'Input `mode` must be new, modify, or repair.');
        }

        $allowPackInstall = $this->booleanInput($input, 'allow_pack_install', false);
        $this->booleanInput($input, 'allow_premium_packs', false);

        $args = ['generate', $intent, '--mode=' . $mode, '--dry-run'];
        if ($allowPackInstall) {
            $args[] = '--allow-pack-install';
        }

        $target = trim((string) ($input['target'] ?? ''));
        if ($target !== '') {
            $args[] = '--target=' . $target;
        }

        $packs = $this->packsFromInput($input['packs'] ?? null);
        if ($packs !== []) {
            $args[] = '--packs=' . implode(',', $packs);
        }

        try {
            $payload = $this->bridge->run($args);
        } catch (FoundryError $error) {
            return $this->blockedPayloadFromError($error);
        }

        $entitlements = $this->normalizeEntitlements($payload['entitlements'] ?? []);
        $packRequirements = $this->normalizePackRequirements($payload['pack_requirements'] ?? []);
        $executionState = $this->normalizeExecutionState(
            (string) ($payload['execution_state'] ?? 'executable'),
            $entitlements,
            $packRequirements,
            null,
        );
        $status = $executionState === 'executable' ? 'planned' : 'blocked';

        return [
            'status' => $status,
            'plan_id' => $payload['plan_record']['plan_id'] ?? null,
            'plan_record_path' => $payload['plan_record']['storage_path'] ?? null,
            'execution_state' => $executionState,
            'validation' => [
                'status' => $status === 'planned' ? 'valid' : 'blocked',
                'errors' => [],
                'warnings' => [],
            ],
            'entitlements' => $entitlements,
            'pack_requirements' => $packRequirements,
            'plan' => is_array($payload['plan'] ?? null) ? $payload['plan'] : [],
            'error' => is_array($payload['error'] ?? null) ? $payload['error'] : null,
        ];
    }

    private function booleanInput(array $input, string $field, bool $default): bool
    {
        if (!array_key_exists($field, $input)) {
            return $default;
        }

        if (!is_bool($input[$field])) {
            throw new FoundryError(
                'MCP_INPUT_INVALID',
                'validation',
                ['tool' => 'generate_plan', 'field' => $field],
                'Input `' . $field . '` must be a boolean.',
            );
        }

        return $input[$field];
    }

    /**
     * @return array<string,mixed>
     */
    private function blockedPayloadFromError(FoundryError $error): array
    {
        $details = is_array($error->details ?? null) ? $error->details : [];
        $cliPayload = is_array($details['payload'] ?? null) ? $details['payload'] : [];
        $cliError = is_array($cliPayload['error'] ?? null) ? $cliPayload['error'] : [];
        $errorDetails = is_array($cliError['details'] ?? null) ? $cliError['details'] : [];
        $code = (string) $error->errorCode;

        $entitlements = $this->normalizeEntitlements(
            $errorDetails['entitlements']
                ?? $details['entitlements']
                ?? [],
        );
        $packRequirements = $this->normalizePackRequirements(
            $errorDetails['pack_requirements']
                ?? $details['pack_requirements']
                ?? [],
        );
        $rawState = (string) (
            $errorDetails['execution_state']
            ?? $details['execution_state']
            ?? 'invalid'
        );
        $executionState = $this->normalizeExecutionState($rawState, $entitlements, $packRequirements, $code);

        return [
            'status' => 'blocked',
            'plan_id' => null,
            'plan_record_path' => null,
            'execution_state' => $executionState,
            'validation' => [
                'status' => $executionState === 'invalid' ? 'invalid' : 'blocked',
                'errors' => [[
                    'code' => $code,
                    'message' => $error->getMessage(),
                    'details' => $errorDetails !== [] ? $errorDetails : $details,
                ]],
                'warnings' => [],
            ],
            'entitlements' => $entitlements,
            'pack_requirements' => $packRequirements,
            'plan' => [],
            'error' => [
                'code' => $code,
                'message' => $error->getMessage(),
                'details' => $errorDetails !== [] ? $errorDetails : $details,
            ],
        ];
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

        if ($state === 'blocked_conflict') {
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
