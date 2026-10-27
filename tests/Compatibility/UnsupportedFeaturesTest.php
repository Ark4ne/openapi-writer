<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Compatibility;

use Ark4ne\OpenApiWriter\Enums\Strictness;
use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Objects\Callback;
use Ark4ne\OpenApiWriter\Objects\Components;
use Ark4ne\OpenApiWriter\Objects\Discriminator;
use Ark4ne\OpenApiWriter\Objects\Encoding;
use Ark4ne\OpenApiWriter\Objects\Example;
use Ark4ne\OpenApiWriter\Objects\Info;
use Ark4ne\OpenApiWriter\Objects\License;
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
use Ark4ne\OpenApiWriter\Objects\Schema;
use Ark4ne\OpenApiWriter\Objects\SecurityRequirement;
use Ark4ne\OpenApiWriter\Objects\SecurityScheme;
use Ark4ne\OpenApiWriter\Objects\Server;
use Ark4ne\OpenApiWriter\Objects\Tag;
use Ark4ne\OpenApiWriter\Objects\Xml;
use Ark4ne\OpenApiWriter\Serialization\DiagnosticLevel;
use Ark4ne\OpenApiWriter\Serialization\Serializer;
use Ark4ne\OpenApiWriter\Tests\Support\CompleteObjects;
use Ark4ne\OpenApiWriter\Tests\Support\Documents;
use Ark4ne\OpenApiWriter\Tests\TestCase;
use Closure;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Each row of the table "Non exprimables dans une version antérieure" (specification, section 6), one feature per
 * row, for every version. From the version that introduced it the feature is written as is; before it, strict mode
 * throws one UnsupportedFeatureException with the pointer of the feature, and lenient mode drops it and reports it
 * as Dropped. The expected output of a line is `tests/fixtures/<line>/unsupported/<row>.json`.
 *
 * An unavailable enum case (`in: querystring`, `type: mutualTLS`, `style: cookie`) drops the object that holds it,
 * so that the output stays valid. Scopes on a non-OAuth2 scheme are emptied rather than dropped.
 */
final class UnsupportedFeaturesTest extends TestCase
{
    /**
     * Keyed by row: the document, the version that introduced the feature, and its pointers.
     *
     * @return array<string, array{Closure(): OpenApi, Version, list<string>}>
     */
    public static function rows(): array
    {
        $rows = [
            'webhooks' => [
                static fn(): OpenApi => Documents::compatibility()->webhooks(PathItem::create('userCreated')->post(Operation::post()->responses(Response::ok()))),
                Version::V3_1_0,
                ['/webhooks'],
            ],
            'json-schema-dialect' => [
                static fn(): OpenApi => Documents::compatibility()->jsonSchemaDialect('https://spec.openapis.org/oas/3.1/dialect/base'),
                Version::V3_1_0,
                ['/jsonSchemaDialect'],
            ],
            'components-path-items' => [
                static fn(): OpenApi => self::components(Components::create()->pathItems(PathItem::create('Ping')->get(Operation::get()->responses(Response::noContent())))),
                Version::V3_1_0,
                ['/components/pathItems'],
            ],
            'info-summary' => [
                static fn(): OpenApi => Documents::compatibility()->info(Info::create()->title('Compatibility')->summary('Compatibility matrix')->version('1.0.0')),
                Version::V3_1_0,
                ['/info/summary'],
            ],
            'license-identifier' => [
                static fn(): OpenApi => Documents::compatibility()->info(Info::create()->title('Compatibility')->version('1.0.0')->license(License::create()->name('MIT')->identifier('MIT'))),
                Version::V3_1_0,
                ['/info/license/identifier'],
            ],
            'reference-summary-and-description' => [
                static fn(): OpenApi => self::components(Components::create()->responses(
                    Reference::to('#/components/responses/Missing', 'NotFound')->summary('Not found')->description('The resource does not exist'),
                )),
                Version::V3_1_0,
                ['/components/responses/NotFound/summary', '/components/responses/NotFound/description'],
            ],
            'mutual-tls' => [
                static fn(): OpenApi => self::components(Components::create()->securitySchemes(SecurityScheme::mutualTLS('Certificate')->description('Client certificate'))),
                Version::V3_1_0,
                ['/components/securitySchemes/Certificate'],
            ],
            'non-oauth-scopes' => [
                static fn(): OpenApi => self::components(Components::create()->securitySchemes(SecurityScheme::apiKey('ApiKey')->name('X-API-Key')->in('header')))
                    ->security(SecurityRequirement::create()->schemes(['ApiKey' => ['read']])),
                Version::V3_1_0,
                ['/security/0/ApiKey'],
            ],
            'content-encoding' => [
                static fn(): OpenApi => self::components(Components::create()->schemas(Schema::string('Token')->contentEncoding('base64url'))),
                Version::V3_1_0,
                ['/components/schemas/Token/contentEncoding'],
            ],
            'non-oauth-scopes-in-a-callback' => [
                static fn(): OpenApi => Documents::compatibility()
                    ->paths(PathItem::create('/hooks')->post(Operation::post()->responses(Response::ok())->callbacks(
                        Callback::create('onEvent')->pathItems(PathItem::create('{$request.body#/url}')->post(
                            Operation::post()->responses(Response::ok())->security(SecurityRequirement::create()->schemes(['ApiKey' => ['read']])),
                        )),
                    )))
                    ->components(Components::create()->securitySchemes(SecurityScheme::apiKey('ApiKey')->name('X-API-Key')->in('header'))),
                Version::V3_1_0,
                ['/paths/~1hooks/post/callbacks/onEvent/{$request.body#~1url}/post/security/0/ApiKey'],
            ],
            'self' => [
                static fn(): OpenApi => Documents::compatibility()->self('https://api.example.com/openapi.json'),
                Version::V3_2_0,
                ['/$self'],
            ],
            'components-media-types' => [
                static fn(): OpenApi => self::components(Components::create()->mediaTypes(MediaType::create('UserJson')->schema(Schema::ref('User')))),
                Version::V3_2_0,
                ['/components/mediaTypes'],
            ],
            'server-name' => [
                static fn(): OpenApi => Documents::compatibility()->servers(Server::create()->url('https://api.example.com')->name('production')),
                Version::V3_2_0,
                ['/servers/0/name'],
            ],
            'query-operation' => [
                static fn(): OpenApi => Documents::compatibility()->paths(PathItem::create('/users')->query(Operation::query()->responses(Response::ok()))),
                Version::V3_2_0,
                ['/paths/~1users/query'],
            ],
            'additional-operations' => [
                static fn(): OpenApi => Documents::compatibility()->paths(PathItem::create('/users')->additionalOperations(Operation::create('COPY')->responses(Response::ok()))),
                Version::V3_2_0,
                ['/paths/~1users/additionalOperations'],
            ],
            'querystring-parameter' => [
                static fn(): OpenApi => Documents::compatibility()->paths(PathItem::create('/search')->get(
                    Operation::get()
                        ->parameters(Parameter::querystring('filter')->content(MediaType::formUrlEncoded()->schema(Schema::object())))
                        ->responses(Response::ok()),
                )),
                Version::V3_2_0,
                ['/paths/~1search/get/parameters/0'],
            ],
            'cookie-style' => [
                static fn(): OpenApi => self::components(Components::create()->parameters(Parameter::cookie('session', 'Session')->style('cookie')->schema(Schema::string()))),
                Version::V3_2_0,
                ['/components/parameters/Session'],
            ],
            'tag-summary' => [
                static fn(): OpenApi => Documents::compatibility()->tags(Tag::create()->name('pets')->summary('Pets')),
                Version::V3_2_0,
                ['/tags/0/summary'],
            ],
            'tag-parent' => [
                static fn(): OpenApi => Documents::compatibility()->tags(Tag::create()->name('animals'), Tag::create()->name('pets')->parent('animals')),
                Version::V3_2_0,
                ['/tags/1/parent'],
            ],
            'tag-kind' => [
                static fn(): OpenApi => Documents::compatibility()->tags(Tag::create()->name('pets')->kind('nav')),
                Version::V3_2_0,
                ['/tags/0/kind'],
            ],
            'media-type-description' => [
                static fn(): OpenApi => self::requestBody(MediaType::json()->description('A user')->schema(Schema::ref('User'))),
                Version::V3_2_0,
                ['/components/requestBodies/Body/content/application~1json/description'],
            ],
            'media-type-item-schema' => [
                static fn(): OpenApi => self::requestBody(MediaType::of('application/jsonl')->itemSchema(Schema::ref('Event'))),
                Version::V3_2_0,
                ['/components/requestBodies/Body/content/application~1jsonl/itemSchema'],
            ],
            'media-type-prefix-encoding' => [
                static fn(): OpenApi => self::requestBody(MediaType::of('multipart/mixed')->prefixEncoding(Encoding::create()->contentType('application/json'))),
                Version::V3_2_0,
                ['/components/requestBodies/Body/content/multipart~1mixed/prefixEncoding'],
            ],
            'media-type-item-encoding' => [
                static fn(): OpenApi => self::requestBody(MediaType::of('multipart/mixed')->itemEncoding(Encoding::create()->contentType('image/png'))),
                Version::V3_2_0,
                ['/components/requestBodies/Body/content/multipart~1mixed/itemEncoding'],
            ],
            'encoding-encoding' => [
                static fn(): OpenApi => self::requestBody(MediaType::multipart()->encoding(
                    Encoding::create('parts')->contentType('multipart/mixed')->encoding(Encoding::create('inner')->contentType('text/plain')),
                )),
                Version::V3_2_0,
                ['/components/requestBodies/Body/content/multipart~1form-data/encoding/parts/encoding'],
            ],
            'encoding-prefix-encoding' => [
                static fn(): OpenApi => self::requestBody(MediaType::multipart()->encoding(
                    Encoding::create('parts')->contentType('multipart/mixed')->prefixEncoding(Encoding::create()->contentType('text/plain')),
                )),
                Version::V3_2_0,
                ['/components/requestBodies/Body/content/multipart~1form-data/encoding/parts/prefixEncoding'],
            ],
            'encoding-item-encoding' => [
                static fn(): OpenApi => self::requestBody(MediaType::multipart()->encoding(
                    Encoding::create('parts')->contentType('multipart/mixed')->itemEncoding(Encoding::create()->contentType('text/plain')),
                )),
                Version::V3_2_0,
                ['/components/requestBodies/Body/content/multipart~1form-data/encoding/parts/itemEncoding'],
            ],
            'example-data-value' => [
                static fn(): OpenApi => self::components(Components::create()->examples(Example::create('Date')->summary('A date')->dataValue('2025-06-01'))),
                Version::V3_2_0,
                ['/components/examples/Date/dataValue'],
            ],
            'example-serialized-value' => [
                static fn(): OpenApi => self::components(Components::create()->examples(Example::create('Date')->summary('A date')->serializedValue('2025-06-01'))),
                Version::V3_2_0,
                ['/components/examples/Date/serializedValue'],
            ],
            'discriminator-default-mapping' => [
                static fn(): OpenApi => self::components(Components::create()->schemas(
                    Schema::object('Pet')->discriminator(Discriminator::create()->propertyName('petType')->defaultMapping('#/components/schemas/Cat')),
                )),
                Version::V3_2_0,
                ['/components/schemas/Pet/discriminator/defaultMapping'],
            ],
            'xml-node-type' => [
                static fn(): OpenApi => self::components(Components::create()->schemas(Schema::string('Name')->xml(Xml::create()->nodeType('attribute')))),
                Version::V3_2_0,
                ['/components/schemas/Name/xml/nodeType'],
            ],
            'device-authorization-flow' => [
                static fn(): OpenApi => self::components(Components::create()->securitySchemes(SecurityScheme::oauth2('OAuth2')->flows(
                    OAuthFlows::create()
                        ->clientCredentials(OAuthFlow::create()->tokenUrl('https://example.com/oauth/token')->scopes([]))
                        ->deviceAuthorization(OAuthFlow::create()->deviceAuthorizationUrl('https://example.com/oauth/device')->tokenUrl('https://example.com/oauth/token')->scopes([])),
                ))),
                Version::V3_2_0,
                ['/components/securitySchemes/OAuth2/flows/deviceAuthorization'],
            ],
            'oauth2-metadata-url' => [
                static fn(): OpenApi => self::components(Components::create()->securitySchemes(
                    SecurityScheme::oauth2('OAuth2')
                        ->flows(OAuthFlows::create()->clientCredentials(OAuthFlow::create()->tokenUrl('https://example.com/oauth/token')->scopes([])))
                        ->oauth2MetadataUrl('https://example.com/.well-known/oauth-authorization-server'),
                )),
                Version::V3_2_0,
                ['/components/securitySchemes/OAuth2/oauth2MetadataUrl'],
            ],
            'security-scheme-deprecated' => [
                static fn(): OpenApi => self::components(Components::create()->securitySchemes(SecurityScheme::apiKey('ApiKey')->name('X-API-Key')->in('header')->deprecated(true))),
                Version::V3_2_0,
                ['/components/securitySchemes/ApiKey/deprecated'],
            ],
            'response-summary' => [
                static fn(): OpenApi => self::components(Components::create()->responses(Response::create('NotFound')->summary('Not found')->description('The resource does not exist'))),
                Version::V3_2_0,
                ['/components/responses/NotFound/summary'],
            ],
            'media-type-reference' => [
                static fn(): OpenApi => self::components(Components::create()->responses(
                    Response::create('User')->description('A user')->content(MediaType::ref('UserJson', 'application/json')),
                )),
                Version::V3_2_0,
                ['/components/responses/User/content/application~1json'],
            ],
            'path-item-ref' => [
                static fn(): OpenApi => Documents::compatibility()->paths(PathItem::create('/users')->ref('https://example.com/paths/users.json')),
                Version::V3_0_0,
                [],
            ],
        ];

        foreach (self::jsonSchemaKeywords() as $keyword => [$schema, $name]) {
            $rows['json-schema-' . $keyword] = [
                static fn(): OpenApi => self::components(Components::create()->schemas($schema)),
                Version::V3_1_0,
                ['/components/schemas/Keyword/' . $name],
            ];
        }

        return $rows;
    }

    /**
     * JSON Schema 2020-12 keywords without 3.0 equivalent: the schema, and the name of the keyword in the output.
     *
     * @return array<string, array{Schema, string}>
     */
    public static function jsonSchemaKeywords(): array
    {
        $keyword = Schema::object('Keyword');

        return [
            'schema' => [$keyword->schema('https://spec.openapis.org/oas/3.1/dialect/base'), '$schema'],
            'id' => [$keyword->id('https://example.com/schemas/keyword'), '$id'],
            'anchor' => [$keyword->anchor('keyword'), '$anchor'],
            'dynamic-anchor' => [$keyword->dynamicAnchor('node'), '$dynamicAnchor'],
            'dynamic-ref' => [$keyword->dynamicRef('#node'), '$dynamicRef'],
            'defs' => [$keyword->defs(Schema::string('Name')), '$defs'],
            'comment' => [$keyword->comment('A comment for maintainers'), '$comment'],
            'content-schema' => [$keyword->contentSchema(Schema::object()), 'contentSchema'],
            'prefix-items' => [$keyword->prefixItems(Schema::string(), Schema::integer()), 'prefixItems'],
            'contains' => [$keyword->contains(Schema::integer()), 'contains'],
            'min-contains' => [$keyword->minContains(1), 'minContains'],
            'max-contains' => [$keyword->maxContains(2), 'maxContains'],
            'unevaluated-items' => [$keyword->unevaluatedItems(false), 'unevaluatedItems'],
            'pattern-properties' => [$keyword->patternProperties(Schema::string('^x-')), 'patternProperties'],
            'property-names' => [$keyword->propertyNames(Schema::create()->pattern('^[a-z]+$')), 'propertyNames'],
            'unevaluated-properties' => [$keyword->unevaluatedProperties(false), 'unevaluatedProperties'],
            'dependent-required' => [$keyword->dependentRequired(['creditCard' => ['billingAddress']]), 'dependentRequired'],
            'dependent-schemas' => [$keyword->dependentSchemas(Schema::object('creditCard')->required('billingAddress')), 'dependentSchemas'],
            'if' => [$keyword->if(Schema::object()->required('country')), 'if'],
            'then' => [$keyword->then(Schema::object()->required('zipCode')), 'then'],
            'else' => [$keyword->else(Schema::object()->required('postalCode')), 'else'],
        ];
    }

    /**
     * @return iterable<string, array{string, Version}>
     */
    public static function cases(): iterable
    {
        foreach (array_keys(self::rows()) as $row) {
            foreach (Version::cases() as $version) {
                yield $row . ' in ' . $version->value => [$row, $version];
            }
        }
    }

    #[DataProvider('cases')]
    public function test_strict_mode_writes_the_feature_or_rejects_it(string $row, Version $version): void
    {
        [$build, $since, $pointers] = self::rows()[$row];

        if (!CompleteObjects::atLeast($version, $since)) {
            self::assertUnsupported($pointers, static fn(): string => $build()->toJson($version));

            return;
        }

        $result = Serializer::for($version)->serialize($build());

        self::assertResultSerializesTo($result, OpenApi::class, $version, 'unsupported/' . $row);
        self::assertTrue($result->diagnostics()->isEmpty());
    }

    #[DataProvider('cases')]
    public function test_lenient_mode_writes_the_feature_or_drops_and_reports_it(string $row, Version $version): void
    {
        [$build, $since, $pointers] = self::rows()[$row];

        $result = Serializer::for($version, Strictness::Lenient)->serialize($build());

        $dropped = CompleteObjects::atLeast($version, $since) ? [] : $pointers;
        self::assertResultSerializesTo($result, OpenApi::class, $version, 'unsupported/' . $row);
        self::assertDiagnostics(array_map(static fn(string $pointer): array => [DiagnosticLevel::Dropped, $pointer], $dropped), $result->diagnostics());
    }

    private static function components(Components $components): OpenApi
    {
        return Documents::compatibility()->components($components);
    }

    private static function requestBody(MediaType $mediaType): OpenApi
    {
        return self::components(Components::create()->requestBodies(RequestBody::create('Body')->content($mediaType)));
    }
}
