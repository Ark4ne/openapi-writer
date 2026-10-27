<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization\Rewriters;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Objects\Schema;
use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Serialization\Context;

/**
 * `nullable()` without type (a `$ref`, a composition…): the keywords that constrain the value move to the first branch
 * of `anyOf: [{…}, {type: object, nullable: true, enum: [null]}]` in 3.0, `anyOf: [{…}, {type: "null"}]` from 3.1;
 * annotations (`description`, `example`…), identifiers and extensions stay outside. A schema made of annotations only
 * already accepts `null` and is left as is.
 */
final class NullableWithoutTypeRewriter implements Rewriter
{
    /**
     * Keywords that stay next to `anyOf`: they do not constrain the value.
     */
    private const OUTSIDE = [
        '$schema', '$id', '$anchor', '$dynamicAnchor', '$defs', '$comment', 'title', 'description', 'default',
        'readOnly', 'writeOnly', 'xml', 'externalDocs', 'example', 'examples', 'deprecated',
    ];

    public function supports(SpecObject $object): bool
    {
        return $object instanceof Schema && $object->nullable === true && !$object->has('type');
    }

    public function rewrite(SpecObject $object, array $node, Context $context): array
    {
        $branch = array_filter(
            $node,
            static fn(string $key): bool => !in_array($key, self::OUTSIDE, true) && !str_starts_with($key, 'x-'),
            ARRAY_FILTER_USE_KEY,
        );

        if ($branch === []) {
            return $node;
        }

        $null = $context->version->isAtLeast(Version::V3_1_0) ? ['type' => 'null'] : NullTypeRewriter::NULL_SCHEMA;
        $node = array_diff_key($node, $branch);
        $node['anyOf'] = [$branch, $null];
        $context->converted('nullable without type written as anyOf: [{…}, null]');

        return $node;
    }
}
