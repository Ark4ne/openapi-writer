<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Objects;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Exceptions\DuplicateObjectIdException;
use Ark4ne\OpenApiWriter\Exceptions\InvalidArgumentException;
use Ark4ne\OpenApiWriter\Exceptions\MissingDependencyException;
use Ark4ne\OpenApiWriter\Exceptions\MissingObjectIdException;
use Ark4ne\OpenApiWriter\Exceptions\UnsupportedFeatureException;

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
        return clone $this;
    }

    /**
     * Removes a field: it no longer appears in the output, even for free-value fields where `null` is a value.
     */
    public function without(string $field): static
    {
        return clone $this;
    }

    /**
     * Whether the field was set, `null` included for free-value fields.
     */
    public function has(string $field): bool
    {
        return false;
    }

    /**
     * Value of a field, or `null` when it was never set.
     */
    public function __get(string $name): mixed
    {
        return null;
    }

    public function __isset(string $name): bool
    {
        return false;
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
        return [];
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
        return '';
    }

    /**
     * @throws UnsupportedFeatureException
     * @throws MissingDependencyException when `symfony/yaml` is not installed
     */
    public function toYaml(Version|string|null $version = null): string
    {
        return '';
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
        return clone $this;
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
        return clone $this;
    }
}
