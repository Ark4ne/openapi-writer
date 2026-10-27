<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization\Rewriters;

use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Serialization\Context;

/**
 * `exclusiveMinimum(n)` / `exclusiveMaximum(n)`: `minimum: n` + `exclusiveMinimum: true` in 3.0, numeric from 3.1.
 */
final class ExclusiveBoundsRewriter implements Rewriter
{
    public function supports(SpecObject $object): bool
    {
        return false;
    }

    public function rewrite(SpecObject $object, array $node, Context $context): array
    {
        return $node;
    }
}
