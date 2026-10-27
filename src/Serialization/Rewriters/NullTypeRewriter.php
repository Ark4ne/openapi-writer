<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization\Rewriters;

use Ark4ne\OpenApiWriter\Enums\SchemaType;
use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Objects\Schema;
use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Serialization\Context;

/**
 * `Schema::null()`: `type: object` + `nullable: true` + `enum: [null]` in 3.0, `type: "null"` from 3.1. Next to other
 * types, `null` becomes `nullable: true` in 3.0; several types are then reported by MultiTypeRewriter.
 */
final class NullTypeRewriter implements Rewriter
{
    /**
     * The schema that accepts `null` only, in 3.0: since 3.0.3, `nullable` has no effect without an explicit type.
     */
    public const NULL_SCHEMA = ['enum' => [null], 'type' => 'object', 'nullable' => true];

    public function supports(SpecObject $object): bool
    {
        return $object instanceof Schema && is_array($object->type) && in_array(SchemaType::Null, $object->type, true);
    }

    public function rewrite(SpecObject $object, array $node, Context $context): array
    {
        $types = $node['type'] ?? null;

        if ($context->version->isAtLeast(Version::V3_1_0) || !is_array($types)) {
            return $node;
        }

        $types = array_values(array_filter($types, static fn(mixed $type): bool => $type !== SchemaType::Null->value));

        if ($types === []) {
            $types = [SchemaType::Object->value];
            $node['enum'] = [null];
        }

        $node['type'] = $types;
        $node['nullable'] = true;

        if (count($types) === 1) {
            $context->converted('type null written as nullable: true');
        }

        return $node;
    }
}
