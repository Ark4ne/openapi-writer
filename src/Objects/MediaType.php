<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Objects;

use Ark4ne\OpenApiWriter\Contracts\ExampleContract;
use Ark4ne\OpenApiWriter\Contracts\MediaTypeContract;
use Ark4ne\OpenApiWriter\Contracts\SchemaContract;
use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Exceptions\DuplicateObjectIdException;
use Ark4ne\OpenApiWriter\Exceptions\MissingObjectIdException;
use Ark4ne\OpenApiWriter\Metadata\Field;
use Ark4ne\OpenApiWriter\Support\Undefined;

/**
 * Schema and examples of one media type; its objectId is the media type. `example` and `examples` are mutually exclusive,
 * and so are `encoding` (the parts of a form) and `prefixEncoding` / `itemEncoding` (the items of a sequence).
 *
 * @see https://spec.openapis.org/oas/v3.2.0.html#media-type-object
 *
 * @property-read string|null $description
 * @property-read SchemaContract|null $schema
 * @property-read SchemaContract|null $itemSchema
 * @property-read mixed $example
 * @property-read array<string, ExampleContract>|null $examples
 * @property-read array<string, Encoding>|null $encoding
 * @property-read list<Encoding>|null $prefixEncoding
 * @property-read Encoding|null $itemEncoding
 */
final class MediaType extends ExtensibleSpecObject implements MediaTypeContract
{
    #[Field(since: Version::V3_2_0)] protected string|Undefined $description = Undefined::Value;
    #[Field] protected SchemaContract|Undefined $schema = Undefined::Value;
    #[Field(since: Version::V3_2_0)] protected SchemaContract|Undefined $itemSchema = Undefined::Value;
    #[Field] protected mixed $example = Undefined::Value;
    /** @var array<string, ExampleContract>|Undefined */
    #[Field(map: true)] protected array|Undefined $examples = Undefined::Value;
    /** @var array<string, Encoding>|Undefined */
    #[Field(map: true)] protected array|Undefined $encoding = Undefined::Value;
    /** @var list<Encoding>|Undefined */
    #[Field(since: Version::V3_2_0)] protected array|Undefined $prefixEncoding = Undefined::Value;
    #[Field(since: Version::V3_2_0)] protected Encoding|Undefined $itemEncoding = Undefined::Value;

    public static function json(): static
    {
        return static::create('application/json');
    }

    public static function xml(): static
    {
        return static::create('application/xml');
    }

    public static function formUrlEncoded(): static
    {
        return static::create('application/x-www-form-urlencoded');
    }

    public static function multipart(): static
    {
        return static::create('multipart/form-data');
    }

    /**
     * Any media type or media type range: `MediaType::of('text/csv')`.
     */
    public static function of(string $mediaType): static
    {
        return static::create($mediaType);
    }

    /**
     * Reference to `#/components/mediaTypes/<name>`. The path is built here, since a Reference does not know its
     * section; a value containing `#` or `/` is written as is.
     *
     * @param string|null $objectId key of the reference in its parent
     */
    public static function ref(string $ref, ?string $objectId = null): Reference
    {
        return Reference::to(self::componentPath('mediaTypes', $ref), $objectId);
    }

    public function description(?string $description): static
    {
        return $this->with('description', $description);
    }

    public function schema(?SchemaContract $schema): static
    {
        return $this->with('schema', $schema);
    }

    public function itemSchema(?SchemaContract $itemSchema): static
    {
        return $this->with('itemSchema', $itemSchema);
    }

    /**
     * `null` is written as a value; `without('example')` removes the field.
     */
    public function example(mixed $example): static
    {
        $this->exclusiveOf('example', 'examples');

        return $this->with('example', $example);
    }

    /**
     * Keyed by the objectId of each entry.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function examples(ExampleContract ...$examples): static
    {
        if ($examples !== []) {
            $this->exclusiveOf('examples', 'example');
        }

        return $this->with('examples', $examples);
    }

    /**
     * Appends entries keyed by their objectId; an objectId already present throws.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function addExamples(ExampleContract ...$examples): static
    {
        if ($examples !== []) {
            $this->exclusiveOf('examples', 'example');
        }

        return $this->withAdded('examples', $examples);
    }

    /**
     * Keyed by the objectId of each Encoding, the name of the property it applies to.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function encoding(Encoding ...$encoding): static
    {
        if ($encoding !== []) {
            $this->exclusiveOf('encoding', 'prefixEncoding', 'itemEncoding');
        }

        return $this->with('encoding', $encoding);
    }

    /**
     * Appends entries keyed by their objectId; an objectId already present throws.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function addEncoding(Encoding ...$encoding): static
    {
        if ($encoding !== []) {
            $this->exclusiveOf('encoding', 'prefixEncoding', 'itemEncoding');
        }

        return $this->withAdded('encoding', $encoding);
    }

    public function prefixEncoding(Encoding ...$prefixEncoding): static
    {
        if ($prefixEncoding !== []) {
            $this->exclusiveOf('prefixEncoding', 'encoding');
        }

        return $this->with('prefixEncoding', $prefixEncoding);
    }

    public function addPrefixEncoding(Encoding ...$prefixEncoding): static
    {
        if ($prefixEncoding !== []) {
            $this->exclusiveOf('prefixEncoding', 'encoding');
        }

        return $this->withAdded('prefixEncoding', $prefixEncoding);
    }

    public function itemEncoding(?Encoding $itemEncoding): static
    {
        if ($itemEncoding !== null) {
            $this->exclusiveOf('itemEncoding', 'encoding');
        }

        return $this->with('itemEncoding', $itemEncoding);
    }
}
