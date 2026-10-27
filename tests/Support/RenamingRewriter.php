<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Support;

use Ark4ne\OpenApiWriter\Objects\Info;
use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Serialization\Context;
use Ark4ne\OpenApiWriter\Serialization\Rewriters\Rewriter;

/**
 * A custom rewriter: replaces the title of an Info.
 */
final class RenamingRewriter implements Rewriter
{
    public function supports(SpecObject $object): bool
    {
        return $object instanceof Info;
    }

    public function rewrite(SpecObject $object, array $node, Context $context): array
    {
        return ['title' => 'Renamed'] + $node;
    }
}
