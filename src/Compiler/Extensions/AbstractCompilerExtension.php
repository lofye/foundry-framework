<?php
declare(strict_types=1);

namespace Foundry\Compiler\Extensions;

use Foundry\Compiler\CompilerPass;
use Foundry\Compiler\Migration\MigrationRule;
use Foundry\Compiler\Projection\ProjectionEmitter;

abstract class AbstractCompilerExtension implements CompilerExtension
{
    /** @return array<int,CompilerPass> */
    public function discoveryPasses(): array
    {
        return [];
    }

    /** @return array<int,CompilerPass> */
    public function normalizePasses(): array
    {
        return [];
    }

    /** @return array<int,CompilerPass> */
    public function linkPasses(): array
    {
        return [];
    }

    /** @return array<int,CompilerPass> */
    public function validatePasses(): array
    {
        return [];
    }

    /** @return array<int,CompilerPass> */
    public function enrichPasses(): array
    {
        return [];
    }

    /** @return array<int,CompilerPass> */
    public function emitPasses(): array
    {
        return [];
    }

    /** @return array<int,CompilerPass> */
    public function analyzePasses(): array
    {
        return [];
    }

    /** @return array<int,ProjectionEmitter> */
    public function projectionEmitters(): array
    {
        return [];
    }

    /** @return array<int,MigrationRule> */
    public function migrationRules(): array
    {
        return [];
    }

    /** @return array<string,mixed> */
    public function describe(): array
    {
        return [
            'name' => $this->name(),
            'version' => $this->version(),
            'discovery_passes' => count($this->discoveryPasses()),
            'normalize_passes' => count($this->normalizePasses()),
            'link_passes' => count($this->linkPasses()),
            'validate_passes' => count($this->validatePasses()),
            'enrich_passes' => count($this->enrichPasses()),
            'emit_passes' => count($this->emitPasses()),
            'analyze_passes' => count($this->analyzePasses()),
            'projection_emitters' => array_values(array_map(
                static fn (ProjectionEmitter $emitter): string => $emitter->id(),
                $this->projectionEmitters(),
            )),
            'migration_rules' => array_values(array_map(
                static fn (MigrationRule $rule): string => $rule->id(),
                $this->migrationRules(),
            )),
        ];
    }
}
