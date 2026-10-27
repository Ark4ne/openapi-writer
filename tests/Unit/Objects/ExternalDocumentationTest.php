<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Objects;

use Ark4ne\OpenApiWriter\Objects\ExternalDocumentation;

final class ExternalDocumentationTest extends ExtensibleSpecObjectTestCase
{
    protected static function objectClass(): string
    {
        return ExternalDocumentation::class;
    }

    public static function fields(): array
    {
        return [
            'description' => [['Find more info here'], 'Find more info here'],
            'url' => [['https://example.com/docs'], 'https://example.com/docs'],
        ];
    }
}
