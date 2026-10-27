<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Objects;

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
}
