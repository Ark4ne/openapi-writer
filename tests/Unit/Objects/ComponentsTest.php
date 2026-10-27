<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Objects;

use Ark4ne\OpenApiWriter\Objects\Callback;
use Ark4ne\OpenApiWriter\Objects\Components;
use Ark4ne\OpenApiWriter\Objects\Example;
use Ark4ne\OpenApiWriter\Objects\Header;
use Ark4ne\OpenApiWriter\Objects\Link;
use Ark4ne\OpenApiWriter\Objects\MediaType;
use Ark4ne\OpenApiWriter\Objects\Parameter;
use Ark4ne\OpenApiWriter\Objects\PathItem;
use Ark4ne\OpenApiWriter\Objects\Reference;
use Ark4ne\OpenApiWriter\Objects\RequestBody;
use Ark4ne\OpenApiWriter\Objects\Response;
use Ark4ne\OpenApiWriter\Objects\Schema;
use Ark4ne\OpenApiWriter\Objects\SecurityScheme;

final class ComponentsTest extends ExtensibleSpecObjectTestCase
{
    protected static function objectClass(): string
    {
        return Components::class;
    }

    public static function fields(): array
    {
        $user = Schema::object('User');
        $error = Schema::object('Error');
        $notFound = Response::create('NotFound')->description('Not found');
        $page = Parameter::query('page', 'Page');
        $example = Example::create('Alice')->value(['name' => 'Alice']);
        $body = RequestBody::create('UserBody')->required(true);
        $rateLimit = Header::create('RateLimit')->schema(Schema::integer());
        $apiKey = SecurityScheme::apiKey('ApiKey')->name('X-API-Key')->in('header');
        $link = Link::create('UserRepositories')->operationId('getRepositoriesByOwner');
        $callback = Callback::create('onEvent');
        $pathItem = PathItem::create('Ping');
        $mediaType = MediaType::create('UserJson')->schema(Schema::ref('User'));

        return [
            'schemas' => [[$user, $error], ['User' => $user, 'Error' => $error]],
            'responses' => [[$notFound], ['NotFound' => $notFound]],
            'parameters' => [[$page], ['Page' => $page]],
            'examples' => [[$example], ['Alice' => $example]],
            'requestBodies' => [[$body], ['UserBody' => $body]],
            'headers' => [[$rateLimit], ['RateLimit' => $rateLimit]],
            'securitySchemes' => [[$apiKey], ['ApiKey' => $apiKey]],
            'links' => [[$link], ['UserRepositories' => $link]],
            'callbacks' => [[$callback], ['onEvent' => $callback]],
            'pathItems' => [[$pathItem], ['Ping' => $pathItem]],
            'mediaTypes' => [[$mediaType], ['UserJson' => $mediaType]],
        ];
    }

    public function test_a_section_accepts_references_keyed_by_their_object_id(): void
    {
        $alias = Reference::to('#/components/schemas/User', 'Customer');

        $components = Components::create()->schemas($alias);

        self::assertSame(['Customer' => $alias], $components->schemas);
    }

    public function test_the_same_object_id_in_two_sections_is_allowed(): void
    {
        $schema = Schema::object('User');
        $response = Response::create('User')->description('A user');

        $components = Components::create()->schemas($schema)->responses($response);

        self::assertSame(['User' => $schema], $components->schemas);
        self::assertSame(['User' => $response], $components->responses);
    }

    public function test_add_keeps_the_insertion_order(): void
    {
        $b = Schema::object('B');
        $a = Schema::object('A');

        $components = Components::create()->schemas($b)->addSchemas($a);

        self::assertSame(['B', 'A'], array_keys($components->schemas ?? []));
    }
}
