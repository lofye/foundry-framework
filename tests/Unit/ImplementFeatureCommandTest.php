<?php

declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\CLI\CommandContext;
use Foundry\CLI\Commands\ImplementFeatureCommand;
use Foundry\Support\FoundryError;
use PHPUnit\Framework\TestCase;

final class ImplementFeatureCommandTest extends TestCase
{
    public function test_run_requires_feature_name(): void
    {
        $error = $this->expectFoundryError(function (): void {
            (new ImplementFeatureCommand())->run(['implement', 'feature'], new CommandContext());
        });

        $this->assertSame('CLI_IMPLEMENT_FEATURE_REQUIRED', $error->errorCode);
    }

    public function test_run_rejects_conflicting_repair_modes(): void
    {
        $error = $this->expectFoundryError(function (): void {
            (new ImplementFeatureCommand())->run(
                ['implement', 'feature', 'billing', '--repair', '--auto-repair'],
                new CommandContext(),
            );
        });

        $this->assertSame('CLI_IMPLEMENT_REPAIR_MODE_CONFLICT', $error->errorCode);
        $this->assertSame(['repair' => true, 'auto_repair' => true], $error->details);
    }

    public function test_json_run_returns_payload_and_forwards_repair_flags(): void
    {
        $captured = [];
        $payload = $this->payload(['status' => 'repaired']);
        $command = new ImplementFeatureCommand(
            function (string $feature, bool $repair, bool $autoRepair, CommandContext $context) use (&$captured, $payload): array {
                $captured = compact('feature', 'repair', 'autoRepair', 'context');

                return $payload;
            },
        );

        $result = $command->run(['implement', 'feature', 'billing', '--repair'], new CommandContext(jsonOutput: true));

        $this->assertSame(0, $result['status']);
        $this->assertNull($result['message']);
        $this->assertSame($payload, $result['payload']);
        $this->assertSame('billing', $captured['feature']);
        $this->assertTrue($captured['repair']);
        $this->assertFalse($captured['autoRepair']);
        $this->assertInstanceOf(CommandContext::class, $captured['context']);
    }

    public function test_auto_repair_flag_is_forwarded(): void
    {
        $captured = [];
        $command = new ImplementFeatureCommand(
            function (string $feature, bool $repair, bool $autoRepair) use (&$captured): array {
                $captured = compact('feature', 'repair', 'autoRepair');

                return $this->payload(['status' => 'completed']);
            },
        );

        $result = $command->run(['implement', 'feature', 'billing', '--auto-repair'], new CommandContext(jsonOutput: true));

        $this->assertSame(0, $result['status']);
        $this->assertFalse($captured['repair']);
        $this->assertTrue($captured['autoRepair']);
    }

    public function test_blocked_status_exits_non_zero_and_renders_empty_sections(): void
    {
        $command = new ImplementFeatureCommand(fn(): array => $this->payload(['status' => 'blocked']));

        $result = $command->run(['implement', 'feature', 'billing'], new CommandContext());

        $this->assertSame(1, $result['status']);
        $this->assertNull($result['payload']);
        $this->assertSame(<<<'TEXT'
Implement feature: billing
Status: blocked
Can proceed: yes
Requires repair: no
Repair attempted: no
Repair successful: no
Actions taken:
- none
Issues:
- none
Required actions:
- none
TEXT, $result['message']);
    }

    public function test_human_output_renders_issues_actions_quality_gate_reason_and_required_action(): void
    {
        $command = new ImplementFeatureCommand(fn(): array => $this->payload([
            'status' => 'completed_with_issues',
            'can_proceed' => false,
            'requires_repair' => true,
            'repair_attempted' => true,
            'repair_successful' => false,
            'actions_taken' => ['checked context', 'ran verifier'],
            'issues' => [
                ['code' => 'CTX_MISSING', 'message' => 'Context is missing.'],
                ['message' => 'Message only.'],
            ],
            'required_actions' => ['repair context'],
            'quality_gate' => [
                'passed' => false,
                'coverage' => ['global_line_coverage' => 89.456],
            ],
            'reason' => 'Context verification failed.',
            'required_action' => 'Run context repair.',
        ]));

        $result = $command->run(['implement', 'feature', 'billing'], new CommandContext());

        $this->assertSame(1, $result['status']);
        $this->assertSame(<<<'TEXT'
Implement feature: billing
Status: completed_with_issues
Can proceed: no
Requires repair: yes
Repair attempted: yes
Repair successful: no
Actions taken:
- checked context
- ran verifier
Issues:
- CTX_MISSING: Context is missing.
- : Message only.
Required actions:
- repair context
Quality gate: failed
Global line coverage: 89.46%
Reason: Context verification failed.
Required action: Run context repair.
TEXT, $result['message']);
    }

    public function test_completed_status_exits_successfully(): void
    {
        $command = new ImplementFeatureCommand(fn(): array => $this->payload(['status' => 'completed']));

        $result = $command->run(['implement', 'feature', 'billing'], new CommandContext(jsonOutput: true));

        $this->assertSame(0, $result['status']);
    }

    /**
     * @param array<string,mixed> $overrides
     *
     * @return array<string,mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_replace([
            'feature' => 'billing',
            'status' => 'completed',
            'can_proceed' => true,
            'requires_repair' => false,
            'repair_attempted' => false,
            'repair_successful' => false,
            'actions_taken' => [],
            'issues' => [],
            'required_actions' => [],
        ], $overrides);
    }

    /**
     * @param callable():void $callback
     */
    private function expectFoundryError(callable $callback): FoundryError
    {
        try {
            $callback();
        } catch (FoundryError $error) {
            return $error;
        }

        self::fail('Expected FoundryError was not thrown.');
    }
}
