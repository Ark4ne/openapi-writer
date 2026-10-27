<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Objects;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Objects\Components;
use Ark4ne\OpenApiWriter\Objects\ExternalDocumentation;
use Ark4ne\OpenApiWriter\Objects\Info;
use Ark4ne\OpenApiWriter\Objects\OpenApi;
use Ark4ne\OpenApiWriter\Objects\Operation;
use Ark4ne\OpenApiWriter\Objects\PathItem;
use Ark4ne\OpenApiWriter\Objects\Reference;
use Ark4ne\OpenApiWriter\Objects\SecurityRequirement;
use Ark4ne\OpenApiWriter\Objects\Server;
use Ark4ne\OpenApiWriter\Objects\Tag;
use JsonSerializable;

final class OpenApiTest extends ExtensibleSpecObjectTestCase
{
    protected static function objectClass(): string
    {
        return OpenApi::class;
    }

    public static function fields(): array
    {
        $info = Info::create()->title('Users API')->version('1.0.0');
        $server = Server::create()->url('https://api.example.com');
        $users = PathItem::create('/users')->get(Operation::get());
        $user = PathItem::create('/users/{id}')->get(Operation::get());
        $newUser = PathItem::create('newUser')->post(Operation::post());
        $deletedUser = Reference::to('#/components/pathItems/DeletedUser', 'deletedUser');
        $components = Components::create();
        $security = SecurityRequirement::create()->schemes(['api_key' => []]);
        $tag = Tag::create()->name('users');
        $docs = ExternalDocumentation::create()->url('https://example.com/docs');

        return [
            'openapi' => [['3.0.4'], Version::V3_0_4],
            'self' => [['https://api.example.com/openapi.json'], 'https://api.example.com/openapi.json'],
            'info' => [[$info], $info],
            'jsonSchemaDialect' => [['https://spec.openapis.org/oas/3.1/dialect/base'], 'https://spec.openapis.org/oas/3.1/dialect/base'],
            'servers' => [[$server], [$server]],
            'paths' => [[$users, $user], ['/users' => $users, '/users/{id}' => $user]],
            'webhooks' => [[$newUser, $deletedUser], ['newUser' => $newUser, 'deletedUser' => $deletedUser]],
            'components' => [[$components], $components],
            'security' => [[$security], [$security]],
            'tags' => [[$tag], [$tag]],
            'externalDocs' => [[$docs], $docs],
        ];
    }

    public function test_openapi_accepts_the_enum(): void
    {
        self::assertSame(Version::V3_2_0, OpenApi::create()->openapi(Version::V3_2_0)->openapi);
    }

    public function test_an_open_api_document_is_json_serializable(): void
    {
        self::assertContains(JsonSerializable::class, class_implements(OpenApi::class));
    }
}
