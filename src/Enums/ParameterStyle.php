<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Enums;

/**
 * Serialization style of a parameter or an encoding (`style`); `cookie` since 3.2.
 */
enum ParameterStyle: string implements VersionedEnum
{
    case Matrix = 'matrix';
    case Label = 'label';
    case Simple = 'simple';
    case Form = 'form';
    case SpaceDelimited = 'spaceDelimited';
    case PipeDelimited = 'pipeDelimited';
    case DeepObject = 'deepObject';
    case Cookie = 'cookie';

    public function since(): Version
    {
        return match ($this) {
            self::Cookie => Version::V3_2_0,
            default => Version::V3_0_0,
        };
    }
}
