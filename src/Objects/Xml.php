<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Objects;

use Ark4ne\OpenApiWriter\Enums\Version;
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
    #[Field(since: Version::V3_2_0)] protected string|Undefined $nodeType = Undefined::Value;
    #[Field] protected string|Undefined $name = Undefined::Value;
    #[Field] protected string|Undefined $namespace = Undefined::Value;
    #[Field] protected string|Undefined $prefix = Undefined::Value;
    #[Field] protected bool|Undefined $attribute = Undefined::Value;
    #[Field] protected bool|Undefined $wrapped = Undefined::Value;

    /**
     * `element`, `attribute`, `text`, `cdata` or `none`.
     */
    public function nodeType(?string $nodeType): static
    {
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
        return $this->with('attribute', $attribute);
    }

    public function wrapped(?bool $wrapped = true): static
    {
        return $this->with('wrapped', $wrapped);
    }
}
