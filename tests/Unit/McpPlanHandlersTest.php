<?php

declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\CLI\Application;
use Foundry\CLI\Command;
use Foundry\CLI\CommandContext;
use Foundry\MCP\CliReadBridge;
use Foundry\MCP\Handlers\GeneratePlanHandler;
use Foundry\MCP\Handlers\ValidatePlanHandler;
use Foundry\Support\FoundryError;
use PHPUnit\Framework\TestCase;

final class McpPlanHandlersTest extends TestCase
{
    public function test_generate_plan_returns_normalized_planned_payload(): void
    {
        $handler = new GeneratePlanHandler($this->bridge(static function (array $args): array {
            TestCase::assertSame('generate', $args[0] ?? null);
            TestCase::assertContains('--mode=new', $args);
            TestCase::assertContains('--dry-run', $args);
            TestCase::assertContains('--packs=foundry/auth,foundry/blog', $args);

            return [
                'status' => 0,
                'payload' => [
                    'ok' => true,
                    'execution_state' => 'executable',
                    'plan' => ['actions' => []],
                    'plan_record' => [
                        'plan_id' => 'plan-123',
                        'storage_path' => '.foundry/plans/example.json',
                    ],
                    'entitlements' => [
                        'status' => 'complete',
                        'required' => [],
                        'granted' => [],
                        'missing' => [],
                        'expired' => [],
                        'unknown' => [],
                    ],
                    'pack_requirements' => [
                        ['pack' => 'foundry/blog', 'source' => 'marketplace'],
                        ['pack' => 'foundry/auth', 'source' => 'marketplace'],
                    ],
                ],
                'message' => null,
            ];
        }));

        $result = $handler->handle([
            'intent' => 'Create blog',
            'mode' => 'new',
            'packs' => ['foundry/blog', 'foundry/auth', 'foundry/blog'],
            'allow_pack_install' => false,
            'allow_premium_packs' => false,
        ]);

        $this->assertSame('planned', $result['status']);
        $this->assertSame('plan-123', $result['plan_id']);
        $this->assertSame('.foundry/plans/example.json', $result['plan_record_path']);
        $this->assertSame('executable', $result['execution_state']);
        $this->assertSame('valid', $result['validation']['status']);
        $this->assertSame(['foundry/auth', 'foundry/blog'], array_values(array_map(
            static fn(array $row): string => (string) ($row['pack'] ?? ''),
            $result['pack_requirements'],
        )));
    }

    public function test_generate_plan_maps_pack_unavailable_error_to_blocked_state(): void
    {
        $handler = new GeneratePlanHandler($this->bridge(static fn(array $args): array => [
            'status' => 1,
            'payload' => [
                'error' => [
                    'code' => 'GENERATE_PACK_INSTALL_REQUIRED',
                    'message' => 'Required packs are not installed.',
                    'details' => [
                        'execution_state' => 'invalid',
                        'entitlements' => [
                            'status' => 'incomplete',
                            'required' => ['foundry/blog'],
                            'granted' => [],
                            'missing' => [],
                            'expired' => [],
                            'unknown' => ['foundry/blog'],
                        ],
                        'pack_requirements' => [[
                            'pack' => 'foundry/blog',
                            'code' => 'MARKETPLACE_PACK_NOT_AVAILABLE',
                        ]],
                    ],
                ],
            ],
            'message' => null,
        ]));

        $result = $handler->handle(['intent' => 'Create blog']);

        $this->assertSame('blocked', $result['status']);
        $this->assertSame('blocked_pack_unavailable', $result['execution_state']);
        $this->assertSame('blocked', $result['validation']['status']);
        $this->assertSame('GENERATE_PACK_INSTALL_REQUIRED', $result['error']['code']);
    }

    public function test_generate_plan_rejects_non_boolean_flag_inputs(): void
    {
        $handler = new GeneratePlanHandler($this->bridge(static fn(array $args): array => [
            'status' => 0,
            'payload' => [],
            'message' => null,
        ]));

        $this->expectException(FoundryError::class);

        try {
            $handler->handle([
                'intent' => 'Create blog',
                'allow_pack_install' => 'yes',
            ]);
        } catch (FoundryError $error) {
            $this->assertSame('MCP_INPUT_INVALID', $error->errorCode);
            throw $error;
        }
    }

    public function test_validate_plan_returns_valid_for_plan_id_path(): void
    {
        $handler = new ValidatePlanHandler($this->bridge(static fn(array $args): array => [
            'status' => 0,
            'payload' => [
                'status' => 'dry_run',
                'execution_state' => 'executable',
                'entitlements' => [
                    'status' => 'complete',
                    'required' => [],
                    'granted' => [],
                    'missing' => [],
                    'expired' => [],
                    'unknown' => [],
                ],
                'pack_requirements' => [],
            ],
            'message' => null,
        ]));

        $result = $handler->handle(['plan_id' => 'plan-123']);

        $this->assertSame('valid', $result['status']);
        $this->assertSame('plan-123', $result['plan_id']);
        $this->assertSame('executable', $result['execution_state']);
        $this->assertSame('valid', $result['validation']['status']);
    }

    public function test_validate_plan_maps_strict_drift_to_stale_status(): void
    {
        $handler = new ValidatePlanHandler($this->bridge(static fn(array $args): array => [
            'status' => 1,
            'payload' => [
                'error' => [
                    'code' => 'PLAN_REPLAY_STRICT_DRIFT',
                    'message' => 'Strict replay cannot proceed because material drift was detected.',
                    'details' => [
                        'current_execution_state' => 'executable',
                        'drift_summary' => ['detected' => true],
                    ],
                ],
            ],
            'message' => null,
        ]));

        $result = $handler->handle(['plan_id' => 'plan-123']);

        $this->assertSame('stale', $result['status']);
        $this->assertSame('stale', $result['execution_state']);
        $this->assertSame('PLAN_REPLAY_STRICT_DRIFT', $result['validation']['errors'][0]['code']);
    }

    public function test_validate_plan_maps_missing_entitlement_to_blocked_status(): void
    {
        $handler = new ValidatePlanHandler($this->bridge(static fn(array $args): array => [
            'status' => 1,
            'payload' => [
                'error' => [
                    'code' => 'MISSING_ENTITLEMENT',
                    'message' => 'Marketplace entitlement is missing.',
                    'details' => [
                        'current_execution_state' => 'blocked_missing_entitlement',
                        'current_entitlements' => [
                            'status' => 'incomplete',
                            'required' => ['foundry/auth'],
                            'granted' => [],
                            'missing' => ['foundry/auth'],
                            'expired' => [],
                            'unknown' => [],
                        ],
                        'current_pack_requirements' => [[
                            'pack' => 'foundry/auth',
                            'source' => 'marketplace',
                        ]],
                    ],
                ],
            ],
            'message' => null,
        ]));

        $result = $handler->handle(['plan_id' => 'plan-123']);

        $this->assertSame('blocked', $result['status']);
        $this->assertSame('blocked_missing_entitlement', $result['execution_state']);
    }

    public function test_validate_plan_supports_inline_plan_input(): void
    {
        $handler = new ValidatePlanHandler($this->bridge(static function (array $args): array {
            self::fail('Inline plan validation should not call CLI bridge.');
        }));

        $result = $handler->handle([
            'plan' => [
                'origin' => 'core',
                'generator_id' => 'core.feature.new',
                'actions' => [[
                    'type' => 'create_file',
                    'path' => 'app/features/comments/feature.yaml',
                    'explain_node_id' => 'feature:comments',
                ]],
                'affected_files' => ['app/features/comments/feature.yaml'],
                'risks' => [],
                'validations' => ['compile_graph'],
                'metadata' => [],
            ],
        ]);

        $this->assertSame('valid', $result['status']);
        $this->assertNull($result['plan_id']);
        $this->assertSame('executable', $result['execution_state']);
    }

    public function test_validate_plan_inline_invalid_plan_returns_invalid_status(): void
    {
        $handler = new ValidatePlanHandler($this->bridge(static function (array $args): array {
            self::fail('Inline plan validation should not call CLI bridge.');
        }));

        $result = $handler->handle([
            'plan' => [
                'origin' => 'core',
                'generator_id' => 'core.feature.new',
                'actions' => [[
                    'type' => 'create_file',
                    'path' => 'app/features/comments/feature.yaml',
                ]],
                'affected_files' => ['app/features/comments/feature.yaml'],
                'risks' => [],
                'validations' => ['compile_graph'],
                'metadata' => [],
            ],
        ]);

        $this->assertSame('invalid', $result['status']);
        $this->assertSame('invalid', $result['execution_state']);
    }

    public function test_validate_plan_inline_entitlements_without_pack_hints_map_to_blocked_unknown(): void
    {
        $handler = new ValidatePlanHandler($this->bridge(static function (array $args): array {
            self::fail('Inline plan validation without pack hints should not call CLI bridge.');
        }));

        $result = $handler->handle([
            'plan' => [
                'origin' => 'core',
                'generator_id' => 'core.feature.new',
                'actions' => [[
                    'type' => 'create_file',
                    'path' => 'app/features/comments/feature.yaml',
                    'explain_node_id' => 'feature:comments',
                ]],
                'affected_files' => ['app/features/comments/feature.yaml'],
                'risks' => [],
                'validations' => ['compile_graph'],
                'metadata' => [
                    'execution_state' => 'invalid',
                    'entitlements' => [
                        'status' => 'incomplete',
                        'required' => [],
                        'granted' => [],
                        'missing' => [],
                        'expired' => [],
                        'unknown' => ['foundry/auth'],
                    ],
                ],
            ],
        ]);

        $this->assertSame('blocked', $result['status']);
        $this->assertSame('blocked_unknown_entitlement', $result['execution_state']);
    }

    public function test_validate_plan_requires_exactly_one_plan_input_source(): void
    {
        $handler = new ValidatePlanHandler($this->bridge(static fn(array $args): array => [
            'status' => 0,
            'payload' => [],
            'message' => null,
        ]));

        $this->expectException(FoundryError::class);

        try {
            $handler->handle([]);
        } catch (FoundryError $error) {
            $this->assertSame('MCP_INPUT_INVALID', $error->errorCode);
            throw $error;
        }
    }

    public function test_validate_plan_rejects_when_both_plan_sources_are_provided(): void
    {
        $handler = new ValidatePlanHandler($this->bridge(static fn(array $args): array => [
            'status' => 0,
            'payload' => [],
            'message' => null,
        ]));

        $this->expectException(FoundryError::class);

        try {
            $handler->handle([
                'plan_id' => 'plan-123',
                'plan' => ['origin' => 'core'],
            ]);
        } catch (FoundryError $error) {
            $this->assertSame('MCP_INPUT_INVALID', $error->errorCode);
            throw $error;
        }
    }

    /**
     * @param \Closure(array<int,string>):array{status:int,payload:array<string,mixed>|null,message:string|null} $resolver
     */
    private function bridge(\Closure $resolver): CliReadBridge
    {
        return new CliReadBridge(new Application([
            new class($resolver) extends Command {
                /**
                 * @param \Closure(array<int,string>):array{status:int,payload:array<string,mixed>|null,message:string|null} $resolver
                 */
                public function __construct(private readonly \Closure $resolver) {}

                public function supportedSignatures(): array
                {
                    return ['fake'];
                }

                public function matches(array $args): bool
                {
                    return true;
                }

                public function run(array $args, CommandContext $context): array
                {
                    $result = ($this->resolver)($args);

                    return [
                        'status' => (int) ($result['status'] ?? 0),
                        'payload' => is_array($result['payload'] ?? null) ? $result['payload'] : [],
                        'message' => is_string($result['message'] ?? null) ? $result['message'] : null,
                    ];
                }
            },
        ]));
    }
}
