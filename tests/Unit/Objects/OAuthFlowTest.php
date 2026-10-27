<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Objects;

use Ark4ne\OpenApiWriter\Objects\OAuthFlow;

final class OAuthFlowTest extends ExtensibleSpecObjectTestCase
{
    protected static function objectClass(): string
    {
        return OAuthFlow::class;
    }

    public static function fields(): array
    {
        $scopes = ['write:pets' => 'modify pets in your account', 'read:pets' => 'read your pets'];

        return [
            'authorizationUrl' => [['https://example.com/api/oauth/dialog'], 'https://example.com/api/oauth/dialog'],
            'deviceAuthorizationUrl' => [['https://example.com/api/oauth/device'], 'https://example.com/api/oauth/device'],
            'tokenUrl' => [['https://example.com/api/oauth/token'], 'https://example.com/api/oauth/token'],
            'refreshUrl' => [['https://example.com/api/oauth/refresh'], 'https://example.com/api/oauth/refresh'],
            'scopes' => [[$scopes], $scopes],
        ];
    }

    public function test_add_scopes_appends_after_the_existing_scopes(): void
    {
        $flow = OAuthFlow::create()->scopes(['read:pets' => 'read your pets']);

        $extended = $flow->addScopes(['write:pets' => 'modify pets in your account']);

        self::assertSame(['read:pets' => 'read your pets', 'write:pets' => 'modify pets in your account'], $extended->scopes);
    }

    public function test_empty_scopes_are_a_value(): void
    {
        $flow = OAuthFlow::create()->scopes([]);

        self::assertTrue($flow->has('scopes'));
        self::assertSame([], $flow->scopes);
    }
}
