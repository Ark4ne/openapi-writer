<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization\Rewriters;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Objects\Schema;
use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Serialization\Context;

/**
 * Base64 content (`contentEncoding`): `format: byte` in 3.0, `contentEncoding: base64` from 3.1. Any other encoding
 * has no 3.0 equivalent; a `format` already set is kept.
 */
final class ContentEncodingRewriter implements Rewriter
{
    public function supports(SpecObject $object): bool
    {
        return $object instanceof Schema && $object->has('contentEncoding');
    }

    public function rewrite(SpecObject $object, array $node, Context $context): array
    {
        $encoding = $node['contentEncoding'] ?? null;

        if ($context->version->isAtLeast(Version::V3_1_0) || $encoding === null) {
            return $node;
        }

        unset($node['contentEncoding']);

        if ($encoding !== 'base64') {
            $context->at('contentEncoding')->unsupported(sprintf('contentEncoding "%s" needs 3.1.0', is_string($encoding) ? $encoding : ''));

            return $node;
        }

        $node['format'] ??= 'byte';
        $context->converted('contentEncoding: base64 written as format: byte');

        return $node;
    }
}
