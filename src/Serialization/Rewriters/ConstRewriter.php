<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization\Rewriters;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Objects\Schema;
use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Serialization\Context;

/**
 * `const(v)`: `enum: [v]` in 3.0, `const: v` from 3.1. The constant is stricter than an enum next to it that holds
 * it, which it replaces; a constant outside the enum has no 3.0 form. A null constant without type is written as
 * the 3.0 null schema.
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

        $const = $node['const'];
        unset($node['const']);

        if (is_array($node['enum'] ?? null) && !in_array($const, $node['enum'], true)) {
            $context->at('const')->unsupported('a const outside the enum needs 3.1.0');

            return $node;
        }

        $node['enum'] = [$const];

        if ($const === null && !isset($node['type'])) {
            // Since 3.0.3, `nullable` has no effect without an explicit type.
            $node['type'] = 'object';
            $node['nullable'] = true;
        }

        $context->converted('const written as a one-value enum');

        return $node;
    }
}
