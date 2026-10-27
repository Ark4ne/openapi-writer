<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization\Rewriters;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Objects\Schema;
use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Serialization\Context;

/**
 * `const(v)`: `enum: [v]` in 3.0, `const: v` from 3.1. The constant is stricter than any enum next to it, which it
 * replaces.
 */
final class ConstRewriter implements Rewriter
{
    public function supports(SpecObject $object): bool
    {
        return $object instanceof Schema && $object->has('const');
    }

    public function rewrite(SpecObject $object, array $node, Context $context): array
    {
        if ($context->version->isAtLeast(Version::V3_1_0) || !array_key_exists('const', $node)) {
            return $node;
        }

        $node['enum'] = [$node['const']];
        unset($node['const']);
        $context->converted('const written as a one-value enum');

        return $node;
    }
}
