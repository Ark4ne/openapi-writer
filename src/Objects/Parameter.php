<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Objects;

use Ark4ne\OpenApiWriter\Contracts\ExampleContract;
use Ark4ne\OpenApiWriter\Contracts\MediaTypeContract;
use Ark4ne\OpenApiWriter\Contracts\ParameterContract;
use Ark4ne\OpenApiWriter\Contracts\SchemaContract;
use Ark4ne\OpenApiWriter\Enums\ParameterLocation;
use Ark4ne\OpenApiWriter\Enums\ParameterStyle;
use Ark4ne\OpenApiWriter\Exceptions\DuplicateObjectIdException;
use Ark4ne\OpenApiWriter\Exceptions\InvalidArgumentException;
use Ark4ne\OpenApiWriter\Exceptions\MissingObjectIdException;
use Ark4ne\OpenApiWriter\Metadata\Field;
use Ark4ne\OpenApiWriter\Support\Undefined;

/**
 * A single operation parameter, identified by `name` and `in`. `example` and `examples` are mutually exclusive.
 *
 * @see https://spec.openapis.org/oas/v3.2.0.html#parameter-object
 *
 * @property-read string|null $name
 * @property-read ParameterLocation|null $in
 * @property-read string|null $description
 * @property-read bool|null $required
 * @property-read bool|null $deprecated
 * @property-read bool|null $allowEmptyValue
 * @property-read ParameterStyle|null $style
 * @property-read bool|null $explode
 * @property-read bool|null $allowReserved
 * @property-read SchemaContract|null $schema
 * @property-read mixed $example
 * @property-read array<string, ExampleContract>|null $examples
 * @property-read array<string, MediaTypeContract>|null $content
 */
final class Parameter extends ExtensibleSpecObject implements ParameterContract
{
    #[Field] protected string|Undefined $name = Undefined::Value;
    #[Field] protected ParameterLocation|Undefined $in = Undefined::Value;
    #[Field] protected string|Undefined $description = Undefined::Value;
    #[Field] protected bool|Undefined $required = Undefined::Value;
    #[Field] protected bool|Undefined $deprecated = Undefined::Value;
    #[Field] protected bool|Undefined $allowEmptyValue = Undefined::Value;
    #[Field] protected ParameterStyle|Undefined $style = Undefined::Value;
    #[Field] protected bool|Undefined $explode = Undefined::Value;
    #[Field] protected bool|Undefined $allowReserved = Undefined::Value;
    #[Field] protected SchemaContract|Undefined $schema = Undefined::Value;
    #[Field] protected mixed $example = Undefined::Value;
    /** @var array<string, ExampleContract>|Undefined */
    #[Field(map: true)] protected array|Undefined $examples = Undefined::Value;
    /** @var array<string, MediaTypeContract>|Undefined */
    #[Field(map: true)] protected array|Undefined $content = Undefined::Value;

    public static function path(string $name, ?string $objectId = null): static
    {
        return static::create($objectId)->name($name)->in(ParameterLocation::Path)->required();
    }

    public static function query(string $name, ?string $objectId = null): static
    {
        return static::create($objectId)->name($name)->in(ParameterLocation::Query);
    }

    public static function header(string $name, ?string $objectId = null): static
    {
        return static::create($objectId)->name($name)->in(ParameterLocation::Header);
    }

    public static function cookie(string $name, ?string $objectId = null): static
    {
        return static::create($objectId)->name($name)->in(ParameterLocation::Cookie);
    }

    public static function querystring(string $name, ?string $objectId = null): static
    {
        return static::create($objectId)->name($name)->in(ParameterLocation::Querystring);
    }

    /**
     * Reference to `#/components/parameters/<name>`. The path is built here, since a Reference does not know its
     * section; a value containing `#` or `/` is written as is.
     *
     * @param string|null $objectId key of the reference in its parent
     */
    public static function ref(string $ref, ?string $objectId = null): Reference
    {
        return Reference::to(self::componentPath('parameters', $ref), $objectId);
    }

    public function name(?string $name): static
    {
        return $this->with('name', $name);
    }

    /**
     * @throws InvalidArgumentException when the string is not a value of the enum
     */
    public function in(ParameterLocation|string|null $in): static
    {
        return $this->with('in', $in);
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

    public function allowEmptyValue(?bool $allowEmptyValue = true): static
    {
        return $this->with('allowEmptyValue', $allowEmptyValue);
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

    public function allowReserved(?bool $allowReserved = true): static
    {
        return $this->with('allowReserved', $allowReserved);
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
