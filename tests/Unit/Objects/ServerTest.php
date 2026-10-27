<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Objects;

use Ark4ne\OpenApiWriter\Objects\Server;
use Ark4ne\OpenApiWriter\Objects\ServerVariable;

final class ServerTest extends ExtensibleSpecObjectTestCase
{
    protected static function objectClass(): string
    {
        return Server::class;
    }

    public static function fields(): array
    {
        $port = ServerVariable::create('port')->default('443');
        $basePath = ServerVariable::create('basePath')->default('v2');

        return [
            'url' => [['https://{username}.example.com:{port}/{basePath}'], 'https://{username}.example.com:{port}/{basePath}'],
            'description' => [['Production'], 'Production'],
            'name' => [['production'], 'production'],
            'variables' => [[$port, $basePath], ['port' => $port, 'basePath' => $basePath]],
        ];
    }
}
