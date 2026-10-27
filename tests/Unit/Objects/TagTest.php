<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Objects;

use Ark4ne\OpenApiWriter\Objects\ExternalDocumentation;
use Ark4ne\OpenApiWriter\Objects\Tag;

final class TagTest extends ExtensibleSpecObjectTestCase
{
    protected static function objectClass(): string
    {
        return Tag::class;
    }

    public static function fields(): array
    {
        $docs = ExternalDocumentation::create()->url('https://example.com/docs/pets');

        return [
            'name' => [['pets'], 'pets'],
            'summary' => [['Pets'], 'Pets'],
            'description' => [['Everything about your pets'], 'Everything about your pets'],
            'externalDocs' => [[$docs], $docs],
            'parent' => [['animals'], 'animals'],
            'kind' => [['nav'], 'nav'],
        ];
    }
}
