<?php

declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\Marketplace\MarketplaceRepository;
use Foundry\Support\FoundryError;
use Foundry\Support\Paths;
use Foundry\Tests\Fixtures\TempProject;
use PHPUnit\Framework\TestCase;

final class MarketplaceRepositoryTest extends TestCase
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

    public function test_load_returns_empty_index_when_missing(): void
    {
        $repository = $this->repository();

        $index = $repository->load();
        $inspect = $repository->inspect();

        $this->assertSame([], $index->packs);
        $this->assertSame('ok', $inspect['status']);
        $this->assertSame([], $inspect['packs']);
        $this->assertSame(['packs' => 0, 'versions' => 0, 'artifacts' => 0], $inspect['totals']);
    }

    public function test_load_sorts_packs_and_versions_deterministically(): void
    {
        $this->writePackFixture(
            name: 'vendor/zeta-pack',
            versions: ['1.0.0', '2.0.0'],
            latestVersion: '2.0.0',
        );
        $this->writePackFixture(
            name: 'vendor/alpha-pack',
            versions: ['1.0.0'],
            latestVersion: '1.0.0',
            append: true,
        );

        $index = $this->repository()->load();

        $this->assertSame(['vendor/alpha-pack', 'vendor/zeta-pack'], array_map(
            static fn(object $pack): string => $pack->name,
            $index->packs,
        ));
        $this->assertSame(['2.0.0', '1.0.0'], array_map(
            static fn(object $version): string => $version->version,
            $index->packs[1]->versions,
        ));
    }

    public function test_load_rejects_duplicate_pack_names(): void
    {
        $this->writeRawIndex([
            'packs' => [
                $this->packRow('vendor/example-pack', ['1.0.0'], '1.0.0'),
                $this->packRow('vendor/example-pack', ['1.0.1'], '1.0.1'),
            ],
        ]);

        $this->expectException(FoundryError::class);
        $this->expectExceptionMessage('Marketplace pack names must be unique.');
        $this->repository()->load();
    }

    public function test_load_rejects_duplicate_versions(): void
    {
        $this->writeRawIndex([
            'packs' => [
                [
                    'name' => 'vendor/example-pack',
                    'display_name' => 'Example',
                    'description' => 'Example',
                    'vendor' => 'vendor',
                    'latest_version' => '1.0.0',
                    'versions' => [
                        $this->versionRow('vendor/example-pack', '1.0.0'),
                        $this->versionRow('vendor/example-pack', '1.0.0'),
                    ],
                    'metadata' => ['homepage' => null, 'license' => null, 'tags' => []],
                ],
            ],
        ]);

        $this->expectException(FoundryError::class);
        $this->expectExceptionMessage('Marketplace pack versions must be unique.');
        $this->repository()->load();
    }

    public function test_load_rejects_invalid_pack_name_and_artifact_path_traversal(): void
    {
        $this->writeRawIndex([
            'packs' => [[
                'name' => '../oops',
                'display_name' => 'Bad',
                'description' => 'Bad',
                'vendor' => 'bad',
                'latest_version' => '1.0.0',
                'versions' => [[
                    'version' => '1.0.0',
                    'requires_foundry' => '>=0.1.0',
                    'artifact' => '../bad.zip',
                    'sha256' => str_repeat('a', 64),
                    'published_at' => '2026-01-01T00:00:00Z',
                    'metadata' => ['homepage' => null, 'license' => null, 'tags' => []],
                ]],
                'metadata' => ['homepage' => null, 'license' => null, 'tags' => []],
            ]],
        ]);

        $this->expectException(FoundryError::class);
        $this->expectExceptionMessage('Marketplace pack name is invalid.');
        $this->repository()->load();
    }

    public function test_safe_pack_key_and_name_validation_are_deterministic(): void
    {
        $this->assertSame('vendor__example-pack', MarketplaceRepository::safePackKey('vendor/example-pack'));
        $this->assertTrue(MarketplaceRepository::validPackName('vendor/example-pack'));
        $this->assertFalse(MarketplaceRepository::validPackName('Vendor/Example'));
        $this->assertFalse(MarketplaceRepository::validPackName('../oops'));
    }

    private function repository(): MarketplaceRepository
    {
        return new MarketplaceRepository(new Paths($this->project->root));
    }

    /**
     * @param array<int,string> $versions
     */
    private function writePackFixture(string $name, array $versions, string $latestVersion, bool $append = false): void
    {
        $existing = ['packs' => []];
        $indexPath = $this->project->root . '/.foundry/marketplace/packs.json';
        if ($append && is_file($indexPath)) {
            $decoded = json_decode((string) file_get_contents($indexPath), true);
            if (is_array($decoded)) {
                $existing = $decoded;
            }
        }

        $existing['packs'][] = $this->packRow($name, $versions, $latestVersion);
        $this->writeRawIndex($existing);
    }

    /**
     * @param array<int,string> $versions
     * @return array<string,mixed>
     */
    private function packRow(string $name, array $versions, string $latestVersion): array
    {
        return [
            'name' => $name,
            'display_name' => 'Example Pack',
            'description' => 'Example',
            'vendor' => str_contains($name, '/') ? explode('/', $name)[0] : $name,
            'latest_version' => $latestVersion,
            'versions' => array_map(fn(string $version): array => $this->versionRow($name, $version), $versions),
            'metadata' => ['homepage' => null, 'license' => null, 'tags' => ['z', 'a']],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function versionRow(string $name, string $version): array
    {
        $safe = MarketplaceRepository::safePackKey($name);
        $artifactRelative = 'artifacts/' . $safe . '/' . $version . '/pack.zip';
        $artifactAbsolute = $this->project->root . '/.foundry/marketplace/' . $artifactRelative;
        if (!is_dir(dirname($artifactAbsolute))) {
            mkdir(dirname($artifactAbsolute), 0777, true);
        }

        file_put_contents($artifactAbsolute, 'zip-' . $name . '-' . $version);

        return [
            'version' => $version,
            'requires_foundry' => '>=0.1.0',
            'artifact' => $artifactRelative,
            'sha256' => hash_file('sha256', $artifactAbsolute),
            'published_at' => '2026-01-01T00:00:00Z',
            'metadata' => ['homepage' => null, 'license' => null, 'tags' => ['z', 'a']],
        ];
    }

    /**
     * @param array<string,mixed> $payload
     */
    private function writeRawIndex(array $payload): void
    {
        $path = $this->project->root . '/.foundry/marketplace/packs.json';
        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR) . "\n");
    }
}

