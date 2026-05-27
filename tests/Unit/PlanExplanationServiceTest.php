<?php

declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\Explain\PlanExplanationService;
use Foundry\Generate\PlanRecordStore;
use Foundry\Support\FoundryError;
use Foundry\Support\Paths;
use Foundry\Tests\Fixtures\TempProject;
use PHPUnit\Framework\TestCase;

final class PlanExplanationServiceTest extends TestCase
{
    private TempProject $project;

    protected function setUp(): void
    {
        $this->project = new TempProject();
    }

    protected function tearDown(): void
    {
        $this->project->cleanup();
    }

    public function test_explains_executable_plan_with_apply_next_action(): void
    {
        $this->persistRecord('plan-ready', ['foundry/blog', 'foundry/auth']);

        $explanation = $this->service(static fn(string $planId): array => [
            'execution_state' => 'executable',
            'entitlements' => ['status' => 'complete'],
            'pack_requirements' => [
                ['pack' => 'foundry/blog', 'source' => 'marketplace', 'distribution' => 'free'],
                ['pack' => 'foundry/auth', 'source' => 'marketplace', 'distribution' => 'free'],
            ],
        ])->explain('plan-ready');

        $this->assertSame('explainable', $explanation['status']);
        $this->assertSame('ready', $explanation['readiness']['status']);
        $this->assertSame('apply_plan', $explanation['readiness']['next_actions'][0]['type']);
        $this->assertSame(['foundry/auth', 'foundry/blog'], array_column($explanation['packs'], 'name'));
        $this->assertSame([
            ['path' => 'app/features/comments/feature.yaml', 'action' => 'write_file'],
            ['path' => 'app/features/comments/routes.yaml', 'action' => 'write_file'],
        ], $explanation['changes']);
    }

    public function test_explains_missing_entitlement_with_resolve_action(): void
    {
        $this->persistRecord('plan-missing', ['foundry/auth']);

        $explanation = $this->service(static fn(string $planId): array => [
            'execution_state' => 'blocked_missing_entitlement',
            'entitlements' => [
                'status' => 'incomplete',
                'required' => ['foundry/auth'],
                'missing' => ['foundry/auth'],
            ],
            'pack_requirements' => [[
                'pack' => 'foundry/auth',
                'source' => 'marketplace',
                'version' => '1.0.0',
                'distribution' => 'premium',
                'entitlement_required' => true,
                'entitlement' => ['required' => true, 'status' => 'missing', 'tier' => 'premium'],
                'executable' => false,
            ]],
        ])->explain('plan-missing');

        $this->assertSame('blocked_missing_entitlement', $explanation['execution_state']);
        $this->assertSame('blocked', $explanation['readiness']['status']);
        $this->assertSame('MISSING_ENTITLEMENT', $explanation['readiness']['reasons'][0]['code']);
        $this->assertSame('resolve_entitlement', $explanation['readiness']['next_actions'][0]['type']);
        $this->assertSame('php bin/foundry pack purchase foundry/auth --json', $explanation['readiness']['next_actions'][0]['command']);
    }

    public function test_explains_expired_entitlement_deterministically(): void
    {
        $this->persistRecord('plan-expired', ['foundry/auth']);

        $explanation = $this->service(static fn(string $planId): array => [
            'execution_state' => 'blocked_expired_entitlement',
            'entitlements' => [
                'status' => 'incomplete',
                'required' => ['foundry/auth'],
                'expired' => ['foundry/auth'],
            ],
            'pack_requirements' => [[
                'pack' => 'foundry/auth',
                'source' => 'marketplace',
                'distribution' => 'premium',
                'entitlement' => ['required' => true, 'status' => 'expired', 'tier' => 'premium', 'expires_at' => '2026-01-01T00:00:00Z'],
            ]],
        ])->explain('plan-expired');

        $this->assertSame('blocked_expired_entitlement', $explanation['execution_state']);
        $this->assertSame('EXPIRED_ENTITLEMENT', $explanation['readiness']['reasons'][0]['code']);
        $this->assertSame('resolve_entitlement', $explanation['readiness']['next_actions'][0]['type']);
        $this->assertSame('2026-01-01T00:00:00Z', $explanation['packs'][0]['entitlement']['expires_at']);
    }

    public function test_explains_stale_plan_with_regenerate_next_action(): void
    {
        $this->persistRecord('plan-stale');

        $explanation = $this->service(static function (string $planId): array {
            throw new FoundryError(
                'PLAN_REPLAY_STRICT_DRIFT',
                'validation',
                ['plan_id' => $planId, 'drift_summary' => ['detected' => true]],
                'Strict replay cannot proceed because material drift was detected.',
            );
        })->explain('plan-stale');

        $this->assertSame('stale', $explanation['execution_state']);
        $this->assertSame('stale', $explanation['readiness']['status']);
        $this->assertSame('PLAN_STALE', $explanation['readiness']['reasons'][0]['code']);
        $this->assertSame('regenerate_plan', $explanation['readiness']['next_actions'][0]['type']);
    }

    public function test_unknown_plan_id_returns_missing_output(): void
    {
        $explanation = $this->service(static fn(string $planId): array => [])->explain('missing-plan');

        $this->assertSame('missing', $explanation['status']);
        $this->assertSame('invalid', $explanation['execution_state']);
        $this->assertSame('PLAN_RECORD_NOT_FOUND', $explanation['validation']['errors'][0]['code']);
    }

    public function test_sorting_and_secret_redaction_are_stable(): void
    {
        $this->persistRecord('plan-sorted');

        $explanation = $this->service(static function (string $planId): array {
            throw new FoundryError(
                'ENTITLEMENT_STATE_CHANGED',
                'validation',
                [
                    'plan_id' => $planId,
                    'license_key' => 'raw-secret-license',
                    'current_execution_state' => 'blocked_missing_entitlement',
                    'current_entitlements' => [
                        'status' => 'incomplete',
                        'required' => ['foundry/zeta', 'foundry/alpha'],
                        'missing' => ['foundry/zeta', 'foundry/alpha'],
                    ],
                    'current_pack_requirements' => [
                        ['pack' => 'foundry/zeta', 'source' => 'marketplace', 'distribution' => 'premium', 'entitlement' => ['required' => true, 'status' => 'missing', 'tier' => 'premium']],
                        ['pack' => 'foundry/alpha', 'source' => 'marketplace', 'distribution' => 'premium', 'entitlement' => ['required' => true, 'status' => 'missing', 'tier' => 'premium']],
                    ],
                ],
                'Marketplace entitlement state changed and now blocks execution.',
            );
        })->explain('plan-sorted');

        $this->assertSame(['foundry/alpha', 'foundry/zeta'], array_column($explanation['packs'], 'name'));
        $this->assertSame(['foundry/alpha', 'foundry/zeta'], array_column($explanation['readiness']['reasons'], 'pack'));
        $this->assertSame(['foundry/alpha', 'foundry/zeta'], array_column($explanation['readiness']['next_actions'], 'pack'));
        $this->assertStringNotContainsString('raw-secret-license', json_encode($explanation, JSON_THROW_ON_ERROR));
        $this->assertSame('[redacted]', $explanation['validation']['errors'][0]['details']['license_key']);
    }

    public function test_rejects_empty_plan_id(): void
    {
        $this->expectException(FoundryError::class);

        $this->service(static fn(string $planId): array => [])->explain('  ');
    }

    public function test_malformed_record_returns_invalid_output(): void
    {
        $this->writeRawRecord('invalid', ['intent' => 'Missing id']);

        $explanation = $this->service(static fn(string $planId): array => [])->explain('anything');

        $this->assertSame('invalid', $explanation['status']);
        $this->assertSame('PLAN_RECORD_INVALID', $explanation['validation']['errors'][0]['code']);
    }

    public function test_unreplayable_record_without_plan_data_returns_invalid_output(): void
    {
        $record = $this->record('plan-unavailable', []);
        $record['plan_original'] = null;
        $record['plan_final'] = null;
        $this->persistRawRecord($record);

        $explanation = $this->service(static function (string $planId): array {
            throw new FoundryError(
                'PLAN_REPLAY_PLAN_UNAVAILABLE',
                'validation',
                ['plan_id' => $planId],
                'Persisted plan record does not contain a replayable plan.',
            );
        })->explain('plan-unavailable');

        $this->assertSame('invalid', $explanation['status']);
        $this->assertSame('regenerate_plan', $explanation['readiness']['next_actions'][0]['type']);
    }

    public function test_completed_plan_uses_none_next_action(): void
    {
        $record = $this->record('plan-complete', []);
        $record['status'] = 'success';
        $record['actions_executed'] = [['type' => 'write_file', 'path' => 'app/features/comments/feature.yaml']];
        $this->persistRawRecord($record);

        $explanation = $this->service(static fn(string $planId): array => [
            'execution_state' => 'executable',
            'entitlements' => ['status' => 'complete'],
            'pack_requirements' => [],
        ])->explain('plan-complete');

        $this->assertSame('none', $explanation['readiness']['next_actions'][0]['type']);
    }

    public function test_unknown_and_unavailable_pack_blockers_are_explained(): void
    {
        $record = $this->record('plan-unknown', []);
        $record['generation_context_packet']['pack_hints'] = ['foundry/unknown'];
        $record['metadata']['requested_intent'] = null;
        $record['plan_original']['actions'][] = ['type' => 'noop'];
        $this->persistRawRecord($record);

        $unknown = $this->service(static fn(string $planId): array => [
            'execution_state' => 'blocked_unknown_entitlement',
            'entitlements' => [
                'required' => ['foundry/unknown'],
                'unknown' => ['foundry/unknown'],
            ],
            'pack_requirements' => [
                ['pack' => '', 'source' => 'bad'],
                [
                    'pack' => 'foundry/unknown',
                    'source' => 'remote',
                    'distribution' => 'surprise',
                    'entitlement' => ['required' => true, 'status' => 'mystery'],
                ],
            ],
        ])->explain('plan-unknown');

        $this->assertSame('blocked_unknown_entitlement', $unknown['execution_state']);
        $this->assertSame('UNKNOWN_ENTITLEMENT', $unknown['readiness']['reasons'][0]['code']);
        $this->assertSame('inspect_marketplace', $unknown['readiness']['next_actions'][0]['type']);
        $this->assertSame('Pack was requested explicitly.', $unknown['packs'][0]['reason']);
        $this->assertSame('unknown', $unknown['packs'][0]['source']);
        $this->assertSame('unknown', $unknown['packs'][0]['distribution']);
        $this->assertSame('invalid', $unknown['packs'][0]['entitlement']['status']);

        $this->persistRecord('plan-unavailable');
        $unavailable = $this->service(static fn(string $planId): array => [
            'execution_state' => 'blocked_pack_unavailable',
            'entitlements' => ['required' => ['foundry/missing'], 'unknown' => ['foundry/missing']],
            'pack_requirements' => [[
                'pack' => 'foundry/missing',
                'source' => 'marketplace',
                'distribution' => 'premium',
                'code' => 'MARKETPLACE_PACK_NOT_AVAILABLE',
                'entitlement' => ['required' => true, 'status' => 'unknown'],
            ]],
        ])->explain('plan-unavailable');

        $this->assertSame('PACK_UNAVAILABLE', $unavailable['readiness']['reasons'][0]['code']);
        $this->assertSame('inspect_marketplace', $unavailable['readiness']['next_actions'][0]['type']);
    }

    public function test_invalid_conflict_and_unknown_states_have_stable_next_actions(): void
    {
        $this->persistRecord('plan-invalid');
        $invalid = $this->service(static fn(string $planId): array => [
            'execution_state' => 'executable',
            'entitlements' => ['status' => 'invalid', 'invalid' => ['foundry/bad']],
            'pack_requirements' => [[
                'pack' => 'foundry/bad',
                'source' => 'marketplace',
                'distribution' => 'premium',
                'entitlement' => ['required' => true, 'status' => 'invalid'],
            ]],
        ])->explain('plan-invalid');

        $this->assertSame('invalid', $invalid['execution_state']);
        $this->assertSame('INVALID_ENTITLEMENT', $invalid['readiness']['reasons'][0]['code']);
        $this->assertSame('regenerate_plan', $invalid['readiness']['next_actions'][0]['type']);

        $this->persistRecord('plan-conflict');
        $conflict = $this->service(static function (string $planId): array {
            throw new FoundryError(
                'PLAN_REPLAY_PRECONDITION_FAILED',
                'validation',
                ['plan_id' => $planId],
                'Replay cannot proceed while the current graph has errors.',
            );
        })->explain('plan-conflict');

        $this->assertSame('blocked_conflict', $conflict['execution_state']);
        $this->assertSame('PLAN_CONFLICT', $conflict['readiness']['reasons'][0]['code']);
        $this->assertSame('validate_plan', $conflict['readiness']['next_actions'][0]['type']);

        $this->persistRecord('plan-unknown-state');
        $unknown = $this->service(static fn(string $planId): array => [
            'execution_state' => 'mystery',
            'entitlements' => ['status' => 'complete'],
            'pack_requirements' => [],
        ])->explain('plan-unknown-state');

        $this->assertSame('unknown', $unknown['execution_state']);
        $this->assertSame('unknown', $unknown['readiness']['status']);
        $this->assertSame('validate_plan', $unknown['readiness']['next_actions'][0]['type']);
    }

    /**
     * @param array<int,string> $packHints
     */
    private function persistRecord(string $planId, array $packHints = []): void
    {
        $this->persistRawRecord($this->record($planId, $packHints));
    }

    /**
     * @param array<string,mixed> $record
     */
    private function persistRawRecord(array $record): void
    {
        (new PlanRecordStore(
            new Paths($this->project->root),
            static fn(): \DateTimeImmutable => new \DateTimeImmutable('2026-04-23T01:02:03Z'),
        ))->persist($record);
    }

    /**
     * @param array<string,mixed> $record
     */
    private function writeRawRecord(string $suffix, array $record): void
    {
        $plansDir = $this->project->root . '/.foundry/plans';
        if (!is_dir($plansDir)) {
            mkdir($plansDir, 0777, true);
        }

        file_put_contents(
            $plansDir . '/20260423T010203Z_' . $suffix . '.json',
            json_encode($record, JSON_THROW_ON_ERROR) . PHP_EOL,
        );
    }

    /**
     * @param array<int,string> $packHints
     * @return array<string,mixed>
     */
    private function record(string $planId, array $packHints): array
    {
        $plan = [
            'actions' => [
                ['type' => 'write_file', 'path' => 'app/features/comments/routes.yaml'],
                ['type' => 'write_file', 'path' => 'app/features/comments/feature.yaml'],
            ],
            'affected_files' => [
                'app/features/comments/routes.yaml',
                'app/features/comments/feature.yaml',
            ],
            'risks' => [],
            'validations' => [],
            'origin' => 'core',
            'generator_id' => 'core.new_feature',
            'extension' => null,
            'confidence' => [],
            'metadata' => [],
        ];

        return [
            'plan_id' => $planId,
            'intent' => 'Create comments',
            'mode' => 'new',
            'targets' => [],
            'generation_context_packet' => [
                'pack_requirements' => [],
                'entitlements' => ['status' => 'not_required'],
                'execution_state' => 'executable',
            ],
            'plan_original' => $plan,
            'plan_final' => null,
            'interactive' => null,
            'user_decisions' => [],
            'actions_executed' => [],
            'affected_files' => $plan['affected_files'],
            'risk_level' => 'LOW',
            'policy' => null,
            'approval' => null,
            'verification_results' => ['skipped' => true, 'ok' => true],
            'status' => 'planned',
            'error' => null,
            'undo' => null,
            'metadata' => [
                'requested_intent' => [
                    'raw' => 'Create comments',
                    'mode' => 'new',
                    'allow_risky' => false,
                    'pack_hints' => $packHints,
                ],
                'execution_state' => 'executable',
                'entitlements' => ['status' => 'not_required'],
            ],
        ];
    }

    /**
     * @param \Closure(string):array<string,mixed> $replay
     */
    private function service(\Closure $replay): PlanExplanationService
    {
        return new PlanExplanationService(new Paths($this->project->root), replay: $replay);
    }
}
