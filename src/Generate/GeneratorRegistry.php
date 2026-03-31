<?php

declare(strict_types=1);

namespace Foundry\Generate;

use Foundry\Compiler\Extensions\ExtensionRegistry;
use Foundry\Packs\InstalledPackExtension;

final class GeneratorRegistry
{
    /**
     * @var array<int,RegisteredGenerator>
     */
    private array $generators = [];

    public function register(RegisteredGenerator $generator): void
    {
        $this->generators[] = $generator;
        usort(
            $this->generators,
            static fn(RegisteredGenerator $left, RegisteredGenerator $right): int => strcmp($left->origin, $right->origin)
                ?: strcmp((string) ($left->extension ?? ''), (string) ($right->extension ?? ''))
                ?: strcmp($left->id, $right->id),
        );
    }

    /**
     * @return array<int,RegisteredGenerator>
     */
    public function all(): array
    {
        return $this->generators;
    }

    public static function forExtensions(ExtensionRegistry $extensions): self
    {
        $registry = new self();

        $registry->register(new RegisteredGenerator(
            id: 'core.feature.new',
            origin: 'core',
            extension: null,
            generator: new Core\CoreNewFeatureGenerator(),
            capabilities: ['core.feature.new'],
            priority: 10,
        ));
        $registry->register(new RegisteredGenerator(
            id: 'core.feature.modify',
            origin: 'core',
            extension: null,
            generator: new Core\CoreModifyFeatureGenerator(),
            capabilities: ['core.feature.modify'],
            priority: 10,
        ));
        $registry->register(new RegisteredGenerator(
            id: 'core.feature.repair',
            origin: 'core',
            extension: null,
            generator: new Core\CoreRepairFeatureGenerator(),
            capabilities: ['core.feature.repair'],
            priority: 10,
        ));

        foreach ($extensions->all() as $extension) {
            if (!$extension instanceof InstalledPackExtension) {
                continue;
            }

            foreach ($extension->generatorDefinitions() as $definition) {
                $registry->register(new RegisteredGenerator(
                    id: $definition->name,
                    origin: 'pack',
                    extension: $extension->packName(),
                    generator: $definition->generator,
                    capabilities: $definition->capabilities,
                    priority: $definition->priority,
                ));
            }
        }

        return $registry;
    }
}
