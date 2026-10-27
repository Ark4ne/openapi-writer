<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Objects;

use Ark4ne\OpenApiWriter\Objects\OAuthFlow;
use Ark4ne\OpenApiWriter\Objects\OAuthFlows;

final class OAuthFlowsTest extends ExtensibleSpecObjectTestCase
{
    protected static function objectClass(): string
    {
        return OAuthFlows::class;
    }

    public static function fields(): array
    {
        $implicit = OAuthFlow::create()->authorizationUrl('https://example.com/oauth/authorize')->scopes([]);
        $password = OAuthFlow::create()->tokenUrl('https://example.com/oauth/token')->scopes([]);
        $clientCredentials = OAuthFlow::create()->tokenUrl('https://example.com/oauth/token')->scopes([]);
        $authorizationCode = OAuthFlow::create()->authorizationUrl('https://example.com/oauth/authorize')->tokenUrl('https://example.com/oauth/token')->scopes([]);
        $deviceAuthorization = OAuthFlow::create()->deviceAuthorizationUrl('https://example.com/oauth/device')->tokenUrl('https://example.com/oauth/token')->scopes([]);

        return [
            'implicit' => [[$implicit], $implicit],
            'password' => [[$password], $password],
            'clientCredentials' => [[$clientCredentials], $clientCredentials],
            'authorizationCode' => [[$authorizationCode], $authorizationCode],
            'deviceAuthorization' => [[$deviceAuthorization], $deviceAuthorization],
        ];
    }
}
