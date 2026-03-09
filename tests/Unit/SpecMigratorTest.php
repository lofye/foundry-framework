<?php
declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\Compiler\Migration\FeatureManifestV2Rule;
use Foundry\Compiler\Migration\ManifestVersionResolver;
use Foundry\Compiler\Migration\SpecMigrator;
use Foundry\Support\Paths;
use Foundry\Support\Yaml;
use Foundry\Tests\Fixtures\TempProject;
use PHPUnit\Framework\TestCase;

final class SpecMigratorTest extends TestCase
{
    private TempProject $project;

    protected function setUp(): void
    {
        $this->project = new TempProject();

        $feature = $this->project->root . '/app/features/publish_post';
        mkdir($feature, 0777, true);

        file_put_contents($feature . '/feature.yaml', <<<'YAML'
version: 1
feature: publish_post
kind: http
route:
  method: post
  path: /posts
auth:
  strategy: bearer
  permissions: [posts.create]
llm:
  editable: true
  risk: medium
YAML);
    }

    protected function tearDown(): void
    {
        $this->project->cleanup();
    }

    public function test_migrate_specs_dry_run_and_write_modes(): void
    {
        $migrator = new SpecMigrator(
            Paths::fromCwd($this->project->root),
            new ManifestVersionResolver(),
            [new FeatureManifestV2Rule()],
        );

        $dryRun = $migrator->migrate(false);
        $this->assertFalse($dryRun->written);
        $this->assertCount(1, $dryRun->changes);
        $this->assertSame('FDY_MIGRATE_FEATURE_MANIFEST_V2', $dryRun->changes[0]['rules'][0]);

        $write = $migrator->migrate(true);
        $this->assertTrue($write->written);
        $this->assertCount(1, $write->changes);

        $manifest = Yaml::parseFile($this->project->root . '/app/features/publish_post/feature.yaml');
        $this->assertSame(2, $manifest['version']);
        $this->assertSame(['bearer'], $manifest['auth']['strategies']);
        $this->assertSame('POST', $manifest['route']['method']);
        $this->assertSame('medium', $manifest['llm']['risk_level']);
    }

    public function test_inspect_returns_rule_metadata(): void
    {
        $migrator = new SpecMigrator(
            Paths::fromCwd($this->project->root),
            new ManifestVersionResolver(),
            [new FeatureManifestV2Rule()],
        );

        $rules = $migrator->inspect();
        $this->assertCount(1, $rules);
        $this->assertSame('FDY_MIGRATE_FEATURE_MANIFEST_V2', $rules[0]['id']);
    }
}
