<?php

declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\Compiler\SourceScanner;
use Foundry\Support\Paths;
use Foundry\Tests\Fixtures\TempProject;
use PHPUnit\Framework\TestCase;

final class SourceScannerTest extends TestCase
{
    private TempProject $project;
    private SourceScanner $scanner;

    protected function setUp(): void
    {
        $this->project = new TempProject();
        $this->scanner = new SourceScanner(Paths::fromCwd($this->project->root));
    }

    protected function tearDown(): void
    {
        $this->project->cleanup();
    }

    public function test_source_files_hashes_feature_detection_and_changed_files_are_deterministic(): void
    {
        $featureBase = $this->project->root . '/Features/PublishPost';
        mkdir($featureBase . '/src', 0777, true);
        mkdir($featureBase . '/tests', 0777, true);
        mkdir($this->project->root . '/config', 0777, true);
        mkdir($this->project->root . '/bootstrap', 0777, true);
        mkdir($this->project->root . '/config/foundry', 0777, true);
        mkdir($this->project->root . '/app/definitions/http', 0777, true);

        file_put_contents($featureBase . '/feature.yaml', "version: 2\nfeature: publish-post\n");
        file_put_contents($featureBase . '/input.schema.json', '{"type":"object"}');
        file_put_contents($featureBase . '/output.schema.json', '{"type":"object"}');
        file_put_contents($featureBase . '/src/Action.php', '<?php declare(strict_types=1);');
        file_put_contents($featureBase . '/tests/publish_post_feature_test.php', '<?php declare(strict_types=1);');
        file_put_contents($featureBase . '/context.manifest.json', '{"version":1,"feature":"publish-post","kind":"http"}');
        file_put_contents($this->project->root . '/config/cache.yaml', "version: 1\n");
        file_put_contents($this->project->root . '/config/runtime.php', '<?php return [];');
        file_put_contents($this->project->root . '/bootstrap/app.php', '<?php declare(strict_types=1);');
        file_put_contents($this->project->root . '/foundry.extensions.php', '<?php return [];');
        file_put_contents($this->project->root . '/config/foundry/extensions.php', '<?php return [];');
        file_put_contents($this->project->root . '/app/definitions/http/feature_manifest.yaml', "version: 1\n");

        $files = $this->scanner->sourceFiles();

        $this->assertSame([
            'Features/PublishPost/context.manifest.json',
            'Features/PublishPost/feature.yaml',
            'Features/PublishPost/input.schema.json',
            'Features/PublishPost/output.schema.json',
            'Features/PublishPost/src/Action.php',
            'Features/PublishPost/tests/publish_post_feature_test.php',
            'app/definitions/http/feature_manifest.yaml',
            'bootstrap/app.php',
            'config/cache.yaml',
            'config/foundry/extensions.php',
            'config/runtime.php',
            'foundry.extensions.php',
        ], $files);

        $hashes = $this->scanner->hashFiles(array_merge($files, ['Features/PublishPost/missing.php']));
        $this->assertArrayNotHasKey('Features/PublishPost/missing.php', $hashes);
        $this->assertSame(array_keys($hashes), $files);
        $this->assertSame(
            $this->scanner->aggregateHash($hashes),
            $this->scanner->aggregateHash(array_reverse($hashes, true)),
        );

        $this->assertSame('publish-post', $this->scanner->featureFromPath('Features/PublishPost/src/Action.php'));
        $this->assertNull($this->scanner->featureFromPath('Features/'));
        $this->assertNull($this->scanner->featureFromPath('app/generated/routes.php'));

        $this->assertSame(
            ['Features/PublishPost/new.php', 'Features/PublishPost/removed.php', 'Features/PublishPost/src/Action.php'],
            $this->scanner->changedFiles(
                [
                    'Features/PublishPost/src/Action.php' => 'old',
                    'Features/PublishPost/removed.php' => 'gone',
                ],
                [
                    'Features/PublishPost/src/Action.php' => 'new',
                    'Features/PublishPost/new.php' => 'added',
                ],
            ),
        );
    }
}
