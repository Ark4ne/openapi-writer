<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Validation\Rules;

use Ark4ne\OpenApiWriter\Enums\Version;

/**
 * Where a walk stands in a written document: an object of the spec, whose keys are fields, or a map, whose keys are
 * names chosen by the author. It tells which values are free (`example`, `default`, extensions…), where a `$ref` or a
 * `schema` key is data, not structure.
 *
 * @internal
 */
final readonly class Scope
{
    /**
     * Fields whose value is free in every object that has them.
     */
    private const FREE = ['example', 'default', 'const', 'enum', 'value', 'dataValue'];

    /**
     * Fields whose value is a map of names. `parameters` is a list everywhere but in `components` and Link, where its
     * keys are names too (the official schemas only accept strings as values of Link parameters).
     */
    private const MAPS_OF_NAMES = [
        'properties', 'patternProperties', '$defs', 'definitions', 'dependentSchemas', 'schemas', 'responses', 'parameters',
        'examples', 'requestBodies', 'headers', 'securitySchemes', 'links', 'callbacks', 'pathItems', 'mediaTypes',
        'content', 'encoding', 'paths', 'webhooks', 'scopes', 'variables', 'mapping', 'additionalOperations',
    ];

    /**
     * @param string|null $field      the field that holds this node; null for the root and for an entry of a map
     * @param bool        $extensible for a map: its `x-` keys are extensions (Paths, Responses, Callback)
     * @param bool        $link       for an object: it is a Link, whose `requestBody` is free
     * @param bool        $xWebhooks  for the root before 3.1: its `x-webhooks` holds webhooks (Redoc extension)
     */
    private function __construct(
        public ?string $field,
        private bool $map,
        private bool $extensible,
        private bool $link,
        private bool $xWebhooks = false,
    ) {}

    public static function root(Version $version): self
    {
        return new self(null, false, false, false, !$version->isAtLeast(Version::V3_1_0));
    }

    public function isMap(): bool
    {
        return $this->map;
    }

    /**
     * The scope of the value of $key in this node, or null when that value is free.
     */
    public function enter(int|string $key, mixed $value): ?self
    {
        if ($this->map) {
            if ($this->extensible && Document::isExtension($key)) {
                return null;
            }

            // An entry of `callbacks` is a Callback: a map of runtime expressions, with extensions.
            return $this->field === 'callbacks'
                ? new self(null, true, true, false)
                : new self(null, false, false, $this->field === 'links');
        }

        if ($this->xWebhooks && $key === 'x-webhooks') {
            return new self('webhooks', true, false, false);
        }

        if (in_array($key, self::FREE, true) || Document::isExtension($key)
            || $key === 'examples' && is_array($value) && array_is_list($value)
            || $this->link && $key === 'requestBody') {
            return null;
        }

        if (in_array($key, self::MAPS_OF_NAMES, true)) {
            // `components.responses` is a map of names; elsewhere `responses` is a Responses object, with extensions.
            return new self($key, true, $key === 'paths' || $key === 'responses' && $this->field !== 'components', false);
        }

        return new self((string) $key, false, false, false);
    }
}
