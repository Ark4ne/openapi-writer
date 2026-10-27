<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Enums;

/**
 * Location of an API key (`in` of an `apiKey` Security Scheme Object).
 */
enum ApiKeyLocation: string
{
    case Query = 'query';
    case Header = 'header';
    case Cookie = 'cookie';
}
