<?php

declare(strict_types=1);

namespace Foundry\Generate;

final class CodeWriter
{
    /**
     * @param array<int,string> $paths
     * @return array<string,array{exists:bool,content:?string}>
     */
    public function snapshot(array $paths): array
    {
        $snapshots = [];
        foreach (array_values(array_unique(array_map('strval', $paths))) as $path) {
            $snapshots[$path] = [
                'exists' => is_file($path),
                'content' => is_file($path) ? (file_get_contents($path) ?: '') : null,
            ];
        }

        ksort($snapshots);

        return $snapshots;
    }

    /**
     * @param array<string,array{exists:bool,content:?string}> $snapshots
     */
    public function restore(array $snapshots): void
    {
        foreach ($snapshots as $path => $snapshot) {
            if (($snapshot['exists'] ?? false) !== true) {
                if (is_file($path)) {
                    @unlink($path);
                }

                continue;
            }

            $directory = dirname($path);
            if (!is_dir($directory)) {
                mkdir($directory, 0777, true);
            }

            file_put_contents($path, (string) ($snapshot['content'] ?? ''));
        }
    }

    public function syncFile(string $path, string $content): bool
    {
        $existing = is_file($path) ? (file_get_contents($path) ?: '') : null;
        if ($existing === $content) {
            return false;
        }

        $directory = dirname($path);
        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        file_put_contents($path, $content);

        return true;
    }
}
