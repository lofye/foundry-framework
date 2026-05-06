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
        $this->assertSame('.foundry/marketplace/identity.json', $whoamiBefore['payload']['storage']['path']);
        $this->assertFalse($whoamiBefore['payload']['storage']['exists']);

        $login = $this->runCommand($app, ['foundry', 'login', '--user=demo-user', '--token=token_demo_1234', '--json']);
        $this->assertSame(0, $login['status']);
        $this->assertTrue($login['payload']['authenticated']);
        $this->assertSame('demo-user', $login['payload']['identity']['user_id']);
        $this->assertSame('toke...1234', $login['payload']['identity']['token_hint']);
        $this->assertTrue($login['payload']['storage']['exists']);

        $whoamiAfter = $this->runCommand($app, ['foundry', 'whoami', '--json']);
        $this->assertSame(0, $whoamiAfter['status']);
        $this->assertSame($login['payload'], $whoamiAfter['payload']);

        $logout = $this->runCommand($app, ['foundry', 'logout', '--json']);
        $this->assertSame(0, $logout['status']);
        $this->assertFalse($logout['payload']['authenticated']);
        $this->assertFalse($logout['payload']['storage']['exists']);
        $this->assertTrue($logout['payload']['logout']['had_session']);
    }

    public function test_login_requires_user_and_token(): void
    {
        $app = new Application();
        $missingUser = $this->runCommand($app, ['foundry', 'login', '--token=token_demo_1234', '--json']);
        $missingToken = $this->runCommand($app, ['foundry', 'login', '--user=demo-user', '--json']);

        $this->assertSame(1, $missingUser['status']);
        $this->assertSame('MARKETPLACE_LOGIN_USER_REQUIRED', $missingUser['payload']['error']['code']);
        $this->assertSame(1, $missingToken['status']);
        $this->assertSame('MARKETPLACE_LOGIN_TOKEN_REQUIRED', $missingToken['payload']['error']['code']);
    }

    public function test_whoami_reports_invalid_identity_file_deterministically(): void
    {
        $path = $this->project->root . '/.foundry/marketplace/identity.json';
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, "{invalid-json}\n");

        $result = $this->runCommand(new Application(), ['foundry', 'whoami', '--json']);

        $this->assertSame(1, $result['status']);
        $this->assertSame('MARKETPLACE_IDENTITY_INVALID_JSON', $result['payload']['error']['code']);
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

