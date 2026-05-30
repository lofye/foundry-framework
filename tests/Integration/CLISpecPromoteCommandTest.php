<?php

declare(strict_types=1);

namespace Foundry\Tests\Integration;

use Foundry\CLI\Application;
use Foundry\Tests\Fixtures\TempProject;
use PHPUnit\Framework\TestCase;

final class CLISpecPromoteCommandTest extends TestCase
{
    private TempProject $project;
    private string $cwd;

    protected function setUp(): void
    {
        $this->project = new TempProject();
        $this->cwd = getcwd() ?: '.';
        chdir($this->project->root);
    }

    protected function tearDown(): void
    {
        chdir($this->cwd);
        $this->project->cleanup();
    }

    public function test_promotes_draft_spec_to_active_path(): void
    {
        $app = new Application();
        $this->runCommand($app, ['foundry', 'context', 'init', 'event-bus', '--json']);

        $draftPath = $this->project->root . '/Features/EventBus/specs/drafts/001-batch-command.md';
        $activePath = $this->project->root . '/Features/EventBus/specs/001-batch-command.md';
        $draftContents = <<<'MD'
# Execution Spec: 001-batch-command

## Feature
- event-bus

## Purpose
- Promote this spec.
MD;
        if (!is_dir(dirname($draftPath))) {
            mkdir(dirname($draftPath), 0777, true);
        }
        file_put_contents($draftPath, $draftContents);

        $result = $this->runCommand($app, ['foundry', 'spec:promote', 'event-bus', '001', '--json']);

        $this->assertFileDoesNotExist($draftPath);
        $this->assertFileExists($activePath);
        $this->assertSame('001-batch-command', $result['payload']['name']);
        $this->assertSame('Features/EventBus/specs/drafts/001-batch-command.md', $result['payload']['draft_path']);
        $this->assertSame('Features/EventBus/specs/001-batch-command.md', $result['payload']['active_path']);
    }

    public function test_requires_target(): void
    {
        $app = new Application();
        $result = $this->runCommand($app, ['foundry', 'spec:promote', '--json']);

        $this->assertSame(1, $result['status']);
        $this->assertSame('CLI_SPEC_PROMOTE_TARGET_REQUIRED', $result['payload']['error']['code']);
    }

    /**
     * @param array<int,string> $argv
     * @return array{status:int,payload:array<string,mixed>}
     */
    private function runCommand(Application $app, array $argv): array
    {
        ob_start();
        $status = $app->run($argv);
        $output = ob_get_clean() ?: '';

        /** @var array<string,mixed> $payload */
        $payload = json_decode($output, true, 512, JSON_THROW_ON_ERROR);

        return ['status' => $status, 'payload' => $payload];
    }
}
