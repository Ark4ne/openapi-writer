<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization\Rewriters;

use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Serialization\Context;

/**
 * Applies, in order, every rewriter that supports a node (Chain of Responsibility).
 */
final readonly class RewriterChain
{
    /**
     * @var list<Rewriter>
     */
    public array $rewriters;

    public function __construct(Rewriter ...$rewriters)
    {
        $this->rewriters = array_values($rewriters);
    }

    /**
     * The library's chain. The order matters where rewriters combine (`nullable` + `$ref` + several types).
     */
    public static function default(): self
    {
        return new self(
            new OpenApiVersionRewriter(),
            new NullableRewriter(),
            new NullableWithoutTypeRewriter(),
            new NullTypeRewriter(),
            new ExclusiveBoundsRewriter(),
            new ConstRewriter(),
            new SchemaExamplesRewriter(),
            new MultiTypeRewriter(),
            new RefSiblingsRewriter(),
            new ContentMediaTypeRewriter(),
            new ContentEncodingRewriter(),
            new BooleanSchemaRewriter(),
            new ResponseDescriptionRewriter(),
            new EmptyPathsRewriter(),
            new NonOAuthScopesRewriter(),
            new MediaTypeReferenceRewriter(),
            new ReservedExtensionRewriter(),
        );
    }

    /**
     * @param array<string, mixed> $node
     *
     * @return array<string, mixed>
     */
    public function rewrite(SpecObject $object, array $node, Context $context): array
    {
        return $node;
    }
}
