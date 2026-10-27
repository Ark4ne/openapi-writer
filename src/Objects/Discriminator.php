<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Objects;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Exceptions\DuplicateObjectIdException;
use Ark4ne\OpenApiWriter\Metadata\Field;
use Ark4ne\OpenApiWriter\Support\Undefined;

/**
 * Selects the schema of a polymorphic payload from a property value.
 *
 * @see https://spec.openapis.org/oas/v3.2.0.html#discriminator-object
 *
 * @property-read string|null $propertyName
 * @property-read array<string, string>|null $mapping
 * @property-read string|null $defaultMapping
 */
final class Discriminator extends ExtensibleSpecObject
{
    #[Field] protected string|Undefined $propertyName = Undefined::Value;
    /** @var array<string, string>|Undefined */
    #[Field(map: true, keyedByObjectId: false)] protected array|Undefined $mapping = Undefined::Value;
    #[Field(since: Version::V3_2_0)] protected string|Undefined $defaultMapping = Undefined::Value;

    public function propertyName(?string $propertyName): static
    {
        return $this->with('propertyName', $propertyName);
    }

    /**
     * Schema names or references, keyed by property value.
     *
     * @param array<string, string> $mapping
     */
    public function mapping(array $mapping): static
    {
        return $this->with('mapping', $mapping);
    }

    /**
     * Appends entries; a key already present throws.
     *
     * @param array<string, string> $mapping
     *
     * @throws DuplicateObjectIdException
     */
    public function addMapping(array $mapping): static
    {
        return $this->withAdded('mapping', $mapping);
    }

    public function defaultMapping(?string $defaultMapping): static
    {
        return $this->with('defaultMapping', $defaultMapping);
    }
}
