<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Objects;

use Ark4ne\OpenApiWriter\Objects\ServerVariable;

final class ServerVariableTest extends ExtensibleSpecObjectTestCase
{
    protected static function objectClass(): string
    {
        return ServerVariable::class;
    }

    public static function fields(): array
    {
        return [
            'enum' => [['8443', '443'], ['8443', '443']],
            'default' => [['8443'], '8443'],
            'description' => [['The port of the server'], 'The port of the server'],
        ];
    }
}
