<?php

declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\CLI\Commands\InitAppCommand;
use Foundry\Support\FoundryError;
use Foundry\Support\Paths;
use Foundry\Tests\Fixtures\TempProject;
use PHPUnit\Framework\TestCase;

final class InitAppCommandInternalsTest extends TestCase
{
    private TempProject $project;
    private InitAppCommand $command;

    protected function setUp(): void
    {
        $this->project = new TempProject();
        $this->command = new InitAppCommand();
    }

    protected function tearDown(): void
    {
        $this->project->cleanup();
    }

    public function test_render_human_message_and_usage_branches_are_deterministic(): void
    {
        $message = $this->invoke('renderHumanMessage', [[
            'project_root' => '/tmp/demo',
            'starter_label' => 'Standard',
            'next_steps' => ['composer install', 'foundry verify graph --json'],
        ]]);

        self::assertIsString($message);
        $this->assertStringContainsString('Foundry project scaffolded.', $message);
        $this->assertStringContainsString('Root: /tmp/demo', $message);
        $this->assertStringContainsString('Starter: Standard', $message);
        $this->assertStringContainsString('- composer install', $message);

        $newUsage = $this->invoke('usage', [true]);
        $initUsage = $this->invoke('usage', [false]);
        $this->assertStringContainsString('foundry new [path]', (string) $newUsage);
        $this->assertStringContainsString('foundry init app <path>', (string) $initUsage);
    }

    public function test_path_and_option_parsing_helpers_cover_edge_inputs(): void
    {
        $cwd = $this->project->root;

        $this->assertSame($cwd, $this->invoke('resolvePath', [$cwd, '.']));
        $this->assertSame($cwd . '/demo', $this->invoke('resolvePath', [$cwd, './demo']));
        $this->assertSame('/abs/path', $this->invoke('resolvePath', [$cwd, '/abs/path/']));

        $this->assertSame('acme/foundry-app', $this->invoke('defaultProjectName', [$cwd . '/___']));
        $this->assertSame('acme/blog-api', $this->invoke('defaultProjectName', [$cwd . '/blog-api']));

        $this->assertSame('minimal', $this->invoke('parseOption', [['--starter', 'minimal'], '--starter']));
        $this->assertNull($this->invoke('parseOption', [['--starter'], '--starter']));
    }

    public function test_existing_directory_bootstrap_rules_ignore_dotfiles_only(): void
    {
        $this->assertTrue($this->invoke('canBootstrapIntoExistingDirectory', [['.env', '.gitignore']]));
        $this->assertFalse($this->invoke('canBootstrapIntoExistingDirectory', [['README.md']]));
    }

    public function test_load_template_and_promotion_failures_are_reported_deterministically(): void
    {
        $paths = new Paths($this->project->root);

        try {
            $this->invoke('loadScaffoldTemplate', [$paths, 'stubs/missing-template.php', []]);
            self::fail('Expected missing template failure.');
        } catch (FoundryError $error) {
            $this->assertSame('CLI_INIT_APP_TEMPLATE_MISSING', $error->errorCode);
        }

        try {
            $this->invoke('promoteAppGuideTemplates', [$paths, []]);
            self::fail('Expected promotion source missing failure.');
        } catch (FoundryError $error) {
            $this->assertSame('CLI_INIT_APP_PROMOTION_SOURCE_MISSING', $error->errorCode);
        }
    }

    public function test_promotion_fails_when_existing_target_cannot_be_unlinked(): void
    {
        $paths = new Paths($this->project->root);
        file_put_contents($paths->join('APP-AGENTS.md'), "agents\n");
        file_put_contents($paths->join('APP-README.md'), "readme\n");
        mkdir($paths->join('AGENTS.md'), 0777, true);

        try {
            $this->invoke('promoteAppGuideTemplates', [$paths, []]);
            self::fail('Expected promotion target unlink failure.');
        } catch (FoundryError $error) {
            $this->assertSame('CLI_INIT_APP_PROMOTION_TARGET_UNLINK_FAILED', $error->errorCode);
        }
    }

    public function test_composer_merge_name_fallback_path(): void
    {
        $paths = new Paths($this->project->root);

        file_put_contents($this->project->root . '/composer.json', (string) json_encode([
            'name' => 'acme/existing',
            'require' => ['php' => '^8.4'],
        ], JSON_PRETTY_PRINT));

        $merged = $this->invoke('mergedComposerConfig', [$paths, [
            'description' => 'desc',
            'type' => 'project',
            'require' => ['lofye/foundry-framework' => '^0.1'],
            'require-dev' => [],
            'scripts' => [],
        ]]);

        self::assertIsArray($merged);
        $this->assertSame('acme/existing', $merged['name']);
    }

    /**
     * @param array<int,mixed> $args
     */
    private function invoke(string $method, array $args): mixed
    {
        $reflection = new \ReflectionMethod($this->command, $method);

        return $reflection->invokeArgs($this->command, $args);
    }
}
