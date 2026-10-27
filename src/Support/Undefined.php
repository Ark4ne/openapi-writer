<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Support;

/**
 * Value of a field that was never set: it is left out of the output, unlike `null` which is written.
 *
 * @internal Never part of a public signature.
 */
enum Undefined
{
    case Value;
}
