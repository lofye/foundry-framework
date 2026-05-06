<?php

declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\Marketplace\MarketplaceHttpController;
use Foundry\Marketplace\MarketplaceRepository;
use Foundry\Support\Paths;
use Foundry\Tests\Fixtures\TempProject;
use PHPUnit\Framework\TestCase;

final class MarketplaceHttpControllerTest extends TestCase
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

    public function test_get_packs_and_get_pack_return_deterministic_payloads(): void
    {
        $this->writeFixture();
        $controller = $this->controller();

        $list = $controller->listPacks();
        $inspect = $controller->getPack('vendor/example-pack');

        $this->assertSame(200, $list['status_code']);
        $this->assertSame('ok', $list['payload']['status']);
        $this->assertSame('vendor/example-pack', $list['payload']['packs'][0]['name']);

        $this->assertSame(200, $inspect['status_code']);
        $this->assertSame('ok', $inspect['payload']['status']);
        $this->assertSame('/packs/vendor/example-pack/1.0.0/download', $inspect['payload']['pack']['versions'][0]['download_url']);
    }

    public function test_get_pack_not_found_and_invalid_name_errors_are_deterministic(): void
    {
        $controller = $this->controller();

        $missing = $controller->getPack('vendor/missing');
        $invalid = $controller->getPack('../oops');

        $this->assertSame(404, $missing['status_code']);
        $this->assertSame('PACK_NOT_FOUND', $missing['payload']['error']['code']);
        $this->assertSame(400, $invalid['status_code']);
        $this->assertSame('PACK_INVALID_NAME', $invalid['payload']['error']['code']);
    }

    public function test_download_returns_artifact_with_required_headers_and_error_paths(): void
    {
        $this->writeFixture();
        $controller = $this->controller();

        $ok = $controller->downloadPack('vendor/example-pack', '1.0.0');
        $missingPack = $controller->downloadPack('vendor/missing', '1.0.0');
        $missingVersion = $controller->downloadPack('vendor/example-pack', '9.9.9');
        $invalidVersion = $controller->downloadPack('vendor/example-pack', '../bad');

        $this->assertSame(200, $ok['status_code']);
        $this->assertSame('application/zip', $ok['headers']['Content-Type']);
        $this->assertSame('vendor/example-pack', $ok['headers']['X-Foundry-Pack-Name']);
        $this->assertSame('1.0.0', $ok['headers']['X-Foundry-Pack-Version']);
        $this->assertStringContainsString('vendor__example-pack-1.0.0.zip', $ok['headers']['Content-Disposition']);
        $this->assertNotSame('', $ok['body']);

        $this->assertSame(404, $missingPack['status_code']);
        $this->assertSame('PACK_NOT_FOUND', $missingPack['payload']['error']['code']);
        $this->assertSame(404, $missingVersion['status_code']);
        $this->assertSame('PACK_VERSION_NOT_FOUND', $missingVersion['payload']['error']['code']);
        $this->assertSame(400, $invalidVersion['status_code']);
        $this->assertSame('PACK_INVALID_VERSION', $invalidVersion['payload']['error']['code']);
    }

    public function test_download_detects_missing_and_checksum_mismatch_artifacts(): void
    {
        $this->writeFixture();
        $controller = $this->controller();
        $artifact = $this->project->root . '/.foundry/marketplace/artifacts/vendor__example-pack/1.0.0/pack.zip';

        unlink($artifact);
        $missing = $controller->downloadPack('vendor/example-pack', '1.0.0');
        $this->assertSame(410, $missing['status_code']);
        $this->assertSame('PACK_ARTIFACT_MISSING', $missing['payload']['error']['code']);

        $this->writeFixture();
        file_put_contents($artifact, 'tampered');
        $checksumMismatch = $controller->downloadPack('vendor/example-pack', '1.0.0');
        $this->assertSame(500, $checksumMismatch['status_code']);
        $this->assertSame('PACK_ARTIFACT_CHECKSUM_MISMATCH', $checksumMismatch['payload']['error']['code']);
    }

    private function controller(): MarketplaceHttpController
    {
        return new MarketplaceHttpController(new MarketplaceRepository(new Paths($this->project->root)));
    }

    private function writeFixture(): void
    {
        $artifactRelative = 'artifacts/vendor__example-pack/1.0.0/pack.zip';
        $artifactAbsolute = $this->project->root . '/.foundry/marketplace/' . $artifactRelative;
        if (!is_dir(dirname($artifactAbsolute))) {
            mkdir(dirname($artifactAbsolute), 0777, true);
        }
        file_put_contents($artifactAbsolute, 'fixture-zip');

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
                    'sha256' => hash_file('sha256', $artifactAbsolute),
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

