<?php

declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\Marketplace\MarketplaceRepository;
use Foundry\Marketplace\MarketplaceVerifier;
use Foundry\Support\Paths;
use Foundry\Tests\Fixtures\TempProject;
use PHPUnit\Framework\TestCase;

final class MarketplaceVerifierTest extends TestCase
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

    public function test_verify_merges_repository_auth_and_entitlement_errors_when_index_is_invalid(): void
    {
        $this->writeInvalidIndex();
        $this->writeInvalidAuthState();
        $this->writeInvalidEntitlementCache();

        $payload = $this->verifier()->verify();
        $codes = array_values(array_map(
            static fn(array $row): string => (string) ($row['code'] ?? ''),
            $payload['errors'],
        ));

        $this->assertSame('fail', $payload['status']);
        $this->assertContains('MARKETPLACE_INDEX_INVALID_JSON', $codes);
        $this->assertContains('MARKETPLACE_AUTH_STATE_INVALID', $codes);
        $this->assertContains('MARKETPLACE_ENTITLEMENT_CACHE_INVALID', $codes);
        $this->assertSame('fail', $payload['auth']['status']);
        $this->assertSame('fail', $payload['entitlements']['status']);
    }

    public function test_verify_sorts_errors_deterministically_when_artifacts_are_missing(): void
    {
        $this->writePackWithMissingArtifact('vendor/z-pack');
        $this->writePackWithMissingArtifact('vendor/a-pack', append: true);

        $payload = $this->verifier()->verify();
        $errors = $payload['errors'];

        $this->assertSame('fail', $payload['status']);
        $this->assertCount(2, $errors);
        $this->assertSame('vendor/a-pack', $errors[0]['details']['name']);
        $this->assertSame('vendor/z-pack', $errors[1]['details']['name']);
    }

    private function verifier(): MarketplaceVerifier
    {
        return new MarketplaceVerifier(new MarketplaceRepository(new Paths($this->project->root)));
    }

    private function writeInvalidIndex(): void
    {
        $path = $this->project->root . '/.foundry/marketplace/packs.json';
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, '{bad-json}' . PHP_EOL);
    }

    private function writeInvalidAuthState(): void
    {
        $path = $this->project->root . '/.foundry/marketplace/identity.json';
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, '{bad-json}' . PHP_EOL);
    }

    private function writeInvalidEntitlementCache(): void
    {
        $path = $this->project->root . '/.foundry/marketplace/entitlements.json';
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, '{bad-json}' . PHP_EOL);
    }

    private function writePackWithMissingArtifact(string $name, bool $append = false): void
    {
        $path = $this->project->root . '/.foundry/marketplace/packs.json';
        $payload = ['packs' => []];

        if ($append && is_file($path)) {
            $decoded = json_decode((string) file_get_contents($path), true);
            if (is_array($decoded)) {
                $payload = $decoded;
            }
        }

        $safe = str_replace('/', '__', $name);
        $payload['packs'][] = [
            'name' => $name,
            'display_name' => 'Example',
            'description' => 'Example',
            'vendor' => 'vendor',
            'latest_version' => '1.0.0',
            'versions' => [[
                'version' => '1.0.0',
                'requires_foundry' => '>=0.1.0',
                'artifact' => 'artifacts/' . $safe . '/1.0.0/pack.zip',
                'sha256' => str_repeat('a', 64),
                'published_at' => '2026-01-01T00:00:00Z',
                'metadata' => ['distribution' => 'free', 'entitlement_required' => false, 'homepage' => null, 'license' => null, 'tags' => []],
            ]],
            'metadata' => ['distribution' => 'free', 'entitlement_required' => false, 'homepage' => null, 'license' => null, 'tags' => []],
        ];

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }
        file_put_contents($path, json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL);
    }
}
