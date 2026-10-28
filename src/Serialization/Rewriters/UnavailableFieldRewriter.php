<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization\Rewriters;

use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Serialization\Context;

/**
 * A rewriter that writes, in another form, a field unknown to the target version. The Normalizer only writes such a
 * field in the node when a rewriter of the chain converts it; any other unavailable field is left out and reported.
 */
interface UnavailableFieldRewriter extends Rewriter
{
    /**
     * Where `rewrite()` writes the content of the unavailable $field of $object: a key of the node (`x-webhooks`), at
     * which the diagnostics of that content point. Null when this rewriter does not convert it.
     */
    public function convertsTo(SpecObject $object, string $field, Context $context): ?string;
}
