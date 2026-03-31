<?php

declare(strict_types=1);

namespace Vendor\Blog;

use Foundry\Pipeline\PipelineExecutionState;
use Foundry\Pipeline\StageInterceptor;

final class FoundryBlogStageInterceptor implements StageInterceptor
{
    #[\Override]
    public function id(): string
    {
        return 'pack.foundry.blog';
    }

    #[\Override]
    public function stage(): string
    {
        return 'auth';
    }

    #[\Override]
    public function priority(): int
    {
        return 50;
    }

    #[\Override]
    public function handle(PipelineExecutionState $state): void
    {
        // Fixture interceptor for pack graph registration tests.
    }

    #[\Override]
    public function isDangerous(): bool
    {
        return false;
    }
}
