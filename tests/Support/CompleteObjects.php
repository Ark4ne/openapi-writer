<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Support;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Objects\Callback;
use Ark4ne\OpenApiWriter\Objects\Components;
use Ark4ne\OpenApiWriter\Objects\Contact;
use Ark4ne\OpenApiWriter\Objects\Discriminator;
use Ark4ne\OpenApiWriter\Objects\Encoding;
use Ark4ne\OpenApiWriter\Objects\Example;
use Ark4ne\OpenApiWriter\Objects\ExternalDocumentation;
use Ark4ne\OpenApiWriter\Objects\Header;
use Ark4ne\OpenApiWriter\Objects\Info;
use Ark4ne\OpenApiWriter\Objects\License;
use Ark4ne\OpenApiWriter\Objects\Link;
use Ark4ne\OpenApiWriter\Objects\MediaType;
use Ark4ne\OpenApiWriter\Objects\OAuthFlow;
use Ark4ne\OpenApiWriter\Objects\OAuthFlows;
use Ark4ne\OpenApiWriter\Objects\OpenApi;
use Ark4ne\OpenApiWriter\Objects\Operation;
use Ark4ne\OpenApiWriter\Objects\Parameter;
use Ark4ne\OpenApiWriter\Objects\PathItem;
use Ark4ne\OpenApiWriter\Objects\Reference;
use Ark4ne\OpenApiWriter\Objects\RequestBody;
use Ark4ne\OpenApiWriter\Objects\Response;
use Ark4ne\OpenApiWriter\Objects\Responses;
use Ark4ne\OpenApiWriter\Objects\Schema;
use Ark4ne\OpenApiWriter\Objects\SecurityRequirement;
use Ark4ne\OpenApiWriter\Objects\SecurityScheme;
use Ark4ne\OpenApiWriter\Objects\Server;
use Ark4ne\OpenApiWriter\Objects\ServerVariable;
use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Objects\Tag;
use Ark4ne\OpenApiWriter\Objects\Xml;
use Closure;

/**
 * Object Mother of the serialization suite: for each of the 29 objects, one or more objects with every field that
 * the target line can express, set in an order different from the output order. The expected output of each case
 * is `tests/fixtures/<line>/objects/<case>.json`.
 */
final class CompleteObjects
{
    /**
     * Keyed by case name: the builder, and the first version that can write the case.
     *
     * @return array<string, array{Closure(Version): SpecObject, Version}>
     */
    public static function cases(): array
    {
        return [
            'contact' => [self::contact(...), Version::V3_0_0],
            'license' => [self::license(...), Version::V3_0_0],
            'info' => [self::info(...), Version::V3_0_0],
            'server' => [self::server(...), Version::V3_0_0],
            'server-variable' => [self::serverVariable(...), Version::V3_0_0],
            'external-documentation' => [self::externalDocumentation(...), Version::V3_0_0],
            'tag' => [self::tag(...), Version::V3_0_0],
            'xml' => [self::xml(...), Version::V3_0_0],
            'discriminator' => [self::discriminator(...), Version::V3_0_0],
            'oauth-flow' => [self::oauthFlow(...), Version::V3_0_0],
            'oauth-flows' => [self::oauthFlows(...), Version::V3_0_0],
            'security-scheme-api-key' => [self::apiKeyScheme(...), Version::V3_0_0],
            'security-scheme-http' => [self::httpScheme(...), Version::V3_0_0],
            'security-scheme-oauth2' => [self::oauth2Scheme(...), Version::V3_0_0],
            'security-scheme-open-id-connect' => [self::openIdConnectScheme(...), Version::V3_0_0],
            'security-scheme-mutual-tls' => [self::mutualTlsScheme(...), Version::V3_1_0],
            'security-requirement' => [self::securityRequirement(...), Version::V3_0_0],
            'security-requirement-anonymous' => [self::anonymousRequirement(...), Version::V3_0_0],
            'example' => [self::example(...), Version::V3_0_0],
            'example-external' => [self::externalExample(...), Version::V3_0_0],
            'example-data-value' => [self::dataValueExample(...), Version::V3_2_0],
            'link' => [self::link(...), Version::V3_0_0],
            'link-operation-ref' => [self::operationRefLink(...), Version::V3_0_0],
            'header' => [self::header(...), Version::V3_0_0],
            'header-content' => [self::contentHeader(...), Version::V3_0_0],
            'parameter' => [self::parameter(...), Version::V3_0_0],
            'parameter-content' => [self::contentParameter(...), Version::V3_0_0],
            'request-body' => [self::requestBody(...), Version::V3_0_0],
            'media-type' => [self::mediaType(...), Version::V3_0_0],
            'media-type-encoding' => [self::multipartMediaType(...), Version::V3_0_0],
            'media-type-sequential' => [self::sequentialMediaType(...), Version::V3_2_0],
            'encoding' => [self::encoding(...), Version::V3_0_0],
            'responses' => [self::responses(...), Version::V3_0_0],
            'response' => [self::response(...), Version::V3_0_0],
            'callback' => [self::callback(...), Version::V3_0_0],
            'path-item' => [self::pathItem(...), Version::V3_0_0],
            'path-item-ref' => [self::refPathItem(...), Version::V3_0_0],
            'operation' => [self::operation(...), Version::V3_0_0],
            'components' => [self::components(...), Version::V3_0_0],
            'schema-object' => [self::objectSchema(...), Version::V3_0_0],
            'schema-string' => [self::stringSchema(...), Version::V3_0_0],
            'schema-number' => [self::numberSchema(...), Version::V3_0_0],
            'schema-array' => [self::arraySchema(...), Version::V3_0_0],
            'schema-composition' => [self::compositionSchema(...), Version::V3_0_0],
            'schema-json-schema-2020-12' => [self::jsonSchema202012(...), Version::V3_1_0],
            'schema-array-2020-12' => [self::arrayKeywords202012(...), Version::V3_1_0],
            'reference' => [self::reference(...), Version::V3_0_0],
            'open-api' => [self::openApi(...), Version::V3_0_0],
        ];
    }

    /**
     * Whether $version is $minimum or later, computed here so that the builders do not depend on the code under test.
     */
    public static function atLeast(Version $version, Version $minimum): bool
    {
        return version_compare($version->value, $minimum->value, '>=');
    }

    public static function contact(Version $version): Contact
    {
        return Contact::create()->x('team', 'platform')->email('support@example.com')->url('https://example.com/support')->name('API Support');
    }

    public static function license(Version $version): License
    {
        $license = License::create()->name('Apache 2.0');

        return self::atLeast($version, Version::V3_1_0)
            ? $license->identifier('Apache-2.0')
            : $license->url('https://www.apache.org/licenses/LICENSE-2.0.html');
    }

    public static function info(Version $version): Info
    {
        $info = Info::create()
            ->x('audience', 'public')
            ->version('1.0.0')
            ->license(License::create()->name('MIT')->url('https://opensource.org/licenses/MIT'))
            ->contact(Contact::create()->name('API Support'))
            ->termsOfService('https://example.com/terms')
            ->description('Manage the users of the platform.')
            ->title('Users API');

        return self::atLeast($version, Version::V3_1_0) ? $info->summary('Users') : $info;
    }

    public static function server(Version $version): Server
    {
        $server = Server::create()
            ->x('internal', false)
            ->variables(
                ServerVariable::create('region')->description('Region of the server')->default('eu')->enum('eu', 'us'),
                ServerVariable::create('version')->default('v1'),
            )
            ->description('Regional server')
            ->url('https://{region}.example.com/{version}');

        return self::atLeast($version, Version::V3_2_0) ? $server->name('regional') : $server;
    }

    public static function serverVariable(Version $version): ServerVariable
    {
        return ServerVariable::create('port')->description('The port of the server')->default('8443')->enum('8443', '443');
    }

    public static function externalDocumentation(Version $version): ExternalDocumentation
    {
        return ExternalDocumentation::create()->url('https://example.com/docs')->description('Find more info here');
    }

    public static function tag(Version $version): Tag
    {
        $tag = Tag::create()
            ->externalDocs(ExternalDocumentation::create()->url('https://example.com/docs/pets'))
            ->description('Everything about your pets')
            ->name('pets');

        return self::atLeast($version, Version::V3_2_0) ? $tag->kind('nav')->parent('animals')->summary('Pets') : $tag;
    }

    public static function xml(Version $version): Xml
    {
        $xml = Xml::create()->prefix('sample')->namespace('https://example.com/schema/sample')->name('animal');

        return self::atLeast($version, Version::V3_2_0) ? $xml->nodeType('element') : $xml->wrapped(true)->attribute(false);
    }

    public static function discriminator(Version $version): Discriminator
    {
        $discriminator = Discriminator::create()
            ->mapping(['dog' => '#/components/schemas/Dog', 'cat' => '#/components/schemas/Cat'])
            ->propertyName('petType');

        return self::atLeast($version, Version::V3_2_0) ? $discriminator->defaultMapping('#/components/schemas/Pet') : $discriminator;
    }

    public static function oauthFlow(Version $version): OAuthFlow
    {
        return OAuthFlow::create()
            ->scopes(['write:pets' => 'modify pets in your account', 'read:pets' => 'read your pets'])
            ->refreshUrl('https://example.com/api/oauth/refresh')
            ->tokenUrl('https://example.com/api/oauth/token')
            ->authorizationUrl('https://example.com/api/oauth/dialog');
    }

    public static function oauthFlows(Version $version): OAuthFlows
    {
        $flows = OAuthFlows::create()
            ->authorizationCode(OAuthFlow::create()->authorizationUrl('https://example.com/oauth/authorize')->tokenUrl('https://example.com/oauth/token')->scopes(['read:pets' => 'read your pets']))
            ->clientCredentials(OAuthFlow::create()->tokenUrl('https://example.com/oauth/token')->scopes([]))
            ->password(OAuthFlow::create()->tokenUrl('https://example.com/oauth/token')->scopes([]))
            ->implicit(OAuthFlow::create()->authorizationUrl('https://example.com/oauth/authorize')->scopes([]));

        return self::atLeast($version, Version::V3_2_0)
            ? $flows->deviceAuthorization(OAuthFlow::create()->deviceAuthorizationUrl('https://example.com/oauth/device')->tokenUrl('https://example.com/oauth/token')->scopes([]))
            : $flows;
    }

    public static function apiKeyScheme(Version $version): SecurityScheme
    {
        $scheme = SecurityScheme::apiKey('ApiKey')->in('header')->name('X-API-Key')->description('API key sent in a header');

        return self::atLeast($version, Version::V3_2_0) ? $scheme->deprecated(true) : $scheme;
    }

    public static function httpScheme(Version $version): SecurityScheme
    {
        return SecurityScheme::http('Bearer')->bearerFormat('JWT')->scheme('bearer');
    }

    public static function oauth2Scheme(Version $version): SecurityScheme
    {
        $scheme = SecurityScheme::oauth2('OAuth2')
            ->flows(OAuthFlows::create()->clientCredentials(OAuthFlow::create()->tokenUrl('https://example.com/oauth/token')->scopes(['read' => 'Read access'])))
            ->description('OAuth2 client credentials');

        return self::atLeast($version, Version::V3_2_0)
            ? $scheme->oauth2MetadataUrl('https://example.com/.well-known/oauth-authorization-server')
            : $scheme;
    }

    public static function openIdConnectScheme(Version $version): SecurityScheme
    {
        return SecurityScheme::openIdConnect('OpenId')->openIdConnectUrl('https://example.com/.well-known/openid-configuration');
    }

    public static function mutualTlsScheme(Version $version): SecurityScheme
    {
        return SecurityScheme::mutualTLS('Certificate')->description('Client certificate');
    }

    public static function securityRequirement(Version $version): SecurityRequirement
    {
        return SecurityRequirement::create()->schemes(['petstore_auth' => ['write:pets', 'read:pets']])->addSchemes(['api_key' => []]);
    }

    public static function anonymousRequirement(Version $version): SecurityRequirement
    {
        return SecurityRequirement::create()->schemes([]);
    }

    public static function example(Version $version): Example
    {
        return Example::create('alice')
            ->x('generated', false)
            ->value(['id' => 1, 'name' => 'Alice', 'score' => 1.0, 'tags' => []])
            ->description('A user with every field set')
            ->summary('A user');
    }

    public static function externalExample(Version $version): Example
    {
        return Example::create('large')->externalValue('https://example.com/examples/users.json')->summary('Many users');
    }

    public static function dataValueExample(Version $version): Example
    {
        return Example::create('date')->serializedValue('2025-06-01')->dataValue('2025-06-01')->summary('A date');
    }

    public static function link(Version $version): Link
    {
        return Link::create('repositories')
            ->server(Server::create()->url('https://eu.example.com'))
            ->description('The repositories of the user')
            ->requestBody('$request.body#/user')
            ->parameters(['username' => '$response.body#/username'])
            ->operationId('getRepositoriesByOwner');
    }

    public static function operationRefLink(Version $version): Link
    {
        return Link::create('repository')->parameters(['username' => '$response.body#/owner/username', 'slug' => '$response.body#/slug'])->operationRef('#/paths/~12.0~1repositories~1{username}~1{slug}/get');
    }

    public static function header(Version $version): Header
    {
        return Header::create('X-Rate-Limit')
            ->example(99)
            ->schema(Schema::integer()->format('int32'))
            ->explode(false)
            ->style('simple')
            ->deprecated(false)
            ->required(true)
            ->description('Remaining requests');
    }

    public static function contentHeader(Version $version): Header
    {
        return Header::create('X-Trace')->content(MediaType::of('text/plain')->schema(Schema::string()))->description('Trace of the request');
    }

    public static function parameter(Version $version): Parameter
    {
        return Parameter::query('page', 'Page')
            ->examples(Example::create('first')->value(1), Example::create('last')->value(100))
            ->schema(Schema::integer()->minimum(1))
            ->allowReserved(false)
            ->explode(true)
            ->style('form')
            ->allowEmptyValue(false)
            ->deprecated(false)
            ->required(false)
            ->description('The page to return');
    }

    public static function contentParameter(Version $version): Parameter
    {
        return Parameter::path('coordinates')->content(MediaType::json()->schema(Schema::object()->required('lat', 'long')));
    }

    public static function requestBody(Version $version): RequestBody
    {
        return RequestBody::create('UserBody')
            ->required(true)
            ->content(MediaType::json()->schema(Schema::ref('User')), MediaType::xml()->schema(Schema::ref('User')))
            ->description('The user to create');
    }

    public static function mediaType(Version $version): MediaType
    {
        return MediaType::json()
            ->examples(Example::create('alice')->value(['name' => 'Alice']), Reference::to('#/components/examples/Bob', 'bob'))
            ->schema(Schema::ref('User'));
    }

    public static function multipartMediaType(Version $version): MediaType
    {
        return MediaType::multipart()
            ->encoding(Encoding::create('avatar')->contentType('image/png'))
            ->schema(Schema::object()->properties(Schema::string('name'), Schema::string('avatar')->format('binary')));
    }

    public static function sequentialMediaType(Version $version): MediaType
    {
        return MediaType::of('application/jsonl')->description('A stream of events, one JSON document per line')->itemSchema(Schema::ref('Event'));
    }

    public static function encoding(Version $version): Encoding
    {
        return Encoding::create('avatar')
            ->allowReserved(false)
            ->explode(true)
            ->style('form')
            ->headers(Header::create('X-Rate-Limit')->schema(Schema::integer()))
            ->contentType('image/png, image/jpeg');
    }

    public static function responses(Version $version): Responses
    {
        return Responses::create()
            ->x('generated', true)
            ->responses(
                Response::ok()->content(MediaType::json()->schema(Schema::ref('User'))),
                Reference::to('#/components/responses/NotFound', '404'),
                Response::create('default')->description('Unexpected error'),
            );
    }

    public static function response(Version $version): Response
    {
        $response = Response::ok()
            ->links(Link::create('repositories')->operationId('getRepositoriesByOwner'))
            ->content(MediaType::json()->schema(Schema::ref('User')))
            ->headers(Header::create('X-Rate-Limit')->schema(Schema::integer()))
            ->description('The requested user');

        return self::atLeast($version, Version::V3_2_0) ? $response->summary('A user') : $response;
    }

    public static function callback(Version $version): Callback
    {
        return Callback::create('onPayment')->pathItems(
            PathItem::create('{$request.body#/callbackUrl}')->post(
                Operation::post()
                    ->responses(Response::ok())
                    ->requestBody(RequestBody::create()->content(MediaType::json()->schema(Schema::ref('Payment')))),
            ),
        );
    }

    public static function pathItem(Version $version): PathItem
    {
        $pathItem = PathItem::create('/users/{id}')
            ->parameters(Parameter::path('id')->schema(Schema::integer()))
            ->servers(Server::create()->url('https://eu.example.com'))
            ->operations(
                Operation::delete()->operationId('deleteUser')->responses(Response::noContent()),
                Operation::get()->operationId('getUser')->responses(Response::ok()),
            )
            ->description('Operations on one user')
            ->summary('A user');

        return self::atLeast($version, Version::V3_2_0)
            ? $pathItem->addAdditionalOperations(Operation::create('COPY')->operationId('copyUser')->responses(Response::created()))
                ->query(Operation::query()->operationId('searchUser')->responses(Response::ok()))
            : $pathItem;
    }

    public static function refPathItem(Version $version): PathItem
    {
        return PathItem::create('/users')->ref('https://example.com/paths/users.json');
    }

    public static function operation(Version $version): Operation
    {
        return Operation::post()
            ->servers(Server::create()->url('https://eu.example.com'))
            ->security(SecurityRequirement::create()->schemes(['api_key' => []]), SecurityRequirement::create()->schemes([]))
            ->deprecated(true)
            ->callbacks(Callback::create('onCreated')->pathItems(PathItem::create('{$request.body#/callbackUrl}')->post(Operation::post()->responses(Response::ok()))))
            ->responses(Response::created(), Response::unprocessableEntity())
            ->requestBody(Reference::to('#/components/requestBodies/UserBody'))
            ->parameters(Parameter::header('X-Request-Id')->schema(Schema::string()->format('uuid')), Reference::to('#/components/parameters/Page'))
            ->operationId('createUser')
            ->externalDocs(ExternalDocumentation::create()->url('https://example.com/docs/users'))
            ->description('Creates one user.')
            ->summary('Create a user')
            ->tags('users', 'admin');
    }

    public static function components(Version $version): Components
    {
        $components = Components::create()
            ->x('generated', true)
            ->callbacks(Callback::create('onEvent')->pathItems(PathItem::create('{$request.body#/url}')->post(Operation::post()->responses(Response::ok()))))
            ->links(Link::create('UserRepositories')->operationId('getRepositoriesByOwner'))
            ->securitySchemes(SecurityScheme::apiKey('ApiKey')->name('X-API-Key')->in('header'))
            ->headers(Header::create('RateLimit')->schema(Schema::integer()))
            ->requestBodies(RequestBody::create('UserBody')->content(MediaType::json()->schema(Schema::ref('User'))))
            ->examples(Example::create('Alice')->value(['name' => 'Alice']))
            ->parameters(Parameter::query('page', 'Page')->schema(Schema::integer()))
            ->responses(Response::create('NotFound')->description('Not found'))
            ->schemas(Schema::object('User')->properties(Schema::string('name')), Schema::object('Empty'));

        if (self::atLeast($version, Version::V3_1_0)) {
            $components = $components->pathItems(PathItem::create('Ping')->get(Operation::get()->responses(Response::noContent())));
        }

        if (self::atLeast($version, Version::V3_2_0)) {
            $components = $components->mediaTypes(MediaType::create('UserJson')->schema(Schema::ref('User')));
        }

        return $components;
    }

    public static function objectSchema(Version $version): Schema
    {
        return Schema::object('User')
            ->deprecated(false)
            ->example(['id' => 1, 'name' => 'Alice'])
            ->externalDocs(ExternalDocumentation::create()->url('https://example.com/docs/user'))
            ->xml(Xml::create()->name('user'))
            ->minProperties(1)
            ->maxProperties(10)
            ->additionalProperties(false)
            ->properties(
                Schema::integer('id')->format('int64')->readOnly(true),
                Schema::string('name')->writeOnly(false),
                Schema::object('settings')->additionalProperties(Schema::string()),
                Schema::object('metadata')->properties(),
            )
            ->required('id', 'name')
            ->default(['name' => 'Anonymous'])
            ->description('A user of the platform')
            ->title('User')
            ->x('model', 'App\\Models\\User');
    }

    public static function stringSchema(Version $version): Schema
    {
        return Schema::string()
            ->minLength(1)
            ->maxLength(255)
            ->pattern('^[a-z]+$')
            ->default('member')
            ->enum('admin', 'member')
            ->format('slug')
            ->title('Role');
    }

    public static function numberSchema(Version $version): Schema
    {
        return Schema::number()->multipleOf(0.5)->minimum(0)->maximum(100.0)->default(1.0);
    }

    public static function arraySchema(Version $version): Schema
    {
        return Schema::array()->uniqueItems(true)->minItems(1)->maxItems(10)->items(Schema::string());
    }

    public static function compositionSchema(Version $version): Schema
    {
        return Schema::create()
            ->not(Schema::null())
            ->oneOf(Schema::ref('Cat'), Schema::ref('Dog'))
            ->anyOf(Schema::string(), Schema::integer())
            ->allOf(Schema::ref('Pet'), Schema::object()->required('name'))
            ->discriminator(Discriminator::create()->propertyName('petType'));
    }

    public static function jsonSchema202012(Version $version): Schema
    {
        return Schema::object()
            ->else(Schema::object()->required('postalCode'))
            ->then(Schema::object()->required('zipCode'))
            ->if(Schema::object()->properties(Schema::create('country')->const('US')))
            ->dependentSchemas(Schema::object('creditCard')->required('billingAddress'))
            ->dependentRequired(['creditCard' => ['billingAddress']])
            ->unevaluatedProperties(false)
            ->propertyNames(Schema::string()->maxLength(32))
            ->patternProperties(Schema::string('^x-'))
            ->contentSchema(Schema::object())
            ->contentMediaType('application/json')
            ->contentEncoding('base64')
            ->comment('Kept in sync with the database')
            ->defs(Schema::string('Name')->minLength(1))
            ->dynamicAnchor('node')
            ->anchor('address')
            ->id('https://example.com/schemas/address')
            ->schema('https://spec.openapis.org/oas/3.1/dialect/base')
            ->examples(['country' => 'US'], ['country' => 'FR'])
            ->type('object', 'null');
    }

    public static function arrayKeywords202012(Version $version): Schema
    {
        return Schema::array()
            ->unevaluatedItems(false)
            ->maxContains(3)
            ->minContains(1)
            ->contains(Schema::integer())
            ->prefixItems(Schema::string(), Schema::integer());
    }

    public static function reference(Version $version): Reference
    {
        $reference = Reference::to('#/components/schemas/User', 'owner');

        return self::atLeast($version, Version::V3_1_0) ? $reference->description('The owner of the resource')->summary('Owner') : $reference;
    }

    public static function openApi(Version $version): OpenApi
    {
        $openApi = OpenApi::create()
            ->externalDocs(ExternalDocumentation::create()->url('https://example.com/docs'))
            ->tags(Tag::create()->name('users'))
            ->security(SecurityRequirement::create()->schemes(['ApiKey' => []]))
            ->components(Components::create()->securitySchemes(SecurityScheme::apiKey('ApiKey')->name('X-API-Key')->in('header')))
            ->paths(PathItem::create('/users')->get(Operation::get()->tags('users')->operationId('listUsers')->responses(Response::ok())))
            ->servers(Server::create()->url('https://api.example.com'))
            ->info(Info::create()->title('Users API')->version('1.0.0'))
            ->openapi($version)
            ->x('generator', 'ark4ne/openapi-writer');

        if (self::atLeast($version, Version::V3_1_0)) {
            $openApi = $openApi
                ->webhooks(PathItem::create('userCreated')->post(Operation::post()->responses(Response::ok())))
                ->jsonSchemaDialect(self::atLeast($version, Version::V3_2_0) ? 'https://spec.openapis.org/oas/3.2/dialect/2025-09-17' : 'https://spec.openapis.org/oas/3.1/dialect/base');
        }

        if (self::atLeast($version, Version::V3_2_0)) {
            $openApi = $openApi->self('https://api.example.com/openapi.json');
        }

        return $openApi;
    }
}
