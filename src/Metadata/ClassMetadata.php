<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Metadata;

use BackedEnum;
use ReflectionProperty;

/**
 * The `#[Field]` declarations of one spec object class, in declaration order (= output order).
 */
final readonly class ClassMetadata
{
    /**
     * @var array<string, int> position of each output name
     */
    private array $ranks;

    private ?int $patternedRank;

    /**
     * @param class-string                                 $class
     * @param array<string, Field>                         $fields     keyed by property name
     * @param array<string, ReflectionProperty>            $properties the property of each field
     * @param array<string, class-string<BackedEnum>|null> $enums      the enum a field holds, if any
     * @param array<string, bool>                          $freeValues whether a field takes any value (`mixed`), `null` included
     */
    public function __construct(
        public string $class,
        public array $fields,
        private array $properties = [],
        private array $enums = [],
        private array $freeValues = [],
    ) {
        $ranks = [];
        $patternedRank = null;

        foreach ($fields as $property => $field) {
            $patternedRank = $field->patterned ? count($ranks) : $patternedRank;
            $ranks[$field->name ?? $property] = count($ranks);
        }

        $this->ranks = $ranks;
        $this->patternedRank = $patternedRank;
    }

    public function field(string $property): ?Field
    {
        return $this->fields[$property] ?? null;
    }

    /**
     * Name of the field in the output: `Field::$name`, or the property name.
     */
    public function outputName(string $property): string
    {
        return $this->fields[$property]->name ?? $property;
    }

    /**
     * Position of a key in the output: the order of the fields; the entries of a patterned field at the place of that
     * field, unknown keys after the fields, extensions last.
     */
    public function rank(int|string $key): int
    {
        return $this->ranks[$key]
            ?? (str_starts_with((string) $key, 'x-') ? PHP_INT_MAX : ($this->patternedRank ?? PHP_INT_MAX - 1));
    }

    /**
     * The enum held by a field (`ParameterLocation` for `Parameter.in`), or null.
     *
     * @return class-string<BackedEnum>|null
     */
    public function enum(string $property): ?string
    {
        return $this->enums[$property] ?? null;
    }

    /**
     * Whether a field takes any value (`default`, `example`, `const`…), where `null` is a value.
     */
    public function isFreeValue(string $property): bool
    {
        return $this->freeValues[$property] ?? false;
    }

    public function read(object $object, string $property): mixed
    {
        return $this->properties[$property]->getValue($object);
    }

    /**
     * Writes a field of an object. Only `SpecObject::with()` and its siblings call it, on a fresh clone.
     */
    public function write(object $object, string $property, mixed $value): void
    {
        $this->properties[$property]->setValue($object, $value);
    }
}
