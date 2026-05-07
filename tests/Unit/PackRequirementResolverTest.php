<?php

declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\Compiler\Extensions\PackDefinition;
use Foundry\Compiler\Extensions\PackRegistry;
use Foundry\Generate\Intent;
use Foundry\Generate\PackRequirementResolver;
use Foundry\Marketplace\MarketplaceEntitlementCache;
use Foundry\Marketplace\PackEntitlementResolver;
use Foundry\Packs\HostedPackRegistry;
use Foundry\Support\Paths;
use Foundry\Tests\Fixtures\TempProject;
use PHPUnit\Framework\TestCase;

final class PackRequirementResolverTest extends TestCase
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

    public function test_resolver_reports_missing_hinted_pack(): void
    {
        $resolver = new PackRequirementResolver();
        $result = $resolver->resolve(
            new Intent(raw: 'Create blog post notes', mode: 'new', packHints: ['foundry/blog']),
            new PackRegistry(),
        );

        $this->assertSame(['pack:foundry/blog'], $result['missing_capabilities']);
        $this->assertSame(['foundry/blog'], $result['suggested_packs']);
    }

    public function test_resolver_ignores_installed_hinted_pack(): void
    {
        $resolver = new PackRequirementResolver();
        $result = $resolver->resolve(
            new Intent(raw: 'Create blog post notes', mode: 'new', packHints: ['foundry/blog']),
            new PackRegistry([
                new PackDefinition(
                    name: 'foundry/blog',
                    version: '1.0.0',
                    extension: 'pack.foundry.blog',
                    providedCapabilities: ['blog.notes'],
                ),
            ]),
        );

        $this->assertSame([], $result['missing_capabilities']);
        $this->assertSame([], $result['suggested_packs']);
    }

    public function test_resolver_surfaces_marketplace_entitlement_summary_for_missing_packs(): void
    {
        $this->writeEntitlements([
            [
                'pack' => 'foundry/premium-pack',
                'type' => 'premium',
                'status' => 'granted',
                'expires_at' => null,
                'source' => 'marketplace',
                'granted_at' => '2026-01-01T00:00:00Z',
            ],
            [
                'pack' => 'foundry/expired-pack',
                'type' => 'premium',
                'status' => 'granted',
                'expires_at' => '2025-01-01T00:00:00Z',
                'source' => 'marketplace',
                'granted_at' => '2024-01-01T00:00:00Z',
            ],
        ]);
        $resolver = new PackRequirementResolver(
            hostedRegistry: $this->registry([
                $this->entry('foundry/premium-pack', 'premium', true),
                $this->entry('foundry/free-pack', 'free', false),
                $this->entry('foundry/expired-pack', 'premium', true),
            ]),
            entitlementResolver: new PackEntitlementResolver(new MarketplaceEntitlementCache(Paths::fromCwd($this->project->root))),
        );

        $result = $resolver->resolve(
            new Intent(raw: 'Create premium flow', mode: 'new', packHints: ['foundry/premium-pack', 'foundry/free-pack', 'foundry/expired-pack']),
            new PackRegistry(),
        );

        $this->assertSame('blocked_expired_entitlement', $result['execution_state']);
        $this->assertSame(['foundry/expired-pack', 'foundry/premium-pack'], $result['entitlements']['required']);
        $this->assertSame(['foundry/premium-pack'], $result['entitlements']['granted']);
        $this->assertSame(['foundry/expired-pack'], $result['entitlements']['expired']);
        $this->assertSame([], $result['entitlements']['missing']);
        $this->assertSame([], $result['entitlements']['unknown']);
        $this->assertSame([], $result['errors']);
    }

    public function test_resolver_marks_unknown_marketplace_packs_invalid(): void
    {
        $resolver = new PackRequirementResolver(
            hostedRegistry: $this->registry([]),
            entitlementResolver: new PackEntitlementResolver(new MarketplaceEntitlementCache(Paths::fromCwd($this->project->root))),
        );

        $result = $resolver->resolve(
            new Intent(raw: 'Create unknown flow', mode: 'new', packHints: ['foundry/missing-pack']),
            new PackRegistry(),
        );

        $this->assertSame('invalid', $result['execution_state']);
        $this->assertSame(['foundry/missing-pack'], $result['entitlements']['required']);
        $this->assertSame(['foundry/missing-pack'], $result['entitlements']['unknown']);
        $this->assertSame('MARKETPLACE_PACK_NOT_AVAILABLE', $result['errors'][0]['code']);
    }

    /**
     * @param list<array<string,mixed>> $rows
     */
    private function writeEntitlements(array $rows): void
    {
        $path = $this->project->root . '/.foundry/marketplace/entitlements.json';
        @mkdir(dirname($path), 0777, true);
        file_put_contents($path, json_encode([
            'entitlements' => $rows,
            'updated_at' => '2026-01-01T00:00:00Z',
        ], JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n");
    }

    /**
     * @param list<array<string,mixed>> $entries
     */
    private function registry(array $entries): HostedPackRegistry
    {
        return new HostedPackRegistry(
            Paths::fromCwd($this->project->root),
            static fn(string $url): string => json_encode($entries, JSON_THROW_ON_ERROR),
            'https://registry.example/packs',
        );
    }

    /**
     * @return array<string,mixed>
     */
    private function entry(string $name, string $distribution, bool $required): array
    {
        return [
            'name' => $name,
            'version' => '1.0.0',
            'description' => 'Marketplace pack',
            'download_url' => 'https://downloads.example/' . str_replace('/', '-', $name) . '.zip',
            'checksum' => str_repeat('a', 64),
            'signature' => null,
            'verified' => true,
            'distribution' => $distribution,
            'entitlement_required' => $required,
            'price' => $distribution === 'free' ? null : ['currency' => 'CAD', 'amount' => '49.00'],
        ];
    }
}
