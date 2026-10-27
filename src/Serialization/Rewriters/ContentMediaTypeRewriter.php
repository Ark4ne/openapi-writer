<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization\Rewriters;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Objects\Schema;
use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Serialization\Context;

/**
 * Binary content (`contentMediaType`): `format: binary` in 3.0, `contentMediaType` from 3.1. With a `contentEncoding`,
 * the content is text: ContentEncodingRewriter writes the format of base64, any other encoding has no 3.0
 * equivalent. A `format` already set is kept.
 */
final class ContentMediaTypeRewriter implements Rewriter
{
    public function supports(SpecObject $object): bool
    {
        return $object instanceof Schema && $object->has('contentMediaType');
    }

    public function rewrite(SpecObject $object, array $node, Context $context): array
    {
        if ($context->version->isAtLeast(Version::V3_1_0)) {
            return $node;
        }

        unset($node['contentMediaType']);

        if (isset($node['contentEncoding'])) {
            if ($node['contentEncoding'] !== 'base64') {
                $context->at('contentMediaType')->unsupported('contentMediaType of a content encoding other than base64 needs 3.1.0');
            }

            return $node;
        }

        $node['format'] ??= 'binary';
        $context->converted('contentMediaType written as format: binary');

        return $node;
    }
}
