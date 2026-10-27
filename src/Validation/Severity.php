<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Validation;

enum Severity: string
{
    case Error = 'error';

    /** Reported without making the document invalid (undeclared tags, examples that do not match their schema). */
    case Warning = 'warning';
}
