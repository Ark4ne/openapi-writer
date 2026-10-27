<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization\Rewriters;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Objects\Schema;
use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Serialization\Context;

/**
 * `$ref` with sibling fields: `allOf: [{$ref}]` + siblings in 3.0, kept as is from 3.1. In 3.0, a Reference Object
 * ignores its siblings; the reference is put first in an `allOf` already present.
 */
final class RefSiblingsRewriter implements Rewriter
{
    public function supports(SpecObject $object): bool
    {
        // A nullable schema without type is written by NullableWithoutTypeRewriter, which wraps its branch.
        return $object instanceof Schema && $object->has('ref') && ($object->has('type') || $object->nullable !== true);
    }

    public function rewrite(SpecObject $object, array $node, Context $context): array
    {
        if ($context->version->isAtLeast(Version::V3_1_0) || !self::hasSiblings($node)) {
            return $node;
        }

        $context->converted('$ref with siblings written as allOf: [{$ref}]');

        return self::wrap($node);
    }

    /**
     * @param array<string, mixed> $node
     */
    public static function hasSiblings(array $node): bool
    {
        return isset($node['$ref']) && count($node) > 1;
    }

    /**
     * The 3.0 form of a schema with a `$ref` and siblings.
     *
     * @param array<string, mixed> $node
     *
     * @return array<string, mixed>
     */
    public static function wrap(array $node): array
    {
        $node['allOf'] = [['$ref' => $node['$ref']], ...(is_array($node['allOf'] ?? null) ? $node['allOf'] : [])];
        unset($node['$ref']);

        return $node;
    }
}
