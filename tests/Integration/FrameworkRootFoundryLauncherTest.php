<?php

declare(strict_types=1);

namespace Foundry\Tests\Integration;

use PHPUnit\Framework\TestCase;

final class FrameworkRootFoundryLauncherTest extends TestCase
{
    public function test_framework_root_launcher_exists_and_is_executable(): void
    {
        $launcherPath = dirname(__DIR__, 2) . '/foundry';

        $this->assertFileExists($launcherPath);
        $this->assertTrue(is_executable($launcherPath));
    }

    public function test_framework_root_launcher_forwards_arguments_to_bin_foundry_using_php_bin(): void
    {
        $frameworkRoot = dirname(__DIR__, 2);
        $launcherPath = $frameworkRoot . '/foundry';

        $tmp = sys_get_temp_dir() . '/foundry-launcher-test-' . bin2hex(random_bytes(8));
        mkdir($tmp, 0777, true);
        $phpBin = $tmp . '/php';
        $capturedArgs = $tmp . '/args.txt';

        file_put_contents($phpBin, "#!/usr/bin/env bash\nprintf '%s\\n' \"\$@\" > " . escapeshellarg($capturedArgs) . "\n");
        chmod($phpBin, 0755);

        $process = proc_open(
            [$launcherPath, 'verify', 'context', '--json'],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
            $pipes,
            $frameworkRoot,
            ['PHP_BIN' => $phpBin],
        );

        $this->assertIsResource($process);
        fclose($pipes[0]);
        fclose($pipes[1]);
        fclose($pipes[2]);

        $status = proc_close($process);
        $this->assertSame(0, $status);
        $this->assertFileExists($capturedArgs);

        $args = file($capturedArgs, FILE_IGNORE_NEW_LINES);
        $this->assertIsArray($args);
        $this->assertSame($frameworkRoot . '/bin/foundry', $args[0] ?? null);
        $this->assertSame('verify', $args[1] ?? null);
        $this->assertSame('context', $args[2] ?? null);
        $this->assertSame('--json', $args[3] ?? null);
    }

    public function test_framework_root_launcher_uses_expected_php_candidate_ordering(): void
    {
        $contents = (string) file_get_contents(dirname(__DIR__, 2) . '/foundry');

        $phpBinPos = strpos($contents, 'if [[ -n "${PHP_BIN:-}" ]]; then');
        $homebrewPos = strpos($contents, '"/opt/homebrew/bin/php"');
        $usrLocalPos = strpos($contents, '"/usr/local/bin/php"');
        $pathPos = strpos($contents, '"$(command -v php || true)"');

        $this->assertNotFalse($phpBinPos);
        $this->assertNotFalse($homebrewPos);
        $this->assertNotFalse($usrLocalPos);
        $this->assertNotFalse($pathPos);
        $this->assertTrue($phpBinPos < $homebrewPos);
        $this->assertTrue($homebrewPos < $usrLocalPos);
        $this->assertTrue($usrLocalPos < $pathPos);
    }
}
