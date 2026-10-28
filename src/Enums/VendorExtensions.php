<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Enums;

/**
 * Whether serialization writes, as vendor extensions, features that the target version cannot express.
 */
enum VendorExtensions: string
{
    /** Follow the specification: such features are unsupported (default). */
    case None = 'none';

    /** Write the Redoc extensions: `x-webhooks` before 3.1, `x-tagGroups` for `Tag.parent` before 3.2. */
    case Redoc = 'redoc';
}
