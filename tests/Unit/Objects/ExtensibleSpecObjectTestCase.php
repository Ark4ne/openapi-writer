<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Objects;

use Ark4ne\OpenApiWriter\Exceptions\InvalidArgumentException;
use Ark4ne\OpenApiWriter\Objects\ExtensibleSpecObject;
use Ark4ne\OpenApiWriter\Objects\SpecObject;

/**
 * Contract of the objects that the specification declares extensible: `x-*` extensions.
 */
abstract class ExtensibleSpecObjectTestCase extends SpecObjectTestCase
{
    public function test_x_adds_an_extension_with_its_prefix_on_a_copy(): void
    {
        $object = self::emptyExtensible();

        $extended = $object->x('logo', ['url' => 'https://example.com/logo.png'])->x('x-internal', true);

        self::assertSame(['x-logo' => ['url' => 'https://example.com/logo.png'], 'x-internal' => true], $extended->extensions);
        self::assertSame([], $object->extensions, 'The original object is unchanged.');
    }

    public function test_x_keeps_null_as_an_extension_value(): void
    {
        $extended = self::emptyExtensible()->x('nothing', null);

        self::assertSame(['x-nothing' => null], $extended->extensions);
    }

    public function test_x_replaces_an_existing_extension_in_place(): void
    {
        $extended = self::emptyExtensible()->x('a', 1)->x('b', 2)->x('x-a', 3);

        self::assertSame(['x-a' => 3, 'x-b' => 2], $extended->extensions);
    }

    public function test_without_x_removes_an_extension_named_with_or_without_its_prefix(): void
    {
        $object = self::emptyExtensible()->x('a', 1)->x('b', 2)->x('c', 3);

        $removed = $object->withoutX('a')->withoutX('x-b');

        self::assertSame(['x-c' => 3], $removed->extensions);
        self::assertSame(['x-a' => 1, 'x-b' => 2, 'x-c' => 3], $object->extensions, 'The original object is unchanged.');
    }

    public function test_x_rejects_an_empty_name(): void
    {
        self::assertThrows(InvalidArgumentException::class, static fn(): SpecObject => self::emptyExtensible()->x('', 'value'));
        self::assertThrows(InvalidArgumentException::class, static fn(): SpecObject => self::emptyExtensible()->x('x-', 'value'));
    }

    private static function emptyExtensible(): ExtensibleSpecObject
    {
        $object = static::emptyObject();
        self::assertInstanceOf(ExtensibleSpecObject::class, $object);

        return $object;
    }
}
