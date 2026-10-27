<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Objects;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Exceptions\InvalidArgumentException;
use Ark4ne\OpenApiWriter\Metadata\Field;
use Ark4ne\OpenApiWriter\Support\Undefined;

/**
 * XML representation of a schema.
 *
 * @see https://spec.openapis.org/oas/v3.2.0.html#xml-object
 *
 * @property-read string|null $nodeType
 * @property-read string|null $name
 * @property-read string|null $namespace
 * @property-read string|null $prefix
 * @property-read bool|null $attribute
 * @property-read bool|null $wrapped
 */
final class Xml extends ExtensibleSpecObject
{
    private const NODE_TYPES = ['element', 'attribute', 'text', 'cdata', 'none'];

    #[Field(since: Version::V3_2_0)] protected string|Undefined $nodeType = Undefined::Value;
    #[Field] protected string|Undefined $name = Undefined::Value;
    #[Field] protected string|Undefined $namespace = Undefined::Value;
    #[Field] protected string|Undefined $prefix = Undefined::Value;
    #[Field] protected bool|Undefined $attribute = Undefined::Value;
    #[Field] protected bool|Undefined $wrapped = Undefined::Value;

    /**
     * `element`, `attribute`, `text`, `cdata` or `none`. It replaces `attribute` and `wrapped`, which cannot be set
     * next to it.
     *
     * @throws InvalidArgumentException on another node type, or next to `attribute` or `wrapped`
     */
    public function nodeType(?string $nodeType): static
    {
        if ($nodeType !== null) {
            if (!in_array($nodeType, self::NODE_TYPES, true)) {
                throw InvalidArgumentException::forField(self::class, 'nodeType', sprintf('expected one of %s, got "%s"', implode(', ', self::NODE_TYPES), $nodeType));
            }

            $this->exclusiveOf('nodeType', 'attribute');
            $this->exclusiveOf('nodeType', 'wrapped');
        }

        return $this->with('nodeType', $nodeType);
    }

    public function name(?string $name): static
    {
        return $this->with('name', $name);
    }

    public function namespace(?string $namespace): static
    {
        return $this->with('namespace', $namespace);
    }

    public function prefix(?string $prefix): static
    {
        return $this->with('prefix', $prefix);
    }

    public function attribute(?bool $attribute = true): static
    {
        if ($attribute !== null) {
            $this->exclusiveOf('attribute', 'nodeType');
        }

        return $this->with('attribute', $attribute);
    }

    public function wrapped(?bool $wrapped = true): static
    {
        if ($wrapped !== null) {
            $this->exclusiveOf('wrapped', 'nodeType');
        }

        return $this->with('wrapped', $wrapped);
    }
}
