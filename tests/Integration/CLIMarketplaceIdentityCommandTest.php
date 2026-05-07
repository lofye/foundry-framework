<?php

declare(strict_types=1);

namespace Foundry\Tests\Integration;

use Foundry\CLI\Application;
use Foundry\Tests\Fixtures\TempProject;
use PHPUnit\Framework\TestCase;

final class CLIMarketplaceIdentityCommandTest extends TestCase
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

    public function test_login_whoami_and_logout_manage_identity_deterministically(): void
    {
        $app = new Application();

        $whoamiBefore = $this->runCommand($app, ['foundry', 'whoami', '--json']);
        $this->assertSame(0, $whoamiBefore['status']);
        $this->assertFalse($whoamiBefore['payload']['authenticated']);
        $this->assertNull($whoamiBefore['payload']['user']);

        $login = $this->runCommand($app, ['foundry', 'login', '--user=demo-user', '--token=token_demo_1234', '--json']);
        $this->assertSame(0, $login['status']);
        $this->assertSame('ok', $login['payload']['status']);
        $this->assertTrue($login['payload']['authenticated']);
        $this->assertSame('demo-user', $login['payload']['user']['id']);
        $this->assertSame('demo-user@marketplace.local', $login['payload']['user']['email']);

        $whoamiAfter = $this->runCommand($app, ['foundry', 'whoami', '--json']);
        $this->assertSame(0, $whoamiAfter['status']);
        $this->assertTrue($whoamiAfter['payload']['authenticated']);
        $this->assertSame('demo-user', $whoamiAfter['payload']['user']['id']);
        $this->assertSame('demo-user@marketplace.local', $whoamiAfter['payload']['user']['email']);
        $this->assertArrayNotHasKey('access_token', $whoamiAfter['payload']['user']);

        $logout = $this->runCommand($app, ['foundry', 'logout', '--json']);
        $this->assertSame(0, $logout['status']);
        $this->assertSame('ok', $logout['payload']['status']);
        $this->assertFalse($logout['payload']['authenticated']);
        $this->assertNull($logout['payload']['user']);
        $this->assertTrue($logout['payload']['logout']['had_session']);
    }

    public function test_login_requires_user_and_token(): void
    {
        $app = new Application();
        $missingUser = $this->runCommand($app, ['foundry', 'login', '--token=token_demo_1234', '--json']);
        $missingToken = $this->runCommand($app, ['foundry', 'login', '--user=demo-user', '--json']);

        $this->assertSame(1, $missingUser['status']);
        $this->assertSame('MARKETPLACE_AUTH_FAILED', $missingUser['payload']['error']['code']);
        $this->assertSame(1, $missingToken['status']);
        $this->assertSame('MARKETPLACE_AUTH_FAILED', $missingToken['payload']['error']['code']);
    }

    public function test_whoami_reports_invalid_identity_file_deterministically(): void
    {
        $path = $this->project->root . '/.foundry/marketplace/identity.json';
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, "{invalid-json}\n");

        $result = $this->runCommand(new Application(), ['foundry', 'whoami', '--json']);

        $this->assertSame(0, $result['status']);
        $this->assertSame('error', $result['payload']['status']);
        $this->assertSame('MARKETPLACE_AUTH_STATE_INVALID', $result['payload']['code']);
        $this->assertFalse($result['payload']['authenticated']);
        $this->assertNull($result['payload']['user']);
    }

    public function test_whoami_reports_expired_credentials_deterministically(): void
    {
        $path = $this->project->root . '/.foundry/marketplace/identity.json';
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, json_encode([
            'token_type' => 'bearer',
            'access_token' => 'token_demo_1234',
            'expires_at' => '2025-01-01T00:00:00Z',
            'user' => [
                'id' => 'demo-user',
                'email' => 'demo@example.com',
                'name' => null,
                'created_at' => '2026-01-01T00:00:00Z',
            ],
        ], JSON_THROW_ON_ERROR) . "\n");

        $result = $this->runCommand(new Application(), ['foundry', 'whoami', '--json']);

        $this->assertSame(0, $result['status']);
        $this->assertFalse($result['payload']['authenticated']);
        $this->assertSame('expired', $result['payload']['reason']);
        $this->assertSame('MARKETPLACE_AUTH_EXPIRED', $result['payload']['code']);
        $this->assertSame('demo-user', $result['payload']['user']['id']);
        $this->assertSame('demo@example.com', $result['payload']['user']['email']);
    }

    public function test_entitlements_lists_cached_entitlements_deterministically(): void
    {
        $path = $this->project->root . '/.foundry/marketplace/entitlements.json';
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, json_encode([
            'entitlements' => [[
                'pack' => 'vendor/premium-pack',
                'type' => 'premium',
                'status' => 'granted',
                'expires_at' => null,
                'source' => 'marketplace',
                'granted_at' => '2026-01-01T00:00:00Z',
            ]],
            'updated_at' => '2026-01-01T00:00:00Z',
        ], JSON_THROW_ON_ERROR) . "\n");

        $result = $this->runCommand(new Application(), ['foundry', 'entitlements', '--json']);

        $this->assertSame(0, $result['status']);
        $this->assertSame('ok', $result['payload']['status']);
        $this->assertSame('vendor/premium-pack', $result['payload']['entitlements'][0]['pack']);
    }

    public function test_login_supports_space_delimited_options_and_plain_text_output(): void
    {
        $app = new Application();

        $login = $this->runCommand($app, ['foundry', 'login', '--user', 'demo-user', '--token', 'token_demo_1234', '--json']);
        $this->assertSame(0, $login['status']);
        $this->assertTrue($login['payload']['authenticated']);

        $whoami = $this->runCommandRaw($app, ['foundry', 'whoami']);
        $this->assertSame(0, $whoami['status']);
        $this->assertStringContainsString('Marketplace identity inspected.', $whoami['output']);
        $this->assertStringContainsString('Authenticated: yes', $whoami['output']);
        $this->assertStringContainsString('User: demo-user', $whoami['output']);
    }

    public function test_entitlements_plain_text_lists_none_when_cache_missing(): void
    {
        $result = $this->runCommandRaw(new Application(), ['foundry', 'entitlements']);

        $this->assertSame(0, $result['status']);
        $this->assertStringContainsString('Marketplace entitlements:', $result['output']);
        $this->assertStringContainsString('- none', $result['output']);
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

    /**
     * @param array<int,string> $argv
     * @return array{status:int,output:string}
     */
    private function runCommandRaw(Application $app, array $argv): array
    {
        ob_start();
        $status = $app->run($argv);
        $output = (string) (ob_get_clean() ?: '');

        return ['status' => $status, 'output' => $output];
    }
}
