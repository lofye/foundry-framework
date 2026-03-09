<?php
declare(strict_types=1);

namespace Foundry\Compiler\Extensions;

use Foundry\Compiler\Migration\MigrationRule;
use Foundry\Compiler\Projection\ProjectionEmitter;

final class ExtensionRegistry
{
    /**
     * @var array<string,CompilerExtension>
     */
    private array $extensions = [];

    /**
     * @param array<int,CompilerExtension> $extensions
     */
    public function __construct(array $extensions = [])
    {
        foreach ($extensions as $extension) {
            $this->register($extension);
        }
    }

    public function register(CompilerExtension $extension): void
    {
        $key = $extension->name() . '@' . $extension->version();
        $this->extensions[$key] = $extension;
        ksort($this->extensions);
    }

    /**
     * @return array<int,CompilerExtension>
     */
    public function all(): array
    {
        return array_values($this->extensions);
    }

    /**
     * @return array<int,ProjectionEmitter>
     */
    public function projectionEmitters(): array
    {
        $emitters = [];
        foreach ($this->all() as $extension) {
            foreach ($extension->projectionEmitters() as $emitter) {
                $emitters[] = $emitter;
            }
        }

        usort($emitters, static fn (ProjectionEmitter $a, ProjectionEmitter $b): int => strcmp($a->id(), $b->id()));

        return $emitters;
    }

    /**
     * @return array<int,MigrationRule>
     */
    public function migrationRules(): array
    {
        $rules = [];
        foreach ($this->all() as $extension) {
            foreach ($extension->migrationRules() as $rule) {
                $rules[] = $rule;
            }
        }

        usort($rules, static fn (MigrationRule $a, MigrationRule $b): int => strcmp($a->id(), $b->id()));

        return $rules;
    }

    /**
     * @param callable(CompilerExtension):array<int,mixed> $selector
     * @return array<int,mixed>
     */
    public function collect(callable $selector): array
    {
        $items = [];
        foreach ($this->all() as $extension) {
            foreach ($selector($extension) as $item) {
                $items[] = $item;
            }
        }

        return $items;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    public function inspectRows(): array
    {
        $rows = [];
        foreach ($this->all() as $extension) {
            $rows[] = $extension->describe();
        }

        usort(
            $rows,
            static fn (array $a, array $b): int => strcmp((string) ($a['name'] ?? ''), (string) ($b['name'] ?? '')),
        );

        return $rows;
    }
}
