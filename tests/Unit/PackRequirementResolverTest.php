<?php

declare(strict_types=1);

namespace Foundry\Tests\Unit;

use Foundry\Compiler\Extensions\PackDefinition;
use Foundry\Compiler\Extensions\PackRegistry;
use Foundry\Generate\Intent;
use Foundry\Generate\PackRequirementResolver;
use PHPUnit\Framework\TestCase;

final class PackRequirementResolverTest extends TestCase
{
    public function test_resolver_reports_missing_hinted_pack(): void
    {
        $resolver = new PackRequirementResolver();
        $result = $resolver->resolve(
            new Intent(raw: 'Create blog post notes', mode: 'new', packHints: ['foundry/blog']),
            new PackRegistry(),
        );

        $this->assertSame(['pack:foundry/blog'], $result['missing_capabilities']);
        $this->assertSame(['foundry/blog'], $result['suggested_packs']);
    }

    public function test_resolver_ignores_installed_hinted_pack(): void
    {
        $resolver = new PackRequirementResolver();
        $result = $resolver->resolve(
            new Intent(raw: 'Create blog post notes', mode: 'new', packHints: ['foundry/blog']),
            new PackRegistry([
                new PackDefinition(
                    name: 'foundry/blog',
                    version: '1.0.0',
                    extension: 'pack.foundry.blog',
                    providedCapabilities: ['blog.notes'],
                ),
            ]),
        );

        $this->assertSame([], $result['missing_capabilities']);
        $this->assertSame([], $result['suggested_packs']);
    }
}
