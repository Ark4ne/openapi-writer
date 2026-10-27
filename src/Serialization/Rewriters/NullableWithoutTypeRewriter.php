<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization\Rewriters;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Objects\Schema;
use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Serialization\Context;

/**
 * `nullable()` on a `$ref` or without type: `anyOf: [{$ref}, {type: object, nullable: true, enum: [null]}]` in 3.0, `anyOf: [{$ref}, {type: "null"}]` from 3.1.
 */
final class NullableWithoutTypeRewriter implements Rewriter
{
    public function supports(SpecObject $object): bool
    {
        return $object instanceof Schema && $object->nullable === true && !$object->has('type') && $object->has('ref');
    }

    public function rewrite(SpecObject $object, array $node, Context $context): array
    {
        if (!$context->version->isAtLeast(Version::V3_1_0) || isset($node['anyOf'])) {
            return $node;
        }

        $node['anyOf'] = [['$ref' => $node['$ref'] ?? null], ['type' => 'null']];
        unset($node['$ref']);
        $context->converted('nullable $ref written as anyOf: [{$ref}, {type: "null"}]');

        return $node;
    }
}
