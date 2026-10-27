<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Objects;

use Ark4ne\OpenApiWriter\Enums\SchemaType;
use Ark4ne\OpenApiWriter\Exceptions\InvalidArgumentException;
use Ark4ne\OpenApiWriter\Objects\Discriminator;
use Ark4ne\OpenApiWriter\Objects\ExternalDocumentation;
use Ark4ne\OpenApiWriter\Objects\Reference;
use Ark4ne\OpenApiWriter\Objects\Schema;
use Ark4ne\OpenApiWriter\Objects\Xml;
use Closure;
use PHPUnit\Framework\Attributes\DataProvider;

final class SchemaTest extends ExtensibleSpecObjectTestCase
{
    protected static function objectClass(): string
    {
        return Schema::class;
    }

    protected static function listsOfUniqueValues(): array
    {
        return ['type'];
    }

    public static function fields(): array
    {
        $address = Schema::object('Address');
        $string = Schema::string();
        $integer = Schema::integer();
        $positive = Schema::integer()->minimum(1);
        $first = Schema::string();
        $second = Schema::integer();
        $name = Schema::string('name');
        $email = Schema::string('email');
        $extension = Schema::string('^x-');
        $billing = Schema::object('billingAddress');
        $user = Reference::to('#/components/schemas/User');
        $admin = Schema::object()->addRequired('role');
        $guest = Schema::null();
        $discriminator = Discriminator::create()->propertyName('petType');
        $xml = Xml::create()->name('user');
        $docs = ExternalDocumentation::create()->url('https://example.com/docs/user');

        return [
            'schema' => [['https://spec.openapis.org/oas/3.1/dialect/base'], 'https://spec.openapis.org/oas/3.1/dialect/base'],
            'id' => [['https://example.com/schemas/user'], 'https://example.com/schemas/user'],
            'anchor' => [['user'], 'user'],
            'dynamicAnchor' => [['node'], 'node'],
            'ref' => [['User'], '#/components/schemas/User'],
            'dynamicRef' => [['#node'], '#node'],
            'defs' => [[$address], ['Address' => $address]],
            'comment' => [['Kept in sync with the database'], 'Kept in sync with the database'],
            'title' => [['User'], 'User'],
            'description' => [['A user of the platform'], 'A user of the platform'],
            'enum' => [['admin', 'member', null], ['admin', 'member', null]],
            'const' => [['admin'], 'admin'],
            'default' => [['member'], 'member'],
            'format' => [['email'], 'email'],
            'type' => [['string', SchemaType::Null], [SchemaType::String, SchemaType::Null]],
            'contentEncoding' => [['base64'], 'base64'],
            'contentMediaType' => [['image/png'], 'image/png'],
            'contentSchema' => [[$string], $string],
            'items' => [[$integer], $integer],
            'prefixItems' => [[$first, $second], [$first, $second]],
            'contains' => [[$positive], $positive],
            'minContains' => [[1], 1],
            'maxContains' => [[3], 3],
            'maxItems' => [[10], 10],
            'minItems' => [[1], 1],
            'uniqueItems' => [[true], true],
            'unevaluatedItems' => [[false], false],
            'pattern' => [['^[a-z]+$'], '^[a-z]+$'],
            'maxLength' => [[255], 255],
            'minLength' => [[0], 0],
            'maximum' => [[100], 100],
            'exclusiveMaximum' => [[100.5], 100.5],
            'minimum' => [[0], 0],
            'exclusiveMinimum' => [[0.0], 0.0],
            'multipleOf' => [[0.01], 0.01],
            'required' => [['name', 'email'], ['name', 'email']],
            'properties' => [[$name, $email], ['name' => $name, 'email' => $email]],
            'patternProperties' => [[$extension], ['^x-' => $extension]],
            'additionalProperties' => [[false], false],
            'propertyNames' => [[$string], $string],
            'unevaluatedProperties' => [[false], false],
            'maxProperties' => [[20], 20],
            'minProperties' => [[1], 1],
            'dependentRequired' => [[['creditCard' => ['billingAddress']]], ['creditCard' => ['billingAddress']]],
            'dependentSchemas' => [[$billing], ['billingAddress' => $billing]],
            'nullable' => [[true], true],
            'allOf' => [[$user, $admin], [$user, $admin]],
            'anyOf' => [[$user, $guest], [$user, $guest]],
            'oneOf' => [[$admin, $guest], [$admin, $guest]],
            'not' => [[$guest], $guest],
            'if' => [[$admin], $admin],
            'then' => [[$positive], $positive],
            'else' => [[true], true],
            'discriminator' => [[$discriminator], $discriminator],
            'readOnly' => [[true], true],
            'writeOnly' => [[true], true],
            'xml' => [[$xml], $xml],
            'externalDocs' => [[$docs], $docs],
            'example' => [[['name' => 'Alice']], ['name' => 'Alice']],
            'examples' => [[['name' => 'Alice'], ['name' => 'Bob']], [['name' => 'Alice'], ['name' => 'Bob']]],
            'deprecated' => [[true], true],
        ];
    }

    /**
     * @return iterable<string, array{Schema, SchemaType}>
     */
    public static function typeShortcuts(): iterable
    {
        yield 'string' => [Schema::string(), SchemaType::String];
        yield 'integer' => [Schema::integer(), SchemaType::Integer];
        yield 'number' => [Schema::number(), SchemaType::Number];
        yield 'boolean' => [Schema::boolean(), SchemaType::Boolean];
        yield 'array' => [Schema::array(), SchemaType::Array];
        yield 'object' => [Schema::object(), SchemaType::Object];
        yield 'null' => [Schema::null(), SchemaType::Null];
    }

    #[DataProvider('typeShortcuts')]
    public function test_each_type_shortcut_sets_one_type(Schema $schema, SchemaType $type): void
    {
        self::assertSame([$type], $schema->type);
    }

    public function test_type_shortcuts_take_an_optional_object_id(): void
    {
        self::assertSame('email', Schema::string('email')->objectId);
        self::assertNull(Schema::string()->objectId);
    }

    public function test_ref_shortcut_returns_a_schema_that_keeps_its_siblings(): void
    {
        $schema = Schema::ref('User', 'owner')->nullable(true)->description('The owner');

        self::assertSame('#/components/schemas/User', $schema->ref);
        self::assertSame('owner', $schema->objectId);
        self::assertTrue($schema->nullable);
        self::assertSame('The owner', $schema->description);
    }

    public function test_type_replaces_the_previous_types(): void
    {
        $schema = Schema::string()->type(SchemaType::Integer);

        self::assertSame([SchemaType::Integer], $schema->type);
    }

    public function test_nullable_is_an_intention_kept_apart_from_the_type(): void
    {
        $schema = Schema::string()->nullable(true);

        self::assertSame([SchemaType::String], $schema->type);
        self::assertTrue($schema->nullable);
    }

    public function test_default_example_and_const_write_a_real_null(): void
    {
        $schema = Schema::string()->default(null)->example(null)->const(null);

        self::assertTrue($schema->has('default'));
        self::assertTrue($schema->has('example'));
        self::assertTrue($schema->has('const'));
        self::assertNull($schema->default);
    }

    public function test_boolean_subschemas_are_kept_as_booleans(): void
    {
        $schema = Schema::array()->items(false)->contains(true)->not(false);

        self::assertFalse($schema->items);
        self::assertTrue($schema->contains);
        self::assertFalse($schema->not);
    }

    public function test_add_required_keeps_duplicates_for_the_validator(): void
    {
        $schema = Schema::object()->required('id')->addRequired('id', 'name');

        self::assertSame(['id', 'id', 'name'], $schema->required);
    }

    public function test_examples_and_example_can_be_set_together(): void
    {
        $schema = Schema::string()->example('a')->examples('a', 'b');

        self::assertSame('a', $schema->example);
        self::assertSame(['a', 'b'], $schema->examples);
    }

    /**
     * @return iterable<string, array{Closure(): Schema}>
     */
    public static function forbiddenValues(): iterable
    {
        yield 'negative maxLength' => [static fn(): Schema => Schema::string()->maxLength(-1)];
        yield 'negative minLength' => [static fn(): Schema => Schema::string()->minLength(-1)];
        yield 'negative maxItems' => [static fn(): Schema => Schema::array()->maxItems(-1)];
        yield 'negative minItems' => [static fn(): Schema => Schema::array()->minItems(-1)];
        yield 'negative maxProperties' => [static fn(): Schema => Schema::object()->maxProperties(-1)];
        yield 'negative minProperties' => [static fn(): Schema => Schema::object()->minProperties(-1)];
        yield 'negative maxContains' => [static fn(): Schema => Schema::array()->maxContains(-1)];
        yield 'negative minContains' => [static fn(): Schema => Schema::array()->minContains(-1)];
        yield 'zero multipleOf' => [static fn(): Schema => Schema::number()->multipleOf(0)];
        yield 'negative multipleOf' => [static fn(): Schema => Schema::number()->multipleOf(-0.5)];
        yield 'unknown type' => [static fn(): Schema => Schema::create()->type('text')];
        yield 'same type twice' => [static fn(): Schema => Schema::create()->type('string', SchemaType::String)];
    }

    /**
     * @param Closure(): Schema $build
     */
    #[DataProvider('forbiddenValues')]
    public function test_a_value_forbidden_in_every_version_is_rejected_at_once(Closure $build): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Schema::');

        $build();
    }

    public function test_type_without_argument_removes_the_type(): void
    {
        self::assertFalse(Schema::string()->type()->has('type'));
    }
}
