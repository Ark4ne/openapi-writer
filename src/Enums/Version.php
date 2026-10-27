<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Enums;

/**
 * OpenAPI versions the library can write, one case per patch version. Within a line the output is identical.
 */
enum Version: string
{
    case V3_0_0 = '3.0.0';
    case V3_0_1 = '3.0.1';
    case V3_0_2 = '3.0.2';
    case V3_0_3 = '3.0.3';
    case V3_0_4 = '3.0.4';
    case V3_1_0 = '3.1.0';
    case V3_1_1 = '3.1.1';
    case V3_1_2 = '3.1.2';
    case V3_2_0 = '3.2.0';

    /**
     * Version written when none is given: 3.1.2.
     */
    public static function default(): self
    {
        return self::V3_1_2;
    }

    public function isAtLeast(self $version): bool
    {
        return false;
    }

    /**
     * Minor line of the version: `3.0`, `3.1` or `3.2`.
     */
    public function line(): string
    {
        return '';
    }
}
