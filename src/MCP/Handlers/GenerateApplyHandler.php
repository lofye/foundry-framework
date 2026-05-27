<?php

declare(strict_types=1);

namespace Foundry\MCP\Handlers;

use Foundry\MCP\CliReadBridge;
use Foundry\MCP\ToolHandler;
use Foundry\Support\FoundryError;

final class GenerateApplyHandler implements ToolHandler
{
    public function __construct(private readonly CliReadBridge $bridge) {}

    public function handle(array $input): array
    {
        $planId = trim((string) ($input['plan_id'] ?? ''));
        if ($planId === '') {
            throw new FoundryError('MCP_INPUT_INVALID', 'validation', ['tool' => 'apply_plan'], 'Input `plan_id` is required.');
        }

        if (array_key_exists('plan', $input)) {
            throw new FoundryError(
                'MCP_INPUT_INVALID',
                'validation',
                ['tool' => 'apply_plan'],
                'Inline `plan` payloads are not accepted by `apply_plan`; pass `plan_id` only.',
            );
        }

        $strict = $this->boolInput($input, 'strict', true);
        $dryRun = $this->boolInput($input, 'dry_run', false);

        $args = ['plan:replay', $planId, '--dry-run'];
        if ($strict) {
            $args[] = '--strict';
        }

        try {
            $preflight = $this->bridge->run($args);
        } catch (FoundryError $error) {
            return $this->failurePayload(
                planId: $planId,
                dryRun: $dryRun,
                error: $error,
                preflightStatus: 'failed',
                preflightExecutionState: 'invalid',
                preflightEntitlements: $this->normalizeEntitlements([]),
                preflightValidationStatus: 'invalid',
            );
        }

        $preflightEntitlements = $this->normalizeEntitlements($preflight['entitlements'] ?? []);
        $preflightExecutionState = $this->normalizeExecutionState(
            state: (string) ($preflight['execution_state'] ?? 'executable'),
            code: null,
            entitlements: $preflightEntitlements,
            packRequirements: $this->normalizePackRequirements($preflight['pack_requirements'] ?? []),
        );

        if ($dryRun) {
            return [
                'status' => 'preflight_passed',
                'plan_id' => $planId,
                'dry_run' => true,
                'execution_state' => $preflightExecutionState,
                'preflight' => [
                    'status' => 'passed',
                    'execution_state' => $preflightExecutionState,
                    'entitlements' => $preflightEntitlements,
                    'validation' => [
                        'status' => 'valid',
                        'errors' => [],
                        'warnings' => [],
                    ],
                ],
                'result' => null,
                'error' => null,
            ];
        }

        $applyArgs = ['plan:replay', $planId];
        if ($strict) {
            $applyArgs[] = '--strict';
        }

        try {
            $applied = $this->bridge->run($applyArgs);
        } catch (FoundryError $error) {
            return $this->failurePayload(
                planId: $planId,
                dryRun: false,
                error: $error,
                preflightStatus: 'passed',
                preflightExecutionState: $preflightExecutionState,
                preflightEntitlements: $preflightEntitlements,
                preflightValidationStatus: 'valid',
            );
        }

        $executionState = $this->normalizeExecutionState(
            state: (string) ($applied['execution_state'] ?? $preflightExecutionState),
            code: null,
            entitlements: $this->normalizeEntitlements($applied['entitlements'] ?? $preflightEntitlements),
            packRequirements: $this->normalizePackRequirements($applied['pack_requirements'] ?? []),
        );

        return [
            'status' => 'applied',
            'plan_id' => $planId,
            'dry_run' => false,
            'execution_state' => $executionState,
            'preflight' => [
                'status' => 'passed',
                'execution_state' => $preflightExecutionState,
                'entitlements' => $preflightEntitlements,
                'validation' => [
                    'status' => 'valid',
                    'errors' => [],
                    'warnings' => [],
                ],
            ],
            'result' => $applied,
            'error' => null,
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function failurePayload(
        string $planId,
        bool $dryRun,
        FoundryError $error,
        string $preflightStatus,
        string $preflightExecutionState,
        array $preflightEntitlements,
        string $preflightValidationStatus,
    ): array {
        $failure = $this->errorPayload($error);
        $executionState = $this->normalizeExecutionState(
            state: (string) ($failure['execution_state'] ?? 'invalid'),
            code: (string) $failure['source_code'],
            entitlements: is_array($failure['entitlements'] ?? null) ? $failure['entitlements'] : [],
            packRequirements: is_array($failure['pack_requirements'] ?? null) ? $failure['pack_requirements'] : [],
        );
        $status = $this->statusForErrorCode((string) $failure['source_code'], $executionState);
        $validationStatus = $status === 'invalid' ? 'invalid' : 'blocked';

        return [
            'status' => $status,
            'plan_id' => $planId,
            'dry_run' => $dryRun,
            'execution_state' => $executionState,
            'preflight' => [
                'status' => $preflightStatus,
                'execution_state' => $preflightExecutionState,
                'entitlements' => $preflightEntitlements,
                'validation' => [
                    'status' => $preflightValidationStatus,
                    'errors' => [],
                    'warnings' => [],
                ],
            ],
            'result' => null,
            'error' => [
                'code' => $failure['code'],
                'pack' => $failure['pack'],
                'message' => $failure['message'],
                'details' => $failure['details'],
            ],
            'validation' => [
                'status' => $validationStatus,
                'errors' => [[
                    'code' => $failure['code'],
                    'message' => $failure['message'],
                    'details' => $failure['details'],
                ]],
                'warnings' => [],
            ],
        ];
    }

    private function boolInput(array $input, string $key, bool $default): bool
    {
        if (!array_key_exists($key, $input)) {
            return $default;
        }

        $value = $input[$key];
        if (!is_bool($value)) {
            throw new FoundryError(
                'MCP_INPUT_INVALID',
                'validation',
                ['tool' => 'apply_plan', 'field' => $key],
                sprintf('Input `%s` must be a boolean value.', $key),
            );
        }

        return $value;
    }

    /**
     * @return array{
     *   source_code:string,
     *   code:string,
     *   pack:?string,
     *   message:string,
     *   details:array<string,mixed>,
     *   entitlements:array<string,mixed>,
     *   pack_requirements:array<int,array<string,mixed>>,
     *   execution_state:string
     * }
     */
    private function errorPayload(FoundryError $error): array
    {
        $details = is_array($error->details ?? null) ? $error->details : [];
        $cliPayload = is_array($details['payload'] ?? null) ? $details['payload'] : [];
        $cliError = is_array($cliPayload['error'] ?? null) ? $cliPayload['error'] : [];
        $errorDetails = is_array($cliError['details'] ?? null) ? $cliError['details'] : [];
        $sourceCode = trim((string) $error->errorCode);
        if ($sourceCode === '') {
            $sourceCode = 'MCP_TOOL_CLI_FAILED';
        }
        $mappedCode = $this->mapErrorCode($sourceCode);
        $pack = null;
        if (is_string($errorDetails['pack'] ?? null) && trim((string) $errorDetails['pack']) !== '') {
            $pack = trim((string) $errorDetails['pack']);
        } elseif (is_string($details['pack'] ?? null) && trim((string) $details['pack']) !== '') {
            $pack = trim((string) $details['pack']);
        }
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
        $executionState = trim((string) (
            $errorDetails['current_execution_state']
            ?? $errorDetails['execution_state']
            ?? $details['current_execution_state']
            ?? $details['execution_state']
            ?? 'invalid'
        ));

        return [
            'source_code' => $sourceCode,
            'code' => $mappedCode,
            'pack' => $pack,
            'message' => $error->getMessage() !== '' ? $error->getMessage() : 'Plan apply is blocked by validation.',
            'details' => $errorDetails !== [] ? $errorDetails : $details,
            'entitlements' => $entitlements,
            'pack_requirements' => $packRequirements,
            'execution_state' => $executionState,
        ];
    }

    private function mapErrorCode(string $code): string
    {
        return match ($code) {
            'PLAN_REPLAY_STRICT_DRIFT' => 'PLAN_STALE',
            'PLAN_REPLAY_PRECONDITION_FAILED' => 'PLAN_CONFLICT',
            'PLAN_REPLAY_VERIFICATION_FAILED' => 'VERIFY_FAILED',
            'GENERATE_POLICY_VIOLATION' => 'POLICY_VIOLATION',
            default => $code,
        };
    }

    private function statusForErrorCode(string $sourceCode, string $executionState): string
    {
        if (in_array($sourceCode, [
            'PLAN_RECORD_NOT_FOUND',
            'PLAN_RECORD_INVALID',
            'PLAN_INTEGRITY_INVALID',
            'PLAN_REPLAY_PLAN_UNAVAILABLE',
            'PLAN_REPLAY_ID_REQUIRED',
            'GENERATE_PLAN_INVALID',
        ], true)) {
            return 'invalid';
        }

        if ($executionState === 'invalid') {
            return 'invalid';
        }

        return 'blocked';
    }

    /**
     * @param array<string,mixed> $entitlements
     * @param array<int,array<string,mixed>> $packRequirements
     */
    private function normalizeExecutionState(
        string $state,
        ?string $code,
        array $entitlements,
        array $packRequirements,
    ): string {
        $state = trim($state);
        $missing = $this->sortedStrings($entitlements['missing'] ?? []);
        if ($missing !== [] || in_array($code, ['MISSING_ENTITLEMENT'], true)) {
            return 'blocked_missing_entitlement';
        }

        $expired = $this->sortedStrings($entitlements['expired'] ?? []);
        if ($expired !== [] || in_array($code, ['EXPIRED_ENTITLEMENT'], true)) {
            return 'blocked_expired_entitlement';
        }

        if ($this->packRequirementHasCode($packRequirements, 'MARKETPLACE_PACK_NOT_AVAILABLE')
            || in_array($code, ['MARKETPLACE_PACK_NOT_AVAILABLE'], true)) {
            return 'blocked_pack_unavailable';
        }

        $unknown = $this->sortedStrings($entitlements['unknown'] ?? []);
        if ($unknown !== [] || in_array($code, ['UNKNOWN_ENTITLEMENT'], true)) {
            return 'blocked_unknown_entitlement';
        }

        if (in_array($code, ['PLAN_REPLAY_STRICT_DRIFT', 'PLAN_STALE'], true) || $state === 'stale') {
            return 'stale';
        }

        if (in_array($code, ['PLAN_REPLAY_PRECONDITION_FAILED', 'PLAN_CONFLICT', 'DEPENDENCY_CONFLICT'], true) || $state === 'blocked_conflict') {
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

        $status = trim((string) ($entitlements['status'] ?? ''));
        if ($status === '') {
            if ($required === []) {
                $status = 'not_required';
            } elseif ($missing === [] && $expired === [] && $unknown === []) {
                $status = 'complete';
            } else {
                $status = 'incomplete';
            }
        }

        return [
            'status' => $status,
            'required' => $required,
            'granted' => $granted,
            'missing' => $missing,
            'expired' => $expired,
            'unknown' => $unknown,
        ];
    }

    /**
     * @param mixed $value
     * @return array<int,array<string,mixed>>
     */
    private function normalizePackRequirements(mixed $value): array
    {
        $rows = array_values(array_filter((array) $value, 'is_array'));
        $rows = array_map(static function (array $row): array {
            if (isset($row['pack'])) {
                $row['pack'] = trim((string) $row['pack']);
            }

            return $row;
        }, $rows);

        usort($rows, static fn(array $left, array $right): int => [
            (string) ($left['pack'] ?? ''),
            (string) ($left['code'] ?? ''),
            (string) ($left['source'] ?? ''),
        ] <=> [
            (string) ($right['pack'] ?? ''),
            (string) ($right['code'] ?? ''),
            (string) ($right['source'] ?? ''),
        ]);

        return array_values($rows);
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     */
    private function packRequirementHasCode(array $rows, string $code): bool
    {
        foreach ($rows as $row) {
            if (trim((string) ($row['code'] ?? '')) === $code) {
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
        $rows = array_values(array_map(static fn(mixed $item): string => trim((string) $item), (array) $value));
        $rows = array_values(array_filter($rows, static fn(string $item): bool => $item !== ''));
        $rows = array_values(array_unique($rows));
        sort($rows);

        return $rows;
    }
}
