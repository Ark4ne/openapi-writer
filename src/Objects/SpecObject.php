<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Objects;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Exceptions\DuplicateObjectIdException;
use Ark4ne\OpenApiWriter\Exceptions\InvalidArgumentException;
use Ark4ne\OpenApiWriter\Exceptions\MissingDependencyException;
use Ark4ne\OpenApiWriter\Exceptions\MissingObjectIdException;
use Ark4ne\OpenApiWriter\Exceptions\UnsupportedFeatureException;
use Ark4ne\OpenApiWriter\Metadata\ClassMetadata;
use Ark4ne\OpenApiWriter\Metadata\Field;
use Ark4ne\OpenApiWriter\Metadata\MetadataFactory;
use Ark4ne\OpenApiWriter\Serialization\Serializer;
use Ark4ne\OpenApiWriter\Support\JsonPointer;
use Ark4ne\OpenApiWriter\Support\Undefined;

/**
 * Base class of every OpenAPI object: an immutable value object.
 *
 * Fields are `#[Field]` properties, declared in the order of the specification, written only through `with()` and
 * `withAdded()` (which return a modified clone) and read through `__get`. A field never set reads `null`; `has()`
 * tells it apart from a field set to `null`.
 *
 * @property-read string|null $objectId key of the object in its parent (property, component, status code, path…)
 */
abstract class SpecObject
{
    final protected function __construct(protected ?string $objectId = null) {}

    /**
     * @param string|null $objectId key of the object in its parent
     */
    public static function create(?string $objectId = null): static
    {
        return new static($objectId);
    }

    public function objectId(?string $objectId): static
    {
        $clone = clone $this;
        $clone->objectId = $objectId;

        return $clone;
    }

    /**
     * Removes a field: it no longer appears in the output, even for free-value fields where `null` is a value.
     *
     * @throws InvalidArgumentException when the object has no such field
     */
    public function without(string $field): static
    {
        $metadata = self::metadata();
        self::attribute($metadata, $field);

        $clone = clone $this;
        $metadata->write($clone, $field, Undefined::Value);

        return $clone;
    }

    /**
     * Whether the field was set, `null` included for free-value fields.
     */
    public function has(string $field): bool
    {
        $metadata = self::metadata();

        return $metadata->field($field) !== null && $metadata->read($this, $field) !== Undefined::Value;
    }

    /**
     * Value of a field, or `null` when it was never set.
     *
     * @throws InvalidArgumentException when the object has no such field
     */
    public function __get(string $name): mixed
    {
        if ($name === 'objectId') {
            return $this->objectId;
        }

        $metadata = self::metadata();
        self::attribute($metadata, $name);
        $value = $metadata->read($this, $name);

        return $value === Undefined::Value ? null : $value;
    }

    public function __isset(string $name): bool
    {
        return $name === 'objectId' ? $this->objectId !== null : $this->has($name);
    }

    /**
     * Writes the object for the given version (default: the `openapi` field of an OpenApi, else 3.1.2), in strict
     * mode. Use `Serializer::for()` to get the diagnostics or to serialize in lenient mode.
     * Empty objects and maps are written as `\stdClass`, so that JSON writes `{}` and never `[]`.
     *
     * @return array<string, mixed>
     *
     * @throws UnsupportedFeatureException
     */
    public function toArray(Version|string|null $version = null): array
    {
        return Serializer::for($version ?? $this->declaredVersion())->serialize($this)->toArray();
    }

    /**
     * An empty object is written `{}`, at the top level too.
     *
     * @param int $flags added to the default flags (`JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE |
     *                   JSON_PRESERVE_ZERO_FRACTION`), e.g. `JSON_PRETTY_PRINT`
     *
     * @throws UnsupportedFeatureException
     */
    public function toJson(Version|string|null $version = null, int $flags = 0): string
    {
        return Serializer::for($version ?? $this->declaredVersion())->serialize($this)->toJson($flags);
    }

    /**
     * @throws UnsupportedFeatureException
     * @throws MissingDependencyException when `symfony/yaml` is not installed
     */
    public function toYaml(Version|string|null $version = null): string
    {
        return Serializer::for($version ?? $this->declaredVersion())->serialize($this)->toYaml();
    }

    /**
     * Path of a component for the `::ref()` shortcuts: a bare name gives `#/components/<section>/<name>`, a value
     * containing `#` or `/` is written as is.
     */
    protected static function componentPath(string $section, string $ref): string
    {
        if (str_contains($ref, '#') || str_contains($ref, '/')) {
            return $ref;
        }

        return '#' . JsonPointer::root()->append('components', $section, $ref)->toString();
    }

    /**
     * Guards a field that the specification forbids next to other ones in every version (`value` and
     * `externalValue`…): setting it while one of them is set throws. An empty collection does not count as set; an
     * empty array in a free-value field (`example([])`) is a value and does.
     *
     * @throws InvalidArgumentException when one of $others is set
     */
    protected function exclusiveOf(string $field, string ...$others): void
    {
        $metadata = MetadataFactory::for(static::class);

        foreach ($others as $other) {
            if ($this->has($other) && ($metadata->isFreeValue($other) || $this->__get($other) !== [])) {
                throw InvalidArgumentException::forField(static::class, $field, sprintf('%s and %s are mutually exclusive', $other, $field));
            }
        }
    }

    /**
     * Version written when none is given; null for the library default (3.1.2).
     */
    protected function declaredVersion(): ?Version
    {
        return null;
    }

    /**
     * Sets a field on a clone. For typed fields `null` resets the field; for free-value fields (`mixed`) `null` is a
     * value. A string given for an enum field is converted with `::from()`. A map field is keyed by objectId.
     *
     * @throws InvalidArgumentException
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    protected function with(string $field, mixed $value): static
    {
        $metadata = self::metadata();
        $attribute = self::attribute($metadata, $field);

        if ($metadata->isFreeValue($field)) {
            // A free value, `null` and arrays included, is kept as given.
        } elseif ($value === null) {
            $value = Undefined::Value;
        } elseif (is_array($value)) {
            $value = self::collection($field, $attribute, $value, []);
        } elseif (is_string($value) || is_int($value)) {
            $value = self::enumCase($metadata, $field, $value);
        }

        $clone = clone $this;
        $metadata->write($clone, $field, $value);

        return $clone;
    }

    /**
     * Appends items to a list or map field on a clone. A map never replaces an existing entry.
     *
     * @param array<array-key, mixed> $items
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    protected function withAdded(string $field, array $items): static
    {
        $metadata = self::metadata();
        $attribute = self::attribute($metadata, $field);
        $current = $metadata->read($this, $field);

        $clone = clone $this;
        $metadata->write($clone, $field, self::collection($field, $attribute, $items, is_array($current) ? $current : []));

        return $clone;
    }

    private static function metadata(): ClassMetadata
    {
        return MetadataFactory::for(static::class);
    }

    /**
     * @throws InvalidArgumentException when the object has no such field
     */
    private static function attribute(ClassMetadata $metadata, string $field): Field
    {
        return $metadata->field($field) ?? throw InvalidArgumentException::forField(static::class, $field, 'no such field');
    }

    /**
     * A list appends the items; a map of objects keys them by objectId; a map of values keeps their keys. A map never
     * replaces an entry already present.
     *
     * @param array<array-key, mixed> $items
     * @param array<array-key, mixed> $current
     *
     * @return array<array-key, mixed>
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    private static function collection(string $field, Field $attribute, array $items, array $current): array
    {
        if (!$attribute->map) {
            return [...array_values($current), ...array_values($items)];
        }

        foreach ($items as $key => $item) {
            if ($attribute->keyedByObjectId) {
                $key = $item instanceof self ? $item->objectId : null;
                $key ?? throw MissingObjectIdException::forField(static::class, $field);
            }

            if (array_key_exists($key, $current)) {
                throw DuplicateObjectIdException::forField(static::class, $field, (string) $key);
            }

            $current[$key] = $item;
        }

        return $current;
    }

    /**
     * The case of the enum a field holds, from its value; other values are returned as is.
     *
     * @throws InvalidArgumentException when the value is not one of the enum
     */
    private static function enumCase(ClassMetadata $metadata, string $field, string|int $value): mixed
    {
        $enum = $metadata->enum($field);

        if ($enum === null) {
            return $value;
        }

        return $enum::tryFrom($value) ?? throw InvalidArgumentException::forEnumValue(static::class, $field, $enum, $value);
    }
}
