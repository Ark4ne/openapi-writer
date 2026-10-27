<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Objects;

use Ark4ne\OpenApiWriter\Exceptions\InvalidArgumentException;
use Ark4ne\OpenApiWriter\Objects\Link;
use Ark4ne\OpenApiWriter\Objects\Server;

final class LinkTest extends ExtensibleSpecObjectTestCase
{
    protected static function objectClass(): string
    {
        return Link::class;
    }

    public static function fields(): array
    {
        $server = Server::create()->url('https://eu.example.com');
        $parameters = ['username' => '$response.body#/username', 'limit' => 10];

        return [
            'operationRef' => [['#/paths/~12.0~1repositories~1{username}/get'], '#/paths/~12.0~1repositories~1{username}/get'],
            'operationId' => [['getRepositoriesByOwner'], 'getRepositoriesByOwner'],
            'parameters' => [[$parameters], $parameters],
            'requestBody' => [['$request.body#/user'], '$request.body#/user'],
            'description' => [['The repositories of the user'], 'The repositories of the user'],
            'server' => [[$server], $server],
        ];
    }

    public function test_operation_ref_and_operation_id_together_are_rejected(): void
    {
        self::assertThrows(InvalidArgumentException::class, static fn(): Link => Link::create()->operationId('getUser')->operationRef('#/paths/~1users/get'));
        self::assertThrows(InvalidArgumentException::class, static fn(): Link => Link::create()->operationRef('#/paths/~1users/get')->operationId('getUser'));
    }
}
