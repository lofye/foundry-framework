<?php
declare(strict_types=1);

namespace Foundry\Compiler\Extensions;

use Foundry\Compiler\Migration\FeatureManifestV2Rule;
use Foundry\Compiler\Migration\MigrationRule;
use Foundry\Compiler\Projection\CoreProjectionEmitters;
use Foundry\Compiler\Projection\ProjectionEmitter;

final class CoreCompilerExtension extends AbstractCompilerExtension
{
    public function name(): string
    {
        return 'core';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    /**
     * @return array<int,ProjectionEmitter>
     */
    public function projectionEmitters(): array
    {
        return CoreProjectionEmitters::all();
    }

    /**
     * @return array<int,MigrationRule>
     */
    public function migrationRules(): array
    {
        return [new FeatureManifestV2Rule()];
    }
}
