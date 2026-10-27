<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Metadata;

use Ark4ne\OpenApiWriter\Enums\Version;
use Attribute;

/**
 * Declares a property of a spec object as an OpenAPI field. Everything serialization needs to know about the field
 * is declared here, never coded.
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final readonly class Field
{
    /**
     * @param string|null  $name      name in the output when it differs from the property (`$ref`, `$self`…)
     * @param Version|null $since     first version that knows the field
     * @param Version|null $until     last version that knows the field
     * @param bool         $map             map field, written `{}` even when empty
     * @param bool         $emit            false for an intent that only a rewriter writes (`nullable`)
     * @param bool         $patterned       map whose entries are written directly in the object (Responses, Callback…)
     * @param bool         $keyedByObjectId map of objects keyed by their objectId (variadic setters); false for a map of
     *                                      values whose setters take an `array<string, T>` (`OAuthFlow.scopes`…)
     */
    public function __construct(
        public ?string $name = null,
        public ?Version $since = null,
        public ?Version $until = null,
        public bool $map = false,
        public bool $emit = true,
        public bool $patterned = false,
        public bool $keyedByObjectId = true,
    ) {}
}
