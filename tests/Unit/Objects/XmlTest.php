<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Objects;

use Ark4ne\OpenApiWriter\Exceptions\InvalidArgumentException;
use Ark4ne\OpenApiWriter\Objects\Xml;

final class XmlTest extends ExtensibleSpecObjectTestCase
{
    protected static function objectClass(): string
    {
        return Xml::class;
    }

    public static function fields(): array
    {
        return [
            'nodeType' => [['element'], 'element'],
            'name' => [['animal'], 'animal'],
            'namespace' => [['https://example.com/schema/sample'], 'https://example.com/schema/sample'],
            'prefix' => [['sample'], 'sample'],
            'attribute' => [[true], true],
            'wrapped' => [[true], true],
        ];
    }

    public function test_node_type_with_attribute_or_wrapped_is_rejected(): void
    {
        self::assertThrows(InvalidArgumentException::class, static fn(): Xml => Xml::create()->nodeType('element')->attribute(true));
        self::assertThrows(InvalidArgumentException::class, static fn(): Xml => Xml::create()->nodeType('element')->wrapped(true));
        self::assertThrows(InvalidArgumentException::class, static fn(): Xml => Xml::create()->attribute(false)->nodeType('element'));
        self::assertThrows(InvalidArgumentException::class, static fn(): Xml => Xml::create()->wrapped(false)->nodeType('element'));
    }

    public function test_an_unknown_node_type_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Xml::nodeType');

        Xml::create()->nodeType('comment');
    }
}
