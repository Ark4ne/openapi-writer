<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Enums;

/**
 * HTTP methods that have a fixed field in a Path Item Object (`query` since 3.2).
 */
enum HttpMethod: string implements VersionedEnum
{
    case Get = 'get';
    case Put = 'put';
    case Post = 'post';
    case Delete = 'delete';
    case Options = 'options';
    case Head = 'head';
    case Patch = 'patch';
    case Trace = 'trace';
    case Query = 'query';

    public function since(): Version
    {
        return match ($this) {
            self::Query => Version::V3_2_0,
            default => Version::V3_0_0,
        };
    }
}
