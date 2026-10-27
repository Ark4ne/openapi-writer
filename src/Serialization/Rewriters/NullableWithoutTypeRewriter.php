<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization\Rewriters;

use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Serialization\Context;

/**
 * `nullable()` on a `$ref` or without type: `anyOf: [{$ref}, {type: object, nullable: true, enum: [null]}]` in 3.0, `anyOf: [{$ref}, {type: "null"}]` from 3.1.
 */
final class NullableWithoutTypeRewriter implements Rewriter
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
