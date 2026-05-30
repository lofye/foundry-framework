<?php

declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\Examples\ExampleLoader;
use Foundry\Support\FoundryError;
use Foundry\Support\Paths;
use PHPUnit\Framework\TestCase;

final class ExampleLoaderTest extends TestCase
{
    public function test_available_examples_expose_truthful_onboarding_metadata(): void
    {
        $loader = new ExampleLoader(Paths::fromCwd(dirname(__DIR__, 2)));

        $examples = $loader->available();

        $this->assertCount(2, $examples);

        $blog = $examples[0];
        $this->assertSame('blog-api', $blog['name']);
        $this->assertSame('canonical', $blog['taxonomy']);
        $this->assertSame('direct_copy', $blog['mode']);
        $this->assertSame(['blog-api'], $blog['source_examples']);
        $this->assertSame(['examples/blog-api'], $blog['source_paths']);
        $this->assertSame('feature:list-posts', $blog['explain_default_target']);
        $this->assertTrue($blog['recommended']);

        $extensions = $examples[1];
        $this->assertSame('extensions-migrations', $extensions['name']);
        $this->assertSame('reference', $extensions['taxonomy']);
        $this->assertSame('composed', $extensions['mode']);
        $this->assertSame(['hello-world', 'extensions-migrations'], $extensions['source_examples']);
        $this->assertSame(['examples/hello-world', 'examples/extensions-migrations'], $extensions['source_paths']);
        $this->assertSame('feature:say-hello', $extensions['explain_default_target']);
        $this->assertFalse($extensions['recommended']);
    }

    public function test_blog_alias_resolves_to_the_blog_api_example(): void
    {
        $loader = new ExampleLoader(Paths::fromCwd(dirname(__DIR__, 2)));
        $target = $this->makeTempDirectory('foundry-example-loader-');

        try {
            $result = $loader->load('blog', $target);

            $this->assertSame('blog-api', $result['example']['name']);
            $this->assertSame('working_directory', $result['workspace_mode']);
            $this->assertFileExists($target . '/Features/ListPosts/feature.yaml');
        } finally {
            $this->deleteDirectory($target);
        }
    }

    public function test_recommended_returns_the_canonical_blog_example(): void
    {
        $loader = new ExampleLoader(Paths::fromCwd(dirname(__DIR__, 2)));

        $recommended = $loader->recommended();

        $this->assertSame('blog-api', $recommended['name']);
        $this->assertTrue($recommended['recommended']);
        $this->assertSame('feature:list-posts', $recommended['explain_default_target']);
    }

    public function test_load_rejects_unknown_example_name(): void
    {
        $loader = new ExampleLoader(Paths::fromCwd(dirname(__DIR__, 2)));
        $target = $this->makeTempDirectory('foundry-example-loader-');

        try {
            $this->expectException(FoundryError::class);
            $this->expectExceptionMessage('Example not found');

            $loader->load('nope', $target);
        } finally {
            $this->deleteDirectory($target);
        }
    }

    public function test_load_refuses_non_empty_working_directory_without_temp_mode(): void
    {
        $loader = new ExampleLoader(Paths::fromCwd(dirname(__DIR__, 2)));
        $target = $this->makeTempDirectory('foundry-example-loader-');
        file_put_contents($target . '/existing.txt', "already here\n");

        try {
            $this->expectException(FoundryError::class);
            $this->expectExceptionMessage('The current directory is not empty');

            $loader->load('blog', $target);
        } finally {
            $this->deleteDirectory($target);
        }
    }

    public function test_load_creates_missing_working_directory(): void
    {
        $loader = new ExampleLoader(Paths::fromCwd(dirname(__DIR__, 2)));
        $target = $this->makeTempDirectory('foundry-example-loader-') . '/nested';

        try {
            $result = $loader->load('blog', $target);

            $this->assertSame('working_directory', $result['workspace_mode']);
            $this->assertFileExists($target . '/Features/ListPosts/feature.yaml');
        } finally {
            $this->deleteDirectory(dirname($target));
        }
    }

    public function test_load_reports_missing_example_source(): void
    {
        $frameworkRoot = $this->makeTempDirectory('foundry-example-loader-empty-root-');
        $target = $this->makeTempDirectory('foundry-example-loader-');
        $loader = new ExampleLoader(new Paths($frameworkRoot, $frameworkRoot));

        try {
            $this->expectException(FoundryError::class);
            $this->expectExceptionMessage('Example source is missing');

            $loader->load('blog', $target);
        } finally {
            $this->deleteDirectory($frameworkRoot);
            $this->deleteDirectory($target);
        }
    }

    public function test_load_extensions_example_into_temp_workspace_composes_copy_sets(): void
    {
        $loader = new ExampleLoader(Paths::fromCwd(dirname(__DIR__, 2)));
        $ignoredWorkingDirectory = $this->makeTempDirectory('foundry-example-loader-');

        try {
            $result = $loader->load('extensions', $ignoredWorkingDirectory, preferTemp: true);
            $target = $result['target_path'];

            $this->assertSame('extensions-migrations', $result['example']['name']);
            $this->assertSame('temp_directory', $result['workspace_mode']);
            $this->assertNotSame($ignoredWorkingDirectory, $target);
            $this->assertFileExists($target . '/Features/SayHello/feature.yaml');
            $this->assertFileExists($target . '/foundry.extensions.php');
            $this->assertFileExists($target . '/demo_extension/DemoCapabilityExtension.php');
            $this->assertContains($target . '/README.md', $result['files_copied']);
        } finally {
            if (isset($target) && is_string($target)) {
                $this->deleteDirectory($target);
            }
            $this->deleteDirectory($ignoredWorkingDirectory);
        }
    }

    private function makeTempDirectory(string $prefix): string
    {
        $path = tempnam(sys_get_temp_dir(), $prefix);
        self::assertIsString($path);
        @unlink($path);
        mkdir($path, 0777, true);

        return str_replace('\\', '/', $path);
    }

    private function deleteDirectory(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        /** @var \SplFileInfo $item */
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
                continue;
            }

            @unlink($item->getPathname());
        }

        @rmdir($path);
    }
}
