<?php

declare(strict_types=1);

namespace Foundry\Tests\Integration;

use Foundry\CLI\Application;
use Foundry\CLI\Commands\GenerateCommand;
use Foundry\Packs\HostedPackRegistry;
use Foundry\Packs\PackChecksum;
use Foundry\Packs\PackManager;
use Foundry\Support\Paths;
use Foundry\Tests\Fixtures\TempProject;
use PHPUnit\Framework\TestCase;

final class CLIGenerateCommandTest extends TestCase
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

    public function test_generate_reports_missing_pack_without_auto_install(): void
    {
        $app = new Application();

        $result = $this->runCommand($app, [
            'foundry',
            'generate',
            'Create',
            'blog',
            'post',
            'notes',
            '--mode=new',
            '--packs=foundry/blog',
            '--json',
        ]);

        $this->assertSame(1, $result['status']);
        $this->assertSame('GENERATE_PACK_INSTALL_REQUIRED', $result['payload']['error']['code']);
        $this->assertSame(['pack:foundry/blog'], $result['payload']['error']['details']['missing_capabilities']);
        $this->assertSame(['foundry/blog'], $result['payload']['error']['details']['suggested_packs']);
    }

    public function test_generate_uses_installed_pack_generator_when_pack_is_available(): void
    {
        $app = new Application();

        $install = $this->runCommand($app, ['foundry', 'pack', 'install', $this->fixturePath('foundry-blog'), '--json']);
        $this->assertSame(0, $install['status']);

        $generate = $this->runCommand($app, [
            'foundry',
            'generate',
            'Create',
            'blog',
            'post',
            'notes',
            '--mode=new',
            '--packs=foundry/blog',
            '--json',
        ]);

        $this->assertSame(0, $generate['status']);
        $this->assertSame('pack', $generate['payload']['plan']['origin']);
        $this->assertSame('generate blog-post', $generate['payload']['plan']['generator_id']);
        $this->assertSame(['foundry/blog'], $generate['payload']['packs_used']);
        $this->assertSame('pack:foundry/blog', $generate['payload']['metadata']['target']['resolved']);
        $this->assertFileExists($this->project->root . '/app/features/blog_post_notes/feature.yaml');
    }

    public function test_generate_can_auto_install_required_pack_when_allowed(): void
    {
        $downloadUrl = 'https://downloads.example/foundry-blog-1.0.0.zip';
        $manifest = $this->fixtureManifest('foundry-blog');
        $app = $this->hostedGenerateApplication(
            [[
                'name' => 'foundry/blog',
                'version' => '1.0.0',
                'description' => 'Blog workflow tools',
                'download_url' => $downloadUrl,
                'checksum' => $manifest['checksum'],
                'signature' => $manifest['signature'],
                'verified' => true,
            ]],
            [$downloadUrl => $this->fixtureArchive('foundry-blog')],
        );

        $generate = $this->runCommand($app, [
            'foundry',
            'generate',
            'Create',
            'blog',
            'post',
            'notes',
            '--mode=new',
            '--packs=foundry/blog',
            '--allow-pack-install',
            '--json',
        ]);

        $this->assertSame(0, $generate['status']);
        $this->assertSame('pack', $generate['payload']['plan']['origin']);
        $this->assertSame('foundry/blog', $generate['payload']['packs_installed'][0]['pack']);
        $this->assertSame('registry', $generate['payload']['packs_installed'][0]['source']['type']);
        $this->assertFileExists($this->project->root . '/.foundry/packs/foundry/blog/1.0.0/foundry.json');
        $this->assertFileExists($this->project->root . '/app/features/blog_post_notes/feature.yaml');
    }

    /**
     * @param array<int,string> $argv
     * @return array{status:int,payload:array<string,mixed>}
     */
    private function runCommand(Application $app, array $argv): array
    {
        ob_start();
        $status = $app->run($argv);
        $output = trim((string) ob_get_clean());

        /** @var array<string,mixed> $payload */
        $payload = json_decode($output, true, 512, JSON_THROW_ON_ERROR);

        return ['status' => $status, 'payload' => $payload];
    }

    private function fixturePath(string $name): string
    {
        return dirname(__DIR__) . '/Fixtures/Packs/' . $name;
    }

    /**
     * @return array<string,mixed>
     */
    private function fixtureManifest(string $fixtureName): array
    {
        return json_decode((string) file_get_contents($this->fixturePath($fixtureName) . '/foundry.json'), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<int,array<string,mixed>> $registryEntries
     * @param array<string,string> $downloads
     */
    private function hostedGenerateApplication(array $registryEntries, array $downloads): Application
    {
        $registryUrl = 'https://registry.example/packs';
        $responses = $downloads + [
            $registryUrl => json_encode($registryEntries, JSON_THROW_ON_ERROR),
        ];

        $fetcher = static function (string $url) use ($responses): string {
            if (!array_key_exists($url, $responses)) {
                throw new \RuntimeException('Unexpected URL: ' . $url);
            }

            return $responses[$url];
        };

        $paths = Paths::fromCwd($this->project->root);
        $registry = new HostedPackRegistry($paths, $fetcher, $registryUrl);
        $manager = new PackManager($paths, $registry);

        return new Application([new GenerateCommand($manager)]);
    }

    private function fixtureArchive(string $fixtureName): string
    {
        $archive = tempnam(sys_get_temp_dir(), 'foundry-generate-archive-');
        assert(is_string($archive));

        $zip = new \ZipArchive();
        $opened = $zip->open($archive, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $this->assertSame(true, $opened);

        $source = $this->fixturePath($fixtureName);
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($source, \FilesystemIterator::SKIP_DOTS),
        );

        foreach ($iterator as $fileInfo) {
            if (!$fileInfo instanceof \SplFileInfo || !$fileInfo->isFile()) {
                continue;
            }

            $relative = substr($fileInfo->getPathname(), strlen(rtrim($source, '/') . '/'));
            $zip->addFile($fileInfo->getPathname(), $relative);
        }

        $zip->close();
        $contents = file_get_contents($archive);
        @unlink($archive);

        return is_string($contents) ? $contents : '';
    }
}
