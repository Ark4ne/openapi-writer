<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Objects;

use Ark4ne\OpenApiWriter\Metadata\Field;
use Ark4ne\OpenApiWriter\Support\Undefined;

/**
 * A reference to external documentation.
 *
 * @see https://spec.openapis.org/oas/v3.2.0.html#external-documentation-object
 *
 * @property-read string|null $description
 * @property-read string|null $url
 */
final class ExternalDocumentation extends ExtensibleSpecObject
{
    #[Field] protected string|Undefined $description = Undefined::Value;
    #[Field] protected string|Undefined $url = Undefined::Value;

    public function description(?string $description): static
    {
        return $this->with('description', $description);
    }

    public function url(?string $url): static
    {
        return $this->with('url', $url);
    }
}
