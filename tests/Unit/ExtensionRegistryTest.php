<?php
declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\Compiler\Extensions\AbstractCompilerExtension;
use Foundry\Compiler\Extensions\ExtensionRegistry;
use Foundry\Compiler\Migration\MigrationRule;
use Foundry\Compiler\Projection\GenericProjectionEmitter;
use PHPUnit\Framework\TestCase;

final class ExtensionRegistryTest extends TestCase
{
    public function test_extensions_are_registered_and_sorted_deterministically(): void
    {
        $ruleA = new class implements MigrationRule {
            public function id(): string { return 'A_RULE'; }
            public function description(): string { return 'A'; }
            public function sourceType(): string { return 'feature_manifest'; }
            public function applies(string $path, array $document): bool { return false; }
            public function migrate(string $path, array $document): array { return $document; }
        };

        $ruleB = new class implements MigrationRule {
            public function id(): string { return 'B_RULE'; }
            public function description(): string { return 'B'; }
            public function sourceType(): string { return 'feature_manifest'; }
            public function applies(string $path, array $document): bool { return false; }
            public function migrate(string $path, array $document): array { return $document; }
        };

        $extensionB = new class($ruleB) extends AbstractCompilerExtension {
            public function __construct(private readonly MigrationRule $rule) {}
            public function name(): string { return 'b-ext'; }
            public function version(): string { return '1.0.0'; }
            public function projectionEmitters(): array {
                return [new GenericProjectionEmitter('z-projection', 'z.php', null, static fn (): array => [])];
            }
            public function migrationRules(): array { return [$this->rule]; }
        };

        $extensionA = new class($ruleA) extends AbstractCompilerExtension {
            public function __construct(private readonly MigrationRule $rule) {}
            public function name(): string { return 'a-ext'; }
            public function version(): string { return '1.0.0'; }
            public function projectionEmitters(): array {
                return [new GenericProjectionEmitter('a-projection', 'a.php', null, static fn (): array => [])];
            }
            public function migrationRules(): array { return [$this->rule]; }
        };

        $registry = new ExtensionRegistry([$extensionB, $extensionA]);

        $rows = $registry->inspectRows();
        $this->assertSame('a-ext', $rows[0]['name']);
        $this->assertSame('b-ext', $rows[1]['name']);

        $emitters = $registry->projectionEmitters();
        $this->assertSame('a-projection', $emitters[0]->id());
        $this->assertSame('z-projection', $emitters[1]->id());

        $rules = $registry->migrationRules();
        $this->assertSame('A_RULE', $rules[0]->id());
        $this->assertSame('B_RULE', $rules[1]->id());
    }
}
