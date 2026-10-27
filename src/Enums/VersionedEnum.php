<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Enums;

/**
 * Enum whose cases appeared in different OpenAPI versions. Serialization treats a case like a field declared with
 * `#[Field(since: ...)]`.
 */
interface VersionedEnum
{
    /**
     * First version that knows this case.
     */
    public function since(): Version;
}
