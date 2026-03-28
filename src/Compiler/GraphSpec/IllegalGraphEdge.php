<?php

declare(strict_types=1);

namespace Foundry\Compiler\GraphSpec;

final class IllegalGraphEdge extends \RuntimeException
{
    public function __construct(string $message)
    {
        parent::__construct($message);
    }
}
