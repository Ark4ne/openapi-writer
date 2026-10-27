<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Objects;

use Ark4ne\OpenApiWriter\Contracts\Extensible;
use Ark4ne\OpenApiWriter\Exceptions\DuplicateObjectIdException;
use Ark4ne\OpenApiWriter\Exceptions\InvalidArgumentException;
use Ark4ne\OpenApiWriter\Exceptions\MissingObjectIdException;
use Ark4ne\OpenApiWriter\Metadata\Field;
use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Tests\TestCase;
use BackedEnum;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionProperty;
use ReflectionUnionType;

/**
 * Contract shared by the 29 objects (Template Method): construction, objectId, immutability, every field through
 * its setter, both methods of every list and map.
 *
 * A concrete test names its class and gives, for every `#[Field]` property, the arguments of its setter and the
 * value read back; it adds its own cases (semantic shortcuts, forbidden values). Extensible objects extend
 * `ExtensibleSpecObjectTestCase`, which adds the `x-*` extensions.
 *
 * Only `fields()` feeds a data provider: the derived groups (lists, maps, enums…) can be empty for an object, so
 * their tests loop over them instead.
 */
abstract class SpecObjectTestCase extends TestCase
{
    /**
     * @return class-string<SpecObject>
     */
    abstract protected static function objectClass(): string;

    /**
     * Keyed by property name, in declaration order: the arguments of the setter, and the value then read by
     * `__get`. Map fields take objects with distinct objectIds (or, for maps of values, one array) and read back
     * keyed by objectId. Objects are compared by identity, so the same instances appear in both.
     *
     * @return array<string, array{list<mixed>, mixed}>
     */
    abstract public static function fields(): array;

    /**
     * @return iterable<string, array{string, list<mixed>, mixed}>
     */
    public static function fieldCases(): iterable
    {
        foreach (static::fields() as $field => [$arguments, $expected]) {
            yield $field => [$field, $arguments, $expected];
        }
    }

    public function test_create_sets_no_field(): void
    {
        $object = static::emptyObject();

        foreach (self::fieldNames() as $field) {
            self::assertFalse($object->has($field), $field);
            self::assertNull($object->__get($field), $field);
        }
    }

    public function test_create_stores_the_object_id(): void
    {
        $class = static::objectClass();

        self::assertSame('key', $class::create('key')->objectId);
        self::assertNull($class::create()->objectId);
    }

    public function test_object_id_returns_a_copy_with_the_new_key(): void
    {
        $class = static::objectClass();
        $object = $class::create('before');

        $renamed = $object->objectId('after');

        self::assertSame('after', $renamed->objectId);
        self::assertSame('before', $object->objectId, 'The original object is unchanged.');
    }

    public function test_fields_provider_covers_every_field_in_declaration_order(): void
    {
        self::assertSame(self::fieldNames(), array_keys(static::fields()));
    }

    public function test_every_field_has_a_setter_and_every_list_or_map_an_add_method(): void
    {
        $class = new ReflectionClass(static::objectClass());

        foreach (self::fieldNames() as $field) {
            self::assertTrue($class->hasMethod($field), sprintf('%s() is missing.', $field));

            if (self::isCollection($field)) {
                self::assertTrue($class->hasMethod('add' . ucfirst($field)), sprintf('add%s() is missing.', ucfirst($field)));
            }
        }
    }

    public function test_extensibility_follows_the_specification(): void
    {
        self::assertSame(static::extensible(), is_subclass_of(static::objectClass(), Extensible::class));
    }

    /**
     * @param list<mixed> $arguments
     */
    #[DataProvider('fieldCases')]
    public function test_setter_writes_its_field_on_a_copy(string $field, array $arguments, mixed $expected): void
    {
        $object = static::emptyObject();

        $updated = self::call($object, $field, $arguments);

        self::assertNotSame($object, $updated);
        self::assertSame($expected, $updated->__get($field));
        self::assertTrue($updated->has($field));
        self::assertTrue($updated->__isset($field));
        self::assertFalse($object->has($field), 'The original object is unchanged.');
    }

    /**
     * @param list<mixed> $arguments
     */
    #[DataProvider('fieldCases')]
    public function test_without_removes_a_field_on_a_copy(string $field, array $arguments, mixed $expected): void
    {
        $object = self::call(static::emptyObject(), $field, $arguments);
        self::assertSame($expected, $object->__get($field));

        $removed = $object->without($field);

        self::assertFalse($removed->has($field));
        self::assertNull($removed->__get($field));
        self::assertTrue($object->has($field), 'The original object is unchanged.');
    }

    public function test_setter_with_null_resets_a_typed_field(): void
    {
        $checked = [];

        foreach (static::fields() as $field => [$arguments]) {
            if (!self::isTyped($field) || !self::setterTakesOneNullable($field)) {
                continue;
            }

            $object = self::call(static::emptyObject(), $field, $arguments);
            self::assertTrue($object->has($field), $field);

            $reset = self::call($object, $field, [null]);

            self::assertFalse($reset->has($field), $field);
            self::assertNull($reset->__get($field), $field);
            $checked[] = $field;
        }

        self::assertSame(self::fieldsWhere(fn(string $field): bool => self::isTyped($field) && self::setterTakesOneNullable($field)), $checked);
    }

    public function test_setter_with_null_writes_null_on_a_free_value_field(): void
    {
        $checked = [];

        foreach (static::fields() as $field => [$arguments]) {
            if (self::isTyped($field)) {
                continue;
            }

            $nulled = self::call(self::call(static::emptyObject(), $field, $arguments), $field, [null]);

            self::assertTrue($nulled->has($field), $field);
            self::assertNull($nulled->__get($field), $field);
            $checked[] = $field;
        }

        self::assertSame(self::fieldsWhere(fn(string $field): bool => !self::isTyped($field)), $checked);
    }

    public function test_setter_replaces_a_whole_list_and_add_appends_to_it(): void
    {
        $checked = [];

        foreach (static::fields() as $field => [$arguments, $expected]) {
            if (!self::isCollection($field) || self::attribute($field)->map) {
                continue;
            }

            self::assertIsArray($expected);
            $object = self::call(static::emptyObject(), $field, $arguments);

            self::assertSame($expected, self::call($object, $field, $arguments)->__get($field), $field);
            self::assertSame([...$expected, ...$expected], self::call($object, 'add' . ucfirst($field), $arguments)->__get($field), $field);
            self::assertSame($expected, $object->__get($field), 'The original object is unchanged.');
            $checked[] = $field;
        }

        self::assertSame(self::fieldsWhere(fn(string $field): bool => self::isCollection($field) && !self::attribute($field)->map), $checked);
    }

    public function test_add_on_an_empty_map_sets_it_and_never_replaces_a_key(): void
    {
        $checked = [];

        foreach (static::fields() as $field => [$arguments, $expected]) {
            if (!self::attribute($field)->map) {
                continue;
            }

            $add = 'add' . ucfirst($field);
            self::assertSame($expected, self::call(static::emptyObject(), $add, $arguments)->__get($field), $field);

            $object = self::call(static::emptyObject(), $field, $arguments);
            self::assertThrows(DuplicateObjectIdException::class, static fn(): SpecObject => self::call($object, $add, $arguments), $field);
            $checked[] = $field;
        }

        self::assertSame(self::fieldsWhere(fn(string $field): bool => self::attribute($field)->map), $checked);
    }

    public function test_both_methods_of_a_map_of_objects_need_distinct_object_ids(): void
    {
        $checked = [];

        foreach (static::fields() as $field => [$arguments]) {
            $entry = $arguments[0] ?? null;

            if (!self::attribute($field)->map || !self::attribute($field)->keyedByObjectId || !$entry instanceof SpecObject) {
                continue;
            }

            $add = 'add' . ucfirst($field);
            $object = static::emptyObject();
            self::assertThrows(DuplicateObjectIdException::class, static fn(): SpecObject => self::call($object, $field, [$entry, $entry]), $field);
            self::assertThrows(DuplicateObjectIdException::class, static fn(): SpecObject => self::call($object, $add, [$entry, $entry]), $field);
            self::assertThrows(MissingObjectIdException::class, static fn(): SpecObject => self::call($object, $field, [$entry->objectId(null)]), $field);
            self::assertThrows(MissingObjectIdException::class, static fn(): SpecObject => self::call($object, $add, [$entry->objectId(null)]), $field);
            $checked[] = $field;
        }

        self::assertSame(self::fieldsWhere(fn(string $field): bool => self::attribute($field)->map && self::attribute($field)->keyedByObjectId), $checked);
    }

    public function test_enum_fields_reject_an_unknown_value(): void
    {
        $checked = [];

        foreach (self::fieldNames() as $field) {
            if (!self::isEnum($field)) {
                continue;
            }

            self::assertThrows(InvalidArgumentException::class, static fn(): SpecObject => self::call(static::emptyObject(), $field, ['not-a-case']), $field);
            $checked[] = $field;
        }

        self::assertSame(self::fieldsWhere(self::isEnum(...)), $checked);
    }

    protected static function extensible(): bool
    {
        return true;
    }

    protected static function emptyObject(): SpecObject
    {
        $class = static::objectClass();

        return $class::create();
    }

    /**
     * Calls a method by name (the setters are named exactly as the fields).
     *
     * @param list<mixed> $arguments
     */
    protected static function call(SpecObject $object, string $method, array $arguments): SpecObject
    {
        $result = (new ReflectionMethod($object, $method))->invoke($object, ...$arguments);

        self::assertInstanceOf(SpecObject::class, $result);

        return $result;
    }

    /**
     * @return list<string>
     */
    private static function fieldNames(): array
    {
        $names = [];

        foreach ((new ReflectionClass(static::objectClass()))->getProperties() as $property) {
            if ($property->getAttributes(Field::class) !== []) {
                $names[] = $property->getName();
            }
        }

        return $names;
    }

    /**
     * Field names matching the predicate. Fields missing from fields() are caught by their own test.
     *
     * @param callable(string): bool $predicate
     *
     * @return list<string>
     */
    private static function fieldsWhere(callable $predicate): array
    {
        return array_values(array_filter(array_keys(static::fields()), $predicate));
    }

    private static function attribute(string $field): Field
    {
        $attributes = (new ReflectionProperty(static::objectClass(), $field))->getAttributes(Field::class);

        return $attributes[0]->newInstance();
    }

    /**
     * @return list<string>
     */
    private static function types(string $field): array
    {
        $type = (new ReflectionProperty(static::objectClass(), $field))->getType();
        $types = $type instanceof ReflectionUnionType ? $type->getTypes() : [$type];
        $names = [];

        foreach ($types as $type) {
            if ($type instanceof ReflectionNamedType) {
                $names[] = $type->getName();
            }
        }

        return $names;
    }

    private static function isTyped(string $field): bool
    {
        return !in_array('mixed', self::types($field), true);
    }

    private static function isCollection(string $field): bool
    {
        return in_array('array', self::types($field), true);
    }

    private static function isEnum(string $field): bool
    {
        foreach (self::types($field) as $type) {
            if (is_subclass_of($type, BackedEnum::class)) {
                return true;
            }
        }

        return false;
    }

    private static function setterTakesOneNullable(string $field): bool
    {
        $parameters = (new ReflectionMethod(static::objectClass(), $field))->getParameters();

        return count($parameters) === 1 && !$parameters[0]->isVariadic() && $parameters[0]->allowsNull();
    }
}
