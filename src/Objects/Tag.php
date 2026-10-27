<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Objects;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Metadata\Field;
use Ark4ne\OpenApiWriter\Support\Undefined;

/**
 * Metadata of a tag used by operations.
 *
 * @see https://spec.openapis.org/oas/v3.2.0.html#tag-object
 *
 * @property-read string|null $name
 * @property-read string|null $summary
 * @property-read string|null $description
 * @property-read ExternalDocumentation|null $externalDocs
 * @property-read string|null $parent
 * @property-read string|null $kind
 */
final class Tag extends ExtensibleSpecObject
{
    #[Field] protected string|Undefined $name = Undefined::Value;
    #[Field(since: Version::V3_2_0)] protected string|Undefined $summary = Undefined::Value;
    #[Field] protected string|Undefined $description = Undefined::Value;
    #[Field] protected ExternalDocumentation|Undefined $externalDocs = Undefined::Value;
    #[Field(since: Version::V3_2_0)] protected string|Undefined $parent = Undefined::Value;
    #[Field(since: Version::V3_2_0)] protected string|Undefined $kind = Undefined::Value;

    public function name(?string $name): static
    {
        return $this->with('name', $name);
    }

    public function summary(?string $summary): static
    {
        return $this->with('summary', $summary);
    }

    public function description(?string $description): static
    {
        return $this->with('description', $description);
    }

    public function externalDocs(?ExternalDocumentation $externalDocs): static
    {
        return $this->with('externalDocs', $externalDocs);
    }

    /**
     * Name of the parent tag. Dropped before 3.2, never converted to `x-tagGroups`.
     */
    public function parent(?string $parent): static
    {
        return $this->with('parent', $parent);
    }

    public function kind(?string $kind): static
    {
        return $this->with('kind', $kind);
    }
}
