<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Support;

use Ark4ne\OpenApiWriter\Objects\Info;
use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Serialization\Context;
use Ark4ne\OpenApiWriter\Serialization\Rewriters\UnavailableFieldRewriter;

/**
 * A custom rewriter that announces the conversion of `Info.summary` and leaves the node unchanged.
 */
final class AnnouncingRewriter implements UnavailableFieldRewriter
{
    public function supports(SpecObject $object): bool
    {
        return $object instanceof Info;
    }

    public function convertsTo(SpecObject $object, string $field, Context $context): ?string
    {
        return $field === 'summary' ? 'x-summary' : null;
    }

    public function rewrite(SpecObject $object, array $node, Context $context): array
    {
        return $node;
    }
}
