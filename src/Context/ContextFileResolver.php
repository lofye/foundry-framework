<?php

declare(strict_types=1);

namespace Foundry\Context;

use Foundry\Support\FeatureNaming;

final class ContextFileResolver
{
    public function __construct(
        private readonly ?string $workspaceRoot = null,
    ) {}

    public function legacySpecPath(string $featureName): string
    {
        return $this->canonicalSpecPath($featureName);
    }

    public function canonicalSpecPath(string $featureName): string
    {
        $featureName = FeatureNaming::canonical($featureName);
        $root = $this->canonicalRootForFeature($featureName);

        return $root . '/' . $this->pascalFromSlug($featureName) . '/' . $featureName . '.spec.md';
    }

    public function specPath(string $featureName): string
    {
        return $this->canonicalSpecPath($featureName);
    }

    public function legacyStatePath(string $featureName): string
    {
        return $this->canonicalStatePath($featureName);
    }

    public function canonicalStatePath(string $featureName): string
    {
        $featureName = FeatureNaming::canonical($featureName);
        $root = $this->canonicalRootForFeature($featureName);

        return $root . '/' . $this->pascalFromSlug($featureName) . '/' . $featureName . '.md';
    }

    public function statePath(string $featureName): string
    {
        return $this->canonicalStatePath($featureName);
    }

    public function legacyDecisionsPath(string $featureName): string
    {
        return $this->canonicalDecisionsPath($featureName);
    }

    public function canonicalDecisionsPath(string $featureName): string
    {
        $featureName = FeatureNaming::canonical($featureName);
        $root = $this->canonicalRootForFeature($featureName);

        return $root . '/' . $this->pascalFromSlug($featureName) . '/' . $featureName . '.decisions.md';
    }

    public function decisionsPath(string $featureName): string
    {
        return $this->canonicalDecisionsPath($featureName);
    }

    /**
     * @return array{spec:string,state:string,decisions:string}
     */
    public function paths(string $featureName): array
    {
        return [
            'spec' => $this->specPath($featureName),
            'state' => $this->statePath($featureName),
            'decisions' => $this->decisionsPath($featureName),
        ];
    }

    /**
     * @return array{spec:string,state:string,decisions:string}
     */
    public function canonicalPaths(string $featureName): array
    {
        $featureName = FeatureNaming::canonical($featureName);

        return [
            'spec' => $this->canonicalSpecPath($featureName),
            'state' => $this->canonicalStatePath($featureName),
            'decisions' => $this->canonicalDecisionsPath($featureName),
        ];
    }

    /**
     * @return array{spec:string,state:string,decisions:string}
     */
    public function legacyPaths(string $featureName): array
    {
        $featureName = FeatureNaming::canonical($featureName);

        return [
            'spec' => $this->legacySpecPath($featureName),
            'state' => $this->legacyStatePath($featureName),
            'decisions' => $this->legacyDecisionsPath($featureName),
        ];
    }

    private function pascalFromSlug(string $slug): string
    {
        $parts = array_filter(explode('-', $slug), static fn(string $part): bool => $part !== '');

        return implode('', array_map(static fn(string $part): string => ucfirst($part), $parts));
    }

    private function isFile(string $relativePath): bool
    {
        $absolutePath = $this->absolutePath($relativePath);
        if ($absolutePath === null) {
            return false;
        }

        return is_file($absolutePath);
    }

    private function isDirectory(string $relativePath): bool
    {
        $absolutePath = $this->absolutePath($relativePath);
        if ($absolutePath === null) {
            return false;
        }

        return is_dir($absolutePath);
    }

    private function absolutePath(string $relativePath): ?string
    {
        if ($this->workspaceRoot === null || $this->workspaceRoot === '') {
            return null;
        }

        return rtrim($this->workspaceRoot, '/\\') . '/' . ltrim($relativePath, '/\\');
    }

    private function canonicalRootForFeature(string $featureName): string
    {
        $featureDir = $this->pascalFromSlug($featureName);

        if (
            $this->pathExists('Modules/' . $featureDir)
            || $this->pathExists('Modules/' . $featureDir . '/' . $featureName . '.spec.md')
            || $this->pathExists('Modules/' . $featureDir . '/' . $featureName . '.md')
            || $this->pathExists('Modules/' . $featureDir . '/' . $featureName . '.decisions.md')
        ) {
            return 'Modules';
        }

        if (
            $this->pathExists('Features/' . $featureDir)
            || $this->pathExists('Features/' . $featureDir . '/' . $featureName . '.spec.md')
            || $this->pathExists('Features/' . $featureDir . '/' . $featureName . '.md')
            || $this->pathExists('Features/' . $featureDir . '/' . $featureName . '.decisions.md')
        ) {
            return 'Features';
        }

        if ($this->isDirectory('Features')) {
            return 'Features';
        }

        return 'Features';
    }

    private function pathExists(string $relativePath): bool
    {
        $absolutePath = $this->absolutePath($relativePath);
        if ($absolutePath === null) {
            return false;
        }

        return file_exists($absolutePath);
    }
}
