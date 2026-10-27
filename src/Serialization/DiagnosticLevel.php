<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Serialization;

enum DiagnosticLevel: string
{
    /** Written in another form that keeps its meaning (`nullable` → `type: [X, "null"]`). */
    case Converted = 'converted';

    /** Not expressible in the target version and removed (lenient mode). */
    case Dropped = 'dropped';

    /** Not expressible in the target version (strict mode turns these into an exception). */
    case Unsupported = 'unsupported';
}
