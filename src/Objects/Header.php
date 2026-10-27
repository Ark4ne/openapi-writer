<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Objects;

use Ark4ne\OpenApiWriter\Contracts\ExampleContract;
use Ark4ne\OpenApiWriter\Contracts\HeaderContract;
use Ark4ne\OpenApiWriter\Contracts\MediaTypeContract;
use Ark4ne\OpenApiWriter\Contracts\SchemaContract;
use Ark4ne\OpenApiWriter\Enums\ParameterStyle;
use Ark4ne\OpenApiWriter\Exceptions\DuplicateObjectIdException;
use Ark4ne\OpenApiWriter\Exceptions\InvalidArgumentException;
use Ark4ne\OpenApiWriter\Exceptions\MissingObjectIdException;
use Ark4ne\OpenApiWriter\Metadata\Field;
use Ark4ne\OpenApiWriter\Support\Undefined;

/**
 * A response or encoding header; its objectId is the header name. `example` and `examples` are mutually exclusive.
 *
 * @see https://spec.openapis.org/oas/v3.2.0.html#header-object
 *
 * @property-read string|null $description
 * @property-read bool|null $required
 * @property-read bool|null $deprecated
 * @property-read ParameterStyle|null $style
 * @property-read bool|null $explode
 * @property-read SchemaContract|null $schema
 * @property-read mixed $example
 * @property-read array<string, ExampleContract>|null $examples
 * @property-read array<string, MediaTypeContract>|null $content
 */
final class Header extends ExtensibleSpecObject implements HeaderContract
{
    #[Field] protected string|Undefined $description = Undefined::Value;
    #[Field] protected bool|Undefined $required = Undefined::Value;
    #[Field] protected bool|Undefined $deprecated = Undefined::Value;
    #[Field] protected ParameterStyle|Undefined $style = Undefined::Value;
    #[Field] protected bool|Undefined $explode = Undefined::Value;
    #[Field] protected SchemaContract|Undefined $schema = Undefined::Value;
    #[Field] protected mixed $example = Undefined::Value;
    /** @var array<string, ExampleContract>|Undefined */
    #[Field(map: true)] protected array|Undefined $examples = Undefined::Value;
    /** @var array<string, MediaTypeContract>|Undefined */
    #[Field(map: true)] protected array|Undefined $content = Undefined::Value;

    /**
     * Reference to `#/components/headers/<name>`. The path is built here, since a Reference does not know its
     * section; a value containing `#` or `/` is written as is.
     *
     * @param string|null $objectId key of the reference in its parent
     */
    public static function ref(string $ref, ?string $objectId = null): Reference
    {
        return Reference::to(self::componentPath('headers', $ref), $objectId);
    }

    public function description(?string $description): static
    {
        return $this->with('description', $description);
    }

    public function required(?bool $required = true): static
    {
        return $this->with('required', $required);
    }

    public function deprecated(?bool $deprecated = true): static
    {
        return $this->with('deprecated', $deprecated);
    }

    /**
     * @throws InvalidArgumentException when the string is not a value of the enum
     */
    public function style(ParameterStyle|string|null $style): static
    {
        return $this->with('style', $style);
    }

    public function explode(?bool $explode = true): static
    {
        return $this->with('explode', $explode);
    }

    public function schema(?SchemaContract $schema): static
    {
        return $this->with('schema', $schema);
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
        $this->exclusiveOf('examples', 'example');

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
        $this->exclusiveOf('examples', 'example');

        return $this->withAdded('examples', $examples);
    }

    /**
     * Keyed by the objectId of each entry.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function content(MediaTypeContract ...$content): static
    {
        return $this->with('content', $content);
    }

    /**
     * Appends entries keyed by their objectId; an objectId already present throws.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function addContent(MediaTypeContract ...$content): static
    {
        return $this->withAdded('content', $content);
    }
}
