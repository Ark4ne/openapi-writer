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
     * The library's chain. The order matters where rewriters combine (`nullable` + `$ref` + several types):
     * NullableWithoutTypeRewriter comes after the other Schema rewriters, so that the branch it builds holds keywords
     * already written for the version. The vendor extension rewriters move fields unknown to the version, which the
     * Normalizer would otherwise drop, before NonOAuthScopesRewriter reads the operations of `x-webhooks`.
     */
    public static function default(): self
    {
        return new self(
            new OpenApiVersionRewriter(),
            new NullableRewriter(),
            new NullTypeRewriter(),
            new ExclusiveBoundsRewriter(),
            new ConstRewriter(),
            new SchemaExamplesRewriter(),
            new MultiTypeRewriter(),
            new RefSiblingsRewriter(),
            new ContentMediaTypeRewriter(),
            new ContentEncodingRewriter(),
            new BooleanSchemaRewriter(),
            new NullableWithoutTypeRewriter(),
            new ResponseDescriptionRewriter(),
            new EmptyPathsRewriter(),
            new WebhooksExtensionRewriter(),
            new TagGroupsExtensionRewriter(),
            new NonOAuthScopesRewriter(),
            new MediaTypeReferenceRewriter(),
            new ReservedExtensionRewriter(),
        );
    }

    /**
     * Where a rewriter of the chain writes the content of the unavailable $field of $object, or null when none
     * converts it (UnavailableFieldRewriter).
     */
    public function convertsTo(SpecObject $object, string $field, Context $context): ?string
    {
        foreach ($this->rewriters as $rewriter) {
            if ($rewriter instanceof UnavailableFieldRewriter && $rewriter->supports($object)) {
                $target = $rewriter->convertsTo($object, $field, $context);

                if ($target !== null) {
                    return $target;
                }
            }
        }

        return null;
    }

    /**
     * @param array<string, mixed> $node
     *
     * @return array<string, mixed>
     */
    public function rewrite(SpecObject $object, array $node, Context $context): array
    {
        foreach ($this->rewriters as $rewriter) {
            if ($rewriter->supports($object)) {
                $node = $rewriter->rewrite($object, $node, $context);
            }
        }

        return $node;
    }
}
