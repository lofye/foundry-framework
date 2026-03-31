<?php

declare(strict_types=1);

namespace Foundry\Examples;

use Foundry\Support\FoundryError;
use Foundry\Support\Paths;

final class ExampleLoader
{
    public function __construct(private readonly ?Paths $paths = null) {}

    /**
     * @return array<int,array<string,mixed>>
     */
    public function available(): array
    {
        return array_values(array_map(
            fn(array $definition): array => $this->publicDefinition($definition),
            $this->definitions(),
        ));
    }

    /**
     * @return array<string,mixed>
     */
    public function load(string $name, string $workingDirectory, bool $preferTemp = false): array
    {
        $definition = $this->definition($name);
        $workingDirectory = $this->normalizePath($workingDirectory);

        $targetPath = $preferTemp
            ? $this->createTempDirectory((string) $definition['name'])
            : $workingDirectory;

        if (!$preferTemp && !$this->isSafeWorkingDirectory($targetPath)) {
            throw new FoundryError(
                'EXAMPLE_TARGET_NOT_EMPTY',
                'validation',
                ['target_path' => $targetPath, 'example' => (string) $definition['name']],
                'The current directory is not empty. Use `foundry init --example=' . (string) $definition['name'] . ' --temp` to load into a temporary workspace.',
            );
        }

        $copied = [];
        foreach ((array) ($definition['copy_sets'] ?? []) as $copySet) {
            if (!is_array($copySet)) {
                continue;
            }

            $source = $this->normalizePath((string) ($copySet['source'] ?? ''));
            $destination = $this->destinationPath(
                $targetPath,
                (string) ($copySet['destination'] ?? ''),
            );

            foreach ($this->copyInto($source, $destination) as $path) {
                $copied[] = $path;
            }
        }

        $copied = array_values(array_unique(array_map($this->normalizePath(...), $copied)));
        sort($copied);

        return [
            'example' => $this->publicDefinition($definition),
            'target_path' => $targetPath,
            'mode' => $preferTemp ? 'temp_directory' : 'working_directory',
            'files_copied' => $copied,
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function definitions(): array
    {
        $paths = $this->paths ?? Paths::fromCwd();
        $frameworkRoot = $paths->frameworkRoot();

        return [
            [
                'name' => 'blog',
                'aliases' => ['blog', 'blog-api', 'reference-blog'],
                'label' => 'Blog (reference)',
                'description' => 'A reference-ready blog application with list, view, and publish flows.',
                'kind' => 'reference',
                'source_examples' => ['examples/blog-api'],
                'next_generate_intent' => 'Add comments to blog posts',
                'next_generate_mode' => 'modify',
                'next_generate_target' => 'list_posts',
                'copy_sets' => [
                    [
                        'source' => $frameworkRoot . '/examples/blog-api',
                        'destination' => '',
                    ],
                ],
            ],
            [
                'name' => 'extensions-migrations',
                'aliases' => ['extensions-migrations', 'extensions', 'migrations'],
                'label' => 'Extensions & migrations (framework)',
                'description' => 'A runnable example that layers extension and migration assets onto a small application.',
                'kind' => 'framework',
                'source_examples' => ['examples/hello-world/app', 'examples/extensions-migrations'],
                'next_generate_intent' => 'Add a feature',
                'next_generate_mode' => 'new',
                'next_generate_target' => null,
                'copy_sets' => [
                    [
                        'source' => $frameworkRoot . '/examples/hello-world/app',
                        'destination' => 'app',
                    ],
                    [
                        'source' => $frameworkRoot . '/examples/extensions-migrations/README.md',
                        'destination' => 'README.md',
                    ],
                    [
                        'source' => $frameworkRoot . '/examples/extensions-migrations/foundry.extensions.php',
                        'destination' => 'foundry.extensions.php',
                    ],
                    [
                        'source' => $frameworkRoot . '/examples/extensions-migrations/demo_extension',
                        'destination' => 'demo_extension',
                    ],
                    [
                        'source' => $frameworkRoot . '/examples/extensions-migrations/migration',
                        'destination' => 'migration',
                    ],
                    [
                        'source' => $frameworkRoot . '/examples/extensions-migrations/codemod',
                        'destination' => 'codemod',
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function definition(string $name): array
    {
        $normalized = strtolower(trim($name));
        foreach ($this->definitions() as $definition) {
            $aliases = array_values(array_map(
                static fn(mixed $value): string => strtolower(trim((string) $value)),
                (array) ($definition['aliases'] ?? []),
            ));

            if (in_array($normalized, $aliases, true)) {
                return $definition;
            }
        }

        throw new FoundryError(
            'EXAMPLE_NOT_FOUND',
            'not_found',
            ['example' => $name],
            'Example not found.',
        );
    }

    /**
     * @param array<string,mixed> $definition
     * @return array<string,mixed>
     */
    private function publicDefinition(array $definition): array
    {
        return [
            'name' => (string) ($definition['name'] ?? ''),
            'label' => (string) ($definition['label'] ?? ''),
            'description' => (string) ($definition['description'] ?? ''),
            'kind' => (string) ($definition['kind'] ?? 'reference'),
            'source_examples' => array_values(array_map('strval', (array) ($definition['source_examples'] ?? []))),
            'next_generate_intent' => (string) ($definition['next_generate_intent'] ?? ''),
            'next_generate_mode' => (string) ($definition['next_generate_mode'] ?? ''),
            'next_generate_target' => $definition['next_generate_target'] !== null
                ? (string) $definition['next_generate_target']
                : null,
        ];
    }

    private function destinationPath(string $targetRoot, string $destination): string
    {
        $destination = trim($destination);
        if ($destination === '') {
            return $targetRoot;
        }

        return $this->normalizePath($targetRoot . '/' . ltrim($destination, '/'));
    }

    /**
     * @return array<int,string>
     */
    private function copyInto(string $source, string $destination): array
    {
        if (is_file($source)) {
            $directory = dirname($destination);
            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }

            copy($source, $destination);

            return [$this->normalizePath($destination)];
        }

        if (!is_dir($source)) {
            throw new FoundryError(
                'EXAMPLE_SOURCE_MISSING',
                'runtime',
                ['source' => $source],
                'Example source is missing.',
            );
        }

        if (!is_dir($destination)) {
            mkdir($destination, 0777, true);
        }

        $copied = [];
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST,
        );

        /** @var \SplFileInfo $item */
        foreach ($iterator as $item) {
            $relativePath = substr($this->normalizePath($item->getPathname()), strlen(rtrim($source, '/') . '/'));
            $target = $this->normalizePath($destination . '/' . $relativePath);

            if ($item->isDir()) {
                if (!is_dir($target)) {
                    mkdir($target, 0777, true);
                }

                continue;
            }

            $directory = dirname($target);
            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }

            copy($item->getPathname(), $target);
            $copied[] = $target;
        }

        return $copied;
    }

    private function isSafeWorkingDirectory(string $path): bool
    {
        if (!is_dir($path)) {
            mkdir($path, 0777, true);

            return true;
        }

        $items = scandir($path);
        if ($items === false) {
            return false;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            return false;
        }

        return true;
    }

    private function createTempDirectory(string $name): string
    {
        $prefix = 'foundry-example-' . preg_replace('/[^a-z0-9]+/i', '-', strtolower($name)) . '-';
        $temporaryFile = tempnam(sys_get_temp_dir(), $prefix);
        if ($temporaryFile === false) {
            throw new FoundryError(
                'EXAMPLE_TEMP_DIRECTORY_FAILED',
                'runtime',
                ['example' => $name],
                'Unable to create a temporary directory for the example.',
            );
        }

        @unlink($temporaryFile);
        mkdir($temporaryFile, 0777, true);

        return $this->normalizePath($temporaryFile);
    }

    private function normalizePath(string $path): string
    {
        return str_replace('\\', '/', rtrim($path, '/'));
    }
}
