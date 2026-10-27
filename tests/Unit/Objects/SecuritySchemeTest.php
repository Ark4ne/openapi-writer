<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Objects;

use Ark4ne\OpenApiWriter\Enums\ApiKeyLocation;
use Ark4ne\OpenApiWriter\Enums\SecuritySchemeType;
use Ark4ne\OpenApiWriter\Objects\OAuthFlow;
use Ark4ne\OpenApiWriter\Objects\OAuthFlows;
use Ark4ne\OpenApiWriter\Objects\SecurityScheme;
use PHPUnit\Framework\Attributes\DataProvider;

final class SecuritySchemeTest extends ExtensibleSpecObjectTestCase
{
    protected static function objectClass(): string
    {
        return SecurityScheme::class;
    }

    public static function fields(): array
    {
        $flows = OAuthFlows::create()->clientCredentials(OAuthFlow::create()->tokenUrl('https://example.com/oauth/token')->scopes([]));

        return [
            'type' => [['apiKey'], SecuritySchemeType::ApiKey],
            'description' => [['API key sent in a header'], 'API key sent in a header'],
            'name' => [['X-API-Key'], 'X-API-Key'],
            'in' => [['header'], ApiKeyLocation::Header],
            'scheme' => [['bearer'], 'bearer'],
            'bearerFormat' => [['JWT'], 'JWT'],
            'flows' => [[$flows], $flows],
            'openIdConnectUrl' => [['https://example.com/.well-known/openid-configuration'], 'https://example.com/.well-known/openid-configuration'],
            'oauth2MetadataUrl' => [['https://example.com/.well-known/oauth-authorization-server'], 'https://example.com/.well-known/oauth-authorization-server'],
            'deprecated' => [[true], true],
        ];
    }

    /**
     * @return iterable<string, array{SecurityScheme, SecuritySchemeType}>
     */
    public static function shortcuts(): iterable
    {
        yield 'api key' => [SecurityScheme::apiKey('ApiKey'), SecuritySchemeType::ApiKey];
        yield 'http' => [SecurityScheme::http('ApiKey'), SecuritySchemeType::Http];
        yield 'oauth2' => [SecurityScheme::oauth2('ApiKey'), SecuritySchemeType::OAuth2];
        yield 'openid connect' => [SecurityScheme::openIdConnect('ApiKey'), SecuritySchemeType::OpenIdConnect];
        yield 'mutual tls' => [SecurityScheme::mutualTLS('ApiKey'), SecuritySchemeType::MutualTLS];
    }

    #[DataProvider('shortcuts')]
    public function test_each_type_shortcut_sets_the_type_and_object_id(SecurityScheme $scheme, SecuritySchemeType $type): void
    {
        self::assertSame($type, $scheme->type);
        self::assertSame('ApiKey', $scheme->objectId);
    }

    public function test_in_and_type_accept_their_enum(): void
    {
        $scheme = SecurityScheme::create()->type(SecuritySchemeType::ApiKey)->in(ApiKeyLocation::Cookie);

        self::assertSame(SecuritySchemeType::ApiKey, $scheme->type);
        self::assertSame(ApiKeyLocation::Cookie, $scheme->in);
    }
}
