<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization\Rewriters;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Objects\Header;
use Ark4ne\OpenApiWriter\Objects\Parameter;
use Ark4ne\OpenApiWriter\Objects\Reference;
use Ark4ne\OpenApiWriter\Objects\RequestBody;
use Ark4ne\OpenApiWriter\Objects\Response;
use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Serialization\Context;
use stdClass;

/**
 * A Reference in a `content` map (Media Type Object or Reference): unsupported before 3.2.
 */
final class MediaTypeReferenceRewriter implements Rewriter
{
    public function supports(SpecObject $object): bool
    {
        return $object instanceof Parameter || $object instanceof Header || $object instanceof RequestBody || $object instanceof Response;
    }

    public function rewrite(SpecObject $object, array $node, Context $context): array
    {
        $content = $node['content'] ?? null;

        if ($context->version->isAtLeast(Version::V3_2_0) || !is_array($content) || !($object instanceof Parameter || $object instanceof Header || $object instanceof RequestBody || $object instanceof Response)) {
            return $node;
        }

        foreach ($object->content ?? [] as $mediaType => $entry) {
            if ($entry instanceof Reference) {
                unset($content[$mediaType]);
                $context->at('content', $mediaType)->unsupported('a Reference to a Media Type needs 3.2.0');
            }
        }

        $node['content'] = $content === [] ? new stdClass() : $content;

        return $node;
    }
}
