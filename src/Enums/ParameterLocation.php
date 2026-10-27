<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Enums;

/**
 * Location of a parameter (`in`); `querystring` since 3.2.
 */
enum ParameterLocation: string implements VersionedEnum
{
    case Query = 'query';
    case Header = 'header';
    case Path = 'path';
    case Cookie = 'cookie';
    case Querystring = 'querystring';

    public function since(): Version
    {
        return match ($this) {
            self::Querystring => Version::V3_2_0,
            default => Version::V3_0_0,
        };
    }
}
