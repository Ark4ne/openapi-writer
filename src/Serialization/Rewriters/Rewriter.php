<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization\Rewriters;

use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Serialization\Context;

/**
 * Handles one difference between versions (one row of the conversion tables of the specification), for every
 * version. It reads the intent from the model and writes it for `$context->version`, reporting what it does.
 */
interface Rewriter
{
    public function supports(SpecObject $object): bool;

    /**
     * A rewriter keeps the empty-object convention: an empty object or map nested in the node is a `\stdClass`.
     *
     * @param array<string, mixed> $node the normalized node of $object
     *
     * @return array<string, mixed> the node rewritten for `$context->version`
     */
    public function rewrite(SpecObject $object, array $node, Context $context): array;
}
