<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization\Rewriters;

use Ark4ne\OpenApiWriter\Enums\SchemaType;
use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Objects\Schema;
use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Serialization\Context;

/**
 * `nullable()` with a type: `type: X` + `nullable: true` in 3.0, `type: [X, "null"]` from 3.1. In 3.0, a `null` type
 * is left to NullTypeRewriter and several types to MultiTypeRewriter, which report the conversion.
 */
final class NullableRewriter implements Rewriter
{
    public function supports(SpecObject $object): bool
    {
        return $object instanceof Schema && $object->nullable === true && $object->has('type');
    }

    public function rewrite(SpecObject $object, array $node, Context $context): array
    {
        $types = $node['type'] ?? null;

        if (!is_array($types) || in_array(SchemaType::Null->value, $types, true)) {
            return $node;
        }

        if ($context->version->isAtLeast(Version::V3_1_0)) {
            $node['type'] = [...$types, SchemaType::Null->value];

            return $node;
        }

        $node['nullable'] = true;

        if (count($types) === 1) {
            $context->converted('nullable written as nullable: true');
        }

        return $node;
    }
}
