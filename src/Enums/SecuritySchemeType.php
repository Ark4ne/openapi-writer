<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Enums;

/**
 * Type of a Security Scheme Object; `mutualTLS` since 3.1.
 */
enum SecuritySchemeType: string implements VersionedEnum
{
    case ApiKey = 'apiKey';
    case Http = 'http';
    case OAuth2 = 'oauth2';
    case OpenIdConnect = 'openIdConnect';
    case MutualTLS = 'mutualTLS';

    public function since(): Version
    {
        return match ($this) {
            self::MutualTLS => Version::V3_1_0,
            default => Version::V3_0_0,
        };
    }
}
