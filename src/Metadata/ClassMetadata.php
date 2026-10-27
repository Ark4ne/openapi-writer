<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Metadata;

/**
 * The `#[Field]` declarations of one spec object class, in declaration order (= output order).
 */
final readonly class ClassMetadata
{
    /**
     * @param class-string         $class
     * @param array<string, Field> $fields keyed by property name
     */
    public function __construct(
        public string $class,
        public array $fields,
    ) {}

    public function field(string $property): ?Field
    {
        return $this->fields[$property] ?? null;
    }

    /**
     * Name of the field in the output: `Field::$name`, or the property name.
     */
    public function outputName(string $property): string
    {
        return '';
    }
}
