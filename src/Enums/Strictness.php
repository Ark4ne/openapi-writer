<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Enums;

/**
 * What serialization does with a feature the target version cannot express.
 */
enum Strictness: string
{
    /** Throw one UnsupportedFeatureException listing every unsupported feature (default). */
    case Strict = 'strict';

    /** Drop each unsupported feature and report it as a diagnostic. */
    case Lenient = 'lenient';
}
