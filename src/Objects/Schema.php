<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Objects;

use Ark4ne\OpenApiWriter\Contracts\SchemaContract;
use Ark4ne\OpenApiWriter\Enums\SchemaType;
use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Exceptions\DuplicateObjectIdException;
use Ark4ne\OpenApiWriter\Exceptions\InvalidArgumentException;
use Ark4ne\OpenApiWriter\Exceptions\MissingObjectIdException;
use Ark4ne\OpenApiWriter\Metadata\Field;
use Ark4ne\OpenApiWriter\Support\Undefined;

/**
 * A JSON Schema: the OpenAPI 3.0 subset, or JSON Schema 2020-12 from 3.1. Keywords are declared in the order of GoldSpec's output, JSON Schema 2020-12 additions next to their family.
 *
 * @see https://spec.openapis.org/oas/v3.2.0.html#schema-object
 *
 * @property-read string|null $schema
 * @property-read string|null $id
 * @property-read string|null $anchor
 * @property-read string|null $dynamicAnchor
 * @property-read string|null $ref
 * @property-read string|null $dynamicRef
 * @property-read array<string, SchemaContract>|null $defs
 * @property-read string|null $comment
 * @property-read string|null $title
 * @property-read string|null $description
 * @property-read list<mixed>|null $enum
 * @property-read mixed $const
 * @property-read mixed $default
 * @property-read string|null $format
 * @property-read list<SchemaType>|null $type
 * @property-read string|null $contentEncoding
 * @property-read string|null $contentMediaType
 * @property-read SchemaContract|null $contentSchema
 * @property-read SchemaContract|bool|null $items
 * @property-read list<SchemaContract>|null $prefixItems
 * @property-read SchemaContract|bool|null $contains
 * @property-read int|null $minContains
 * @property-read int|null $maxContains
 * @property-read int|null $maxItems
 * @property-read int|null $minItems
 * @property-read bool|null $uniqueItems
 * @property-read SchemaContract|bool|null $unevaluatedItems
 * @property-read string|null $pattern
 * @property-read int|null $maxLength
 * @property-read int|null $minLength
 * @property-read int|float|null $maximum
 * @property-read int|float|null $exclusiveMaximum
 * @property-read int|float|null $minimum
 * @property-read int|float|null $exclusiveMinimum
 * @property-read int|float|null $multipleOf
 * @property-read list<string>|null $required
 * @property-read array<string, SchemaContract>|null $properties
 * @property-read array<string, SchemaContract>|null $patternProperties
 * @property-read SchemaContract|bool|null $additionalProperties
 * @property-read SchemaContract|bool|null $propertyNames
 * @property-read SchemaContract|bool|null $unevaluatedProperties
 * @property-read int|null $maxProperties
 * @property-read int|null $minProperties
 * @property-read array<string, list<string>>|null $dependentRequired
 * @property-read array<string, SchemaContract>|null $dependentSchemas
 * @property-read bool|null $nullable
 * @property-read list<SchemaContract>|null $allOf
 * @property-read list<SchemaContract>|null $anyOf
 * @property-read list<SchemaContract>|null $oneOf
 * @property-read SchemaContract|bool|null $not
 * @property-read SchemaContract|bool|null $if
 * @property-read SchemaContract|bool|null $then
 * @property-read SchemaContract|bool|null $else
 * @property-read Discriminator|null $discriminator
 * @property-read bool|null $readOnly
 * @property-read bool|null $writeOnly
 * @property-read Xml|null $xml
 * @property-read ExternalDocumentation|null $externalDocs
 * @property-read mixed $example
 * @property-read list<mixed>|null $examples
 * @property-read bool|null $deprecated
 */
final class Schema extends ExtensibleSpecObject implements SchemaContract
{
    #[Field(name: '$schema', since: Version::V3_1_0)] protected string|Undefined $schema = Undefined::Value;
    #[Field(name: '$id', since: Version::V3_1_0)] protected string|Undefined $id = Undefined::Value;
    #[Field(name: '$anchor', since: Version::V3_1_0)] protected string|Undefined $anchor = Undefined::Value;
    #[Field(name: '$dynamicAnchor', since: Version::V3_1_0)] protected string|Undefined $dynamicAnchor = Undefined::Value;
    #[Field(name: '$ref')] protected string|Undefined $ref = Undefined::Value;
    #[Field(name: '$dynamicRef', since: Version::V3_1_0)] protected string|Undefined $dynamicRef = Undefined::Value;
    /** @var array<string, SchemaContract>|Undefined */
    #[Field(name: '$defs', since: Version::V3_1_0, map: true)] protected array|Undefined $defs = Undefined::Value;
    #[Field(name: '$comment', since: Version::V3_1_0)] protected string|Undefined $comment = Undefined::Value;
    #[Field] protected string|Undefined $title = Undefined::Value;
    #[Field] protected string|Undefined $description = Undefined::Value;
    /** @var list<mixed>|Undefined */
    #[Field] protected array|Undefined $enum = Undefined::Value;
    #[Field] protected mixed $const = Undefined::Value;
    #[Field] protected mixed $default = Undefined::Value;
    #[Field] protected string|Undefined $format = Undefined::Value;
    /** @var list<SchemaType>|Undefined */
    #[Field] protected array|Undefined $type = Undefined::Value;
    #[Field] protected string|Undefined $contentEncoding = Undefined::Value;
    #[Field] protected string|Undefined $contentMediaType = Undefined::Value;
    #[Field(since: Version::V3_1_0)] protected SchemaContract|Undefined $contentSchema = Undefined::Value;
    #[Field] protected SchemaContract|bool|Undefined $items = Undefined::Value;
    /** @var list<SchemaContract>|Undefined */
    #[Field(since: Version::V3_1_0)] protected array|Undefined $prefixItems = Undefined::Value;
    #[Field(since: Version::V3_1_0)] protected SchemaContract|bool|Undefined $contains = Undefined::Value;
    #[Field(since: Version::V3_1_0)] protected int|Undefined $minContains = Undefined::Value;
    #[Field(since: Version::V3_1_0)] protected int|Undefined $maxContains = Undefined::Value;
    #[Field] protected int|Undefined $maxItems = Undefined::Value;
    #[Field] protected int|Undefined $minItems = Undefined::Value;
    #[Field] protected bool|Undefined $uniqueItems = Undefined::Value;
    #[Field(since: Version::V3_1_0)] protected SchemaContract|bool|Undefined $unevaluatedItems = Undefined::Value;
    #[Field] protected string|Undefined $pattern = Undefined::Value;
    #[Field] protected int|Undefined $maxLength = Undefined::Value;
    #[Field] protected int|Undefined $minLength = Undefined::Value;
    #[Field] protected int|float|Undefined $maximum = Undefined::Value;
    #[Field] protected int|float|Undefined $exclusiveMaximum = Undefined::Value;
    #[Field] protected int|float|Undefined $minimum = Undefined::Value;
    #[Field] protected int|float|Undefined $exclusiveMinimum = Undefined::Value;
    #[Field] protected int|float|Undefined $multipleOf = Undefined::Value;
    /** @var list<string>|Undefined */
    #[Field] protected array|Undefined $required = Undefined::Value;
    /** @var array<string, SchemaContract>|Undefined */
    #[Field(map: true)] protected array|Undefined $properties = Undefined::Value;
    /** @var array<string, SchemaContract>|Undefined */
    #[Field(since: Version::V3_1_0, map: true)] protected array|Undefined $patternProperties = Undefined::Value;
    #[Field] protected SchemaContract|bool|Undefined $additionalProperties = Undefined::Value;
    #[Field(since: Version::V3_1_0)] protected SchemaContract|bool|Undefined $propertyNames = Undefined::Value;
    #[Field(since: Version::V3_1_0)] protected SchemaContract|bool|Undefined $unevaluatedProperties = Undefined::Value;
    #[Field] protected int|Undefined $maxProperties = Undefined::Value;
    #[Field] protected int|Undefined $minProperties = Undefined::Value;
    /** @var array<string, list<string>>|Undefined */
    #[Field(since: Version::V3_1_0, map: true, keyedByObjectId: false)] protected array|Undefined $dependentRequired = Undefined::Value;
    /** @var array<string, SchemaContract>|Undefined */
    #[Field(since: Version::V3_1_0, map: true)] protected array|Undefined $dependentSchemas = Undefined::Value;
    #[Field(emit: false)] protected bool|Undefined $nullable = Undefined::Value;
    /** @var list<SchemaContract>|Undefined */
    #[Field] protected array|Undefined $allOf = Undefined::Value;
    /** @var list<SchemaContract>|Undefined */
    #[Field] protected array|Undefined $anyOf = Undefined::Value;
    /** @var list<SchemaContract>|Undefined */
    #[Field] protected array|Undefined $oneOf = Undefined::Value;
    #[Field] protected SchemaContract|bool|Undefined $not = Undefined::Value;
    #[Field(since: Version::V3_1_0)] protected SchemaContract|bool|Undefined $if = Undefined::Value;
    #[Field(since: Version::V3_1_0)] protected SchemaContract|bool|Undefined $then = Undefined::Value;
    #[Field(since: Version::V3_1_0)] protected SchemaContract|bool|Undefined $else = Undefined::Value;
    #[Field] protected Discriminator|Undefined $discriminator = Undefined::Value;
    #[Field] protected bool|Undefined $readOnly = Undefined::Value;
    #[Field] protected bool|Undefined $writeOnly = Undefined::Value;
    #[Field] protected Xml|Undefined $xml = Undefined::Value;
    #[Field] protected ExternalDocumentation|Undefined $externalDocs = Undefined::Value;
    #[Field] protected mixed $example = Undefined::Value;
    /** @var list<mixed>|Undefined */
    #[Field] protected array|Undefined $examples = Undefined::Value;
    #[Field] protected bool|Undefined $deprecated = Undefined::Value;

    public static function string(?string $objectId = null): static
    {
        return static::create($objectId)->type(SchemaType::String);
    }

    public static function integer(?string $objectId = null): static
    {
        return static::create($objectId)->type(SchemaType::Integer);
    }

    public static function number(?string $objectId = null): static
    {
        return static::create($objectId)->type(SchemaType::Number);
    }

    public static function boolean(?string $objectId = null): static
    {
        return static::create($objectId)->type(SchemaType::Boolean);
    }

    public static function array(?string $objectId = null): static
    {
        return static::create($objectId)->type(SchemaType::Array);
    }

    public static function object(?string $objectId = null): static
    {
        return static::create($objectId)->type(SchemaType::Object);
    }

    public static function null(?string $objectId = null): static
    {
        return static::create($objectId)->type(SchemaType::Null);
    }

    /**
     * Schema holding a `$ref` to `#/components/schemas/<name>` (a value containing `#` or `/` is written as is). Sibling
     * fields and `nullable()` stay available; they are converted for 3.0. `$ref` has no instance setter: it would share
     * this name.
     *
     * @param string|null $objectId key of the schema in its parent
     */
    public static function ref(string $ref, ?string $objectId = null): static
    {
        return static::create($objectId)->with('ref', self::componentPath('schemas', $ref));
    }

    /**
     * Sets `$schema`, the dialect of this schema.
     */
    public function schema(?string $schema): static
    {
        return $this->with('schema', $schema);
    }

    /**
     * Sets `$id`.
     */
    public function id(?string $id): static
    {
        return $this->with('id', $id);
    }

    /**
     * Sets `$anchor`.
     */
    public function anchor(?string $anchor): static
    {
        return $this->with('anchor', $anchor);
    }

    /**
     * Sets `$dynamicAnchor`.
     */
    public function dynamicAnchor(?string $dynamicAnchor): static
    {
        return $this->with('dynamicAnchor', $dynamicAnchor);
    }

    /**
     * Sets `$dynamicRef`.
     */
    public function dynamicRef(?string $dynamicRef): static
    {
        return $this->with('dynamicRef', $dynamicRef);
    }

    /**
     * Sets `$defs`, keyed by the objectId of each schema.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function defs(SchemaContract ...$defs): static
    {
        return $this->with('defs', $defs);
    }

    /**
     * Appends entries keyed by their objectId; an objectId already present throws.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function addDefs(SchemaContract ...$defs): static
    {
        return $this->withAdded('defs', $defs);
    }

    /**
     * Sets `$comment`.
     */
    public function comment(?string $comment): static
    {
        return $this->with('comment', $comment);
    }

    public function title(?string $title): static
    {
        return $this->with('title', $title);
    }

    public function description(?string $description): static
    {
        return $this->with('description', $description);
    }

    public function enum(mixed ...$enum): static
    {
        return $this->with('enum', $enum);
    }

    public function addEnum(mixed ...$enum): static
    {
        return $this->withAdded('enum', $enum);
    }

    /**
     * Written as `enum: [value]` in 3.0.
     *
     * `null` is written as a value; `without('const')` removes the field.
     */
    public function const(mixed $const): static
    {
        return $this->with('const', $const);
    }

    /**
     * `null` is written as a value; `without('default')` removes the field.
     */
    public function default(mixed $default): static
    {
        return $this->with('default', $default);
    }

    public function format(?string $format): static
    {
        return $this->with('format', $format);
    }

    /**
     * Several types are written as `anyOf` in 3.0; `SchemaType::Null` is converted too.
     *
     * @throws InvalidArgumentException when a string is not a SchemaType value, or a type is given twice
     */
    public function type(SchemaType|string ...$type): static
    {
        return $this->with('type', self::types([], $type));
    }

    /**
     * Appends types; a type already present throws.
     *
     * @throws InvalidArgumentException when a string is not a SchemaType value, or a type is given twice
     */
    public function addType(SchemaType|string ...$type): static
    {
        return $this->with('type', self::types($this->type instanceof Undefined ? [] : $this->type, $type));
    }

    /**
     * @param list<SchemaType>                $types
     * @param array<array-key, SchemaType|string> $added
     *
     * @return list<SchemaType>
     *
     * @throws InvalidArgumentException
     */
    private static function types(array $types, array $added): array
    {
        foreach ($added as $value) {
            $case = $value instanceof SchemaType ? $value : SchemaType::tryFrom($value);
            $case ?? throw InvalidArgumentException::forEnumValue(self::class, 'type', SchemaType::class, $value);

            if (in_array($case, $types, true)) {
                throw InvalidArgumentException::forField(self::class, 'type', sprintf('"%s" is given twice', $case->value));
            }

            $types[] = $case;
        }

        return $types;
    }

    /**
     * `base64` is written as `format: byte` in 3.0.
     */
    public function contentEncoding(?string $contentEncoding): static
    {
        return $this->with('contentEncoding', $contentEncoding);
    }

    /**
     * A binary media type is written as `format: binary` in 3.0.
     */
    public function contentMediaType(?string $contentMediaType): static
    {
        return $this->with('contentMediaType', $contentMediaType);
    }

    public function contentSchema(?SchemaContract $contentSchema): static
    {
        return $this->with('contentSchema', $contentSchema);
    }

    /**
     * A boolean schema is written `{}` (true) or `{not: {}}` (false) in 3.0.
     */
    public function items(SchemaContract|bool|null $items): static
    {
        return $this->with('items', $items);
    }

    public function prefixItems(SchemaContract ...$prefixItems): static
    {
        return $this->with('prefixItems', $prefixItems);
    }

    public function addPrefixItems(SchemaContract ...$prefixItems): static
    {
        return $this->withAdded('prefixItems', $prefixItems);
    }

    /**
     * A boolean schema is written `{}` (true) or `{not: {}}` (false) in 3.0.
     */
    public function contains(SchemaContract|bool|null $contains): static
    {
        return $this->with('contains', $contains);
    }

    public function minContains(?int $minContains): static
    {
        if ($minContains !== null && $minContains < 0) {
            throw InvalidArgumentException::forField(self::class, 'minContains', 'must be zero or more');
        }

        return $this->with('minContains', $minContains);
    }

    public function maxContains(?int $maxContains): static
    {
        if ($maxContains !== null && $maxContains < 0) {
            throw InvalidArgumentException::forField(self::class, 'maxContains', 'must be zero or more');
        }

        return $this->with('maxContains', $maxContains);
    }

    public function maxItems(?int $maxItems): static
    {
        if ($maxItems !== null && $maxItems < 0) {
            throw InvalidArgumentException::forField(self::class, 'maxItems', 'must be zero or more');
        }

        return $this->with('maxItems', $maxItems);
    }

    public function minItems(?int $minItems): static
    {
        if ($minItems !== null && $minItems < 0) {
            throw InvalidArgumentException::forField(self::class, 'minItems', 'must be zero or more');
        }

        return $this->with('minItems', $minItems);
    }

    public function uniqueItems(?bool $uniqueItems = true): static
    {
        return $this->with('uniqueItems', $uniqueItems);
    }

    public function unevaluatedItems(SchemaContract|bool|null $unevaluatedItems): static
    {
        return $this->with('unevaluatedItems', $unevaluatedItems);
    }

    public function pattern(?string $pattern): static
    {
        return $this->with('pattern', $pattern);
    }

    public function maxLength(?int $maxLength): static
    {
        if ($maxLength !== null && $maxLength < 0) {
            throw InvalidArgumentException::forField(self::class, 'maxLength', 'must be zero or more');
        }

        return $this->with('maxLength', $maxLength);
    }

    public function minLength(?int $minLength): static
    {
        if ($minLength !== null && $minLength < 0) {
            throw InvalidArgumentException::forField(self::class, 'minLength', 'must be zero or more');
        }

        return $this->with('minLength', $minLength);
    }

    public function maximum(int|float|null $maximum): static
    {
        return $this->with('maximum', $maximum);
    }

    /**
     * Numeric bound; written as `maximum` + `exclusiveMaximum: true` in 3.0.
     */
    public function exclusiveMaximum(int|float|null $exclusiveMaximum): static
    {
        return $this->with('exclusiveMaximum', $exclusiveMaximum);
    }

    public function minimum(int|float|null $minimum): static
    {
        return $this->with('minimum', $minimum);
    }

    /**
     * Numeric bound; written as `minimum` + `exclusiveMinimum: true` in 3.0.
     */
    public function exclusiveMinimum(int|float|null $exclusiveMinimum): static
    {
        return $this->with('exclusiveMinimum', $exclusiveMinimum);
    }

    public function multipleOf(int|float|null $multipleOf): static
    {
        if ($multipleOf !== null && $multipleOf <= 0) {
            throw InvalidArgumentException::forField(self::class, 'multipleOf', 'must be greater than zero');
        }

        return $this->with('multipleOf', $multipleOf);
    }

    public function required(string ...$required): static
    {
        return $this->with('required', $required);
    }

    public function addRequired(string ...$required): static
    {
        return $this->withAdded('required', $required);
    }

    /**
     * Keyed by the objectId of each schema, the property name.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function properties(SchemaContract ...$properties): static
    {
        return $this->with('properties', $properties);
    }

    /**
     * Appends entries keyed by their objectId; an objectId already present throws.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function addProperties(SchemaContract ...$properties): static
    {
        return $this->withAdded('properties', $properties);
    }

    /**
     * Keyed by the objectId of each schema, the pattern.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function patternProperties(SchemaContract ...$patternProperties): static
    {
        return $this->with('patternProperties', $patternProperties);
    }

    /**
     * Appends entries keyed by their objectId; an objectId already present throws.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function addPatternProperties(SchemaContract ...$patternProperties): static
    {
        return $this->withAdded('patternProperties', $patternProperties);
    }

    public function additionalProperties(SchemaContract|bool|null $additionalProperties): static
    {
        return $this->with('additionalProperties', $additionalProperties);
    }

    /**
     * A boolean schema is written `{}` (true) or `{not: {}}` (false) in 3.0.
     */
    public function propertyNames(SchemaContract|bool|null $propertyNames): static
    {
        return $this->with('propertyNames', $propertyNames);
    }

    public function unevaluatedProperties(SchemaContract|bool|null $unevaluatedProperties): static
    {
        return $this->with('unevaluatedProperties', $unevaluatedProperties);
    }

    public function maxProperties(?int $maxProperties): static
    {
        if ($maxProperties !== null && $maxProperties < 0) {
            throw InvalidArgumentException::forField(self::class, 'maxProperties', 'must be zero or more');
        }

        return $this->with('maxProperties', $maxProperties);
    }

    public function minProperties(?int $minProperties): static
    {
        if ($minProperties !== null && $minProperties < 0) {
            throw InvalidArgumentException::forField(self::class, 'minProperties', 'must be zero or more');
        }

        return $this->with('minProperties', $minProperties);
    }

    /**
     * Required properties, keyed by the property that requires them.
     *
     * @param array<string, list<string>> $dependentRequired
     */
    public function dependentRequired(array $dependentRequired): static
    {
        return $this->with('dependentRequired', $dependentRequired);
    }

    /**
     * Appends entries; a key already present throws.
     *
     * @param array<string, list<string>> $dependentRequired
     *
     * @throws DuplicateObjectIdException
     */
    public function addDependentRequired(array $dependentRequired): static
    {
        return $this->withAdded('dependentRequired', $dependentRequired);
    }

    /**
     * Keyed by the objectId of each schema, the property that triggers it.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function dependentSchemas(SchemaContract ...$dependentSchemas): static
    {
        return $this->with('dependentSchemas', $dependentSchemas);
    }

    /**
     * Appends entries keyed by their objectId; an objectId already present throws.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function addDependentSchemas(SchemaContract ...$dependentSchemas): static
    {
        return $this->withAdded('dependentSchemas', $dependentSchemas);
    }

    /**
     * Intent written as `nullable: true` in 3.0 and as a `"null"` type from 3.1.
     */
    public function nullable(?bool $nullable = true): static
    {
        return $this->with('nullable', $nullable);
    }

    public function allOf(SchemaContract ...$allOf): static
    {
        return $this->with('allOf', $allOf);
    }

    public function addAllOf(SchemaContract ...$allOf): static
    {
        return $this->withAdded('allOf', $allOf);
    }

    public function anyOf(SchemaContract ...$anyOf): static
    {
        return $this->with('anyOf', $anyOf);
    }

    public function addAnyOf(SchemaContract ...$anyOf): static
    {
        return $this->withAdded('anyOf', $anyOf);
    }

    public function oneOf(SchemaContract ...$oneOf): static
    {
        return $this->with('oneOf', $oneOf);
    }

    public function addOneOf(SchemaContract ...$oneOf): static
    {
        return $this->withAdded('oneOf', $oneOf);
    }

    /**
     * A boolean schema is written `{}` (true) or `{not: {}}` (false) in 3.0.
     */
    public function not(SchemaContract|bool|null $not): static
    {
        return $this->with('not', $not);
    }

    /**
     * A boolean schema is written `{}` (true) or `{not: {}}` (false) in 3.0.
     */
    public function if(SchemaContract|bool|null $if): static
    {
        return $this->with('if', $if);
    }

    /**
     * A boolean schema is written `{}` (true) or `{not: {}}` (false) in 3.0.
     */
    public function then(SchemaContract|bool|null $then): static
    {
        return $this->with('then', $then);
    }

    /**
     * A boolean schema is written `{}` (true) or `{not: {}}` (false) in 3.0.
     */
    public function else(SchemaContract|bool|null $else): static
    {
        return $this->with('else', $else);
    }

    public function discriminator(?Discriminator $discriminator): static
    {
        return $this->with('discriminator', $discriminator);
    }

    public function readOnly(?bool $readOnly = true): static
    {
        return $this->with('readOnly', $readOnly);
    }

    public function writeOnly(?bool $writeOnly = true): static
    {
        return $this->with('writeOnly', $writeOnly);
    }

    public function xml(?Xml $xml): static
    {
        return $this->with('xml', $xml);
    }

    public function externalDocs(?ExternalDocumentation $externalDocs): static
    {
        return $this->with('externalDocs', $externalDocs);
    }

    /**
     * `null` is written as a value; `without('example')` removes the field.
     */
    public function example(mixed $example): static
    {
        return $this->with('example', $example);
    }

    /**
     * Written as `example: <first>` in 3.0.
     */
    public function examples(mixed ...$examples): static
    {
        return $this->with('examples', $examples);
    }

    public function addExamples(mixed ...$examples): static
    {
        return $this->withAdded('examples', $examples);
    }

    public function deprecated(?bool $deprecated = true): static
    {
        return $this->with('deprecated', $deprecated);
    }
}
