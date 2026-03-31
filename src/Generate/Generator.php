<?php

declare(strict_types=1);

namespace Foundry\Generate;

use Foundry\Explain\ExplainModel;

interface Generator
{
    public function supports(ExplainModel $model, Intent $intent): bool;

    public function plan(ExplainModel $model, Intent $intent): GenerationPlan;
}
