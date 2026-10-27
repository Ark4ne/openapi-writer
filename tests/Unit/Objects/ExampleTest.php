<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Objects;

use Ark4ne\OpenApiWriter\Exceptions\InvalidArgumentException;
use Ark4ne\OpenApiWriter\Objects\Example;

final class ExampleTest extends ExtensibleSpecObjectTestCase
{
    protected static function objectClass(): string
    {
        return Example::class;
    }

    public static function fields(): array
    {
        return [
            'summary' => [['A user'], 'A user'],
            'description' => [['A user with every field set'], 'A user with every field set'],
            'dataValue' => [[['id' => 1, 'name' => 'Alice']], ['id' => 1, 'name' => 'Alice']],
            'serializedValue' => [['{"id":1,"name":"Alice"}'], '{"id":1,"name":"Alice"}'],
            'value' => [[['id' => 1, 'name' => 'Alice']], ['id' => 1, 'name' => 'Alice']],
            'externalValue' => [['https://example.com/examples/user.json'], 'https://example.com/examples/user.json'],
        ];
    }

    public function test_value_and_external_value_together_are_rejected(): void
    {
        self::assertThrows(InvalidArgumentException::class, static fn(): Example => Example::create()->value(1)->externalValue('https://example.com/one.json'));
        self::assertThrows(InvalidArgumentException::class, static fn(): Example => Example::create()->externalValue('https://example.com/one.json')->value(1));
    }

    public function test_a_null_value_is_a_value(): void
    {
        $example = Example::create()->value(null);

        self::assertTrue($example->has('value'));
        self::assertNull($example->value);
    }

    public function test_value_and_data_value_or_serialized_value_together_are_rejected(): void
    {
        self::assertThrows(InvalidArgumentException::class, static fn(): Example => Example::create()->value(1)->dataValue(1));
        self::assertThrows(InvalidArgumentException::class, static fn(): Example => Example::create()->dataValue(1)->value(1));
        self::assertThrows(InvalidArgumentException::class, static fn(): Example => Example::create()->value(1)->serializedValue('1'));
        self::assertThrows(InvalidArgumentException::class, static fn(): Example => Example::create()->serializedValue('1')->value(1));
    }

    public function test_serialized_value_and_external_value_together_are_rejected(): void
    {
        self::assertThrows(InvalidArgumentException::class, static fn(): Example => Example::create()->serializedValue('1')->externalValue('https://example.com/one.txt'));
        self::assertThrows(InvalidArgumentException::class, static fn(): Example => Example::create()->externalValue('https://example.com/one.txt')->serializedValue('1'));
    }

    public function test_data_value_goes_with_serialized_value_or_external_value(): void
    {
        $example = Example::create()->dataValue(1)->serializedValue('1');

        self::assertSame('1', $example->serializedValue);
        self::assertSame('https://example.com/one.txt', Example::create()->dataValue(1)->externalValue('https://example.com/one.txt')->externalValue);
    }

    public function test_an_empty_array_is_a_value_for_the_exclusive_fields(): void
    {
        self::assertThrows(InvalidArgumentException::class, static fn(): Example => Example::create()->value([])->dataValue(1));
        self::assertThrows(InvalidArgumentException::class, static fn(): Example => Example::create()->dataValue([])->value(1));
        self::assertThrows(InvalidArgumentException::class, static fn(): Example => Example::create()->value([])->serializedValue('[]'));
    }
}
