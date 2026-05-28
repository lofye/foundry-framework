<?php

declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\CLI\Workflow\BatchWorkflowRunner;
use PHPUnit\Framework\TestCase;

final class BatchWorkflowRunnerTest extends TestCase
{
    public function test_stops_on_first_failure_by_default(): void
    {
        $runner = new BatchWorkflowRunner();
        $executed = [];

        $result = $runner->run('demo', [
            [
                'label' => 'first',
                'command' => 'first',
                'run' => function () use (&$executed): array {
                    $executed[] = 'first';

                    return ['status' => 0, 'message' => null, 'payload' => []];
                },
            ],
            [
                'label' => 'second',
                'command' => 'second',
                'run' => function () use (&$executed): array {
                    $executed[] = 'second';

                    return ['status' => 1, 'message' => null, 'payload' => ['required_actions' => ['Fix second']]];
                },
            ],
            [
                'label' => 'third',
                'command' => 'third',
                'run' => function () use (&$executed): array {
                    $executed[] = 'third';

                    return ['status' => 0, 'message' => null, 'payload' => []];
                },
            ],
        ]);

        $this->assertSame(['first', 'second'], $executed);
        $this->assertFalse($result['ok']);
        $this->assertSame(1, $result['status']);
        $this->assertSame('second', $result['failed_step']);
        $this->assertSame(['Fix second'], $result['next_actions']);
        $this->assertSame(2, $result['summary']['total']);
    }

    public function test_can_continue_after_failure_when_requested(): void
    {
        $runner = new BatchWorkflowRunner();
        $executed = [];

        $result = $runner->run('demo', [
            [
                'label' => 'first',
                'command' => 'first',
                'run' => function () use (&$executed): array {
                    $executed[] = 'first';

                    return ['status' => 1, 'message' => null, 'payload' => []];
                },
            ],
            [
                'label' => 'second',
                'command' => 'second',
                'run' => function () use (&$executed): array {
                    $executed[] = 'second';

                    return ['status' => 0, 'message' => null, 'payload' => []];
                },
            ],
        ], continueOnFailure: true);

        $this->assertSame(['first', 'second'], $executed);
        $this->assertFalse($result['ok']);
        $this->assertSame(2, $result['summary']['total']);
        $this->assertSame('first', $result['failed_step']);
    }
}
