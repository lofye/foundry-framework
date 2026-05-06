<?php

declare(strict_types=1);

namespace Foundry\Tests\Integration;

use Foundry\CLI\Application;
use Foundry\Tests\Fixtures\TempProject;
use PHPUnit\Framework\TestCase;

final class CLIMarketplaceCommandTest extends TestCase
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

    public function test_inspect_marketplace_returns_empty_deterministic_payload_when_storage_missing(): void
    {
        $result = $this->runCommand(new Application(), ['foundry', 'inspect', 'marketplace', '--json']);

        $this->assertSame(0, $result['status']);
        $this->assertSame('ok', $result['payload']['status']);
        $this->assertSame('.foundry/marketplace', $result['payload']['storage']['root']);
        $this->assertSame('.foundry/marketplace/packs.json', $result['payload']['storage']['index']);
        $this->assertFalse($result['payload']['auth']['configured']);
        $this->assertFalse($result['payload']['auth']['authenticated']);
        $this->assertSame('unauthenticated', $result['payload']['auth']['status']);
        $this->assertFalse($result['payload']['auth']['token']['present']);
        $this->assertSame([], $result['payload']['packs']);
        $this->assertSame(['packs' => 0, 'versions' => 0, 'artifacts' => 0], $result['payload']['totals']);
    }

    public function test_inspect_marketplace_and_verify_marketplace_pass_on_valid_fixture(): void
    {
        $this->writeFixture(validChecksum: true);
        $app = new Application();

        $inspect = $this->runCommand($app, ['foundry', 'inspect', 'marketplace', '--json']);
        $verify = $this->runCommand($app, ['foundry', 'verify', 'marketplace', '--json']);

        $this->assertSame(0, $inspect['status']);
        $this->assertSame('vendor/example-pack', $inspect['payload']['packs'][0]['name']);
        $this->assertFalse($inspect['payload']['auth']['configured']);
        $this->assertFalse($inspect['payload']['auth']['authenticated']);
        $this->assertSame(0, $verify['status']);
        $this->assertSame('pass', $verify['payload']['status']);
        $this->assertFalse($verify['payload']['auth']['configured']);
        $this->assertSame([], $verify['payload']['errors']);
        $this->assertSame(['packs' => 1, 'versions' => 1, 'artifacts' => 1], $verify['payload']['checked']);
    }

    public function test_verify_marketplace_fails_with_stable_error_code_when_artifact_missing(): void
    {
        $this->writeFixture(validChecksum: true);
        unlink($this->project->root . '/.foundry/marketplace/artifacts/vendor__example-pack/1.0.0/pack.zip');

        $verify = $this->runCommand(new Application(), ['foundry', 'verify', 'marketplace', '--json']);

        $this->assertSame(1, $verify['status']);
        $this->assertSame('fail', $verify['payload']['status']);
        $this->assertSame('PACK_ARTIFACT_MISSING', $verify['payload']['errors'][0]['code']);
    }

    public function test_verify_marketplace_fails_with_stable_error_code_when_checksum_mismatches(): void
    {
        $this->writeFixture(validChecksum: false);

        $verify = $this->runCommand(new Application(), ['foundry', 'verify', 'marketplace', '--json']);

        $this->assertSame(1, $verify['status']);
        $this->assertSame('fail', $verify['payload']['status']);
        $this->assertSame('PACK_ARTIFACT_CHECKSUM_MISMATCH', $verify['payload']['errors'][0]['code']);
    }

    public function test_inspect_and_verify_marketplace_report_invalid_auth_state_without_leaking_secrets(): void
    {
        $this->writeFixture(validChecksum: true);
        $path = $this->project->root . '/.foundry/marketplace/identity.json';
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, "{\"broken\":true}\n");

        $inspect = $this->runCommand(new Application(), ['foundry', 'inspect', 'marketplace', '--json']);
        $verify = $this->runCommand(new Application(), ['foundry', 'verify', 'marketplace', '--json']);

        $this->assertSame(0, $inspect['status']);
        $this->assertSame('invalid', $inspect['payload']['auth']['status']);
        $this->assertSame('MARKETPLACE_AUTH_STATE_INVALID', $inspect['payload']['auth']['code']);
        $this->assertFalse($inspect['payload']['auth']['authenticated']);
        $this->assertArrayNotHasKey('access_token', $inspect['payload']['auth']);

        $this->assertSame(1, $verify['status']);
        $this->assertSame('fail', $verify['payload']['status']);
        $this->assertSame('fail', $verify['payload']['auth']['status']);
        $this->assertSame('MARKETPLACE_AUTH_STATE_INVALID', $verify['payload']['auth']['code']);
        $this->assertContains('MARKETPLACE_AUTH_STATE_INVALID', array_values(array_map(
            static fn(array $error): string => (string) ($error['code'] ?? ''),
            $verify['payload']['errors'],
        )));
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

    private function writeFixture(bool $validChecksum): void
    {
        $artifactRelative = 'artifacts/vendor__example-pack/1.0.0/pack.zip';
        $artifactAbsolute = $this->project->root . '/.foundry/marketplace/' . $artifactRelative;
        if (!is_dir(dirname($artifactAbsolute))) {
            mkdir(dirname($artifactAbsolute), 0777, true);
        }
        file_put_contents($artifactAbsolute, 'fixture-zip');

        $checksum = $validChecksum ? hash_file('sha256', $artifactAbsolute) : str_repeat('a', 64);

        $payload = [
            'packs' => [[
                'name' => 'vendor/example-pack',
                'display_name' => 'Example Pack',
                'description' => 'A short pack description.',
                'vendor' => 'vendor',
                'latest_version' => '1.0.0',
                'versions' => [[
                    'version' => '1.0.0',
                    'requires_foundry' => '>=0.1.0',
                    'artifact' => $artifactRelative,
                    'sha256' => $checksum,
                    'published_at' => '2026-01-01T00:00:00Z',
                    'metadata' => ['homepage' => null, 'license' => null, 'tags' => []],
                ]],
                'metadata' => ['homepage' => null, 'license' => null, 'tags' => []],
            ]],
        ];

        $index = $this->project->root . '/.foundry/marketplace/packs.json';
        if (!is_dir(dirname($index))) {
            mkdir(dirname($index), 0777, true);
        }
        file_put_contents($index, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");
    }
}
