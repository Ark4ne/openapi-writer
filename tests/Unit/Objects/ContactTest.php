<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Objects;

use Ark4ne\OpenApiWriter\Objects\Contact;

final class ContactTest extends ExtensibleSpecObjectTestCase
{
    protected static function objectClass(): string
    {
        return Contact::class;
    }

    public static function fields(): array
    {
        return [
            'name' => [['API Support'], 'API Support'],
            'url' => [['https://example.com/support'], 'https://example.com/support'],
            'email' => [['support@example.com'], 'support@example.com'],
        ];
    }
}
