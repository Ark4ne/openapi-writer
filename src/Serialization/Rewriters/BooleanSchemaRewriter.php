<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization\Rewriters;

use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Serialization\Context;

/**
 * Boolean subschema (`items: false`, `not: true`…): `{}` for true and `{not: {}}` for false in 3.0, kept from 3.1.
 * `additionalProperties` accepts a boolean in every version and is left as is.
 */
final class BooleanSchemaRewriter implements Rewriter
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
