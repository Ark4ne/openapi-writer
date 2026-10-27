<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization\Rewriters;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Objects\Schema;
use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Serialization\Context;
use stdClass;

/**
 * Boolean subschema (`items: false`, `not: true`…): `{}` for true and `{not: {}}` for false in 3.0, kept from 3.1.
 * `additionalProperties` accepts a boolean in every version and is left as is; the other boolean subschemas are
 * 3.1 keywords.
 */
final class BooleanSchemaRewriter implements Rewriter
{
    private const SUBSCHEMAS = ['items', 'not'];

    public function supports(SpecObject $object): bool
    {
        return $object instanceof Schema;
    }

    public function rewrite(SpecObject $object, array $node, Context $context): array
    {
        if ($context->version->isAtLeast(Version::V3_1_0)) {
            return $node;
        }

        $converted = false;

        foreach (self::SUBSCHEMAS as $keyword) {
            if (is_bool($node[$keyword] ?? null)) {
                $node[$keyword] = $node[$keyword] ? new stdClass() : ['not' => new stdClass()];
                $converted = true;
            }
        }

        if ($converted) {
            $context->converted('boolean subschema written as {} or {not: {}}');
        }

        return $node;
    }
}
