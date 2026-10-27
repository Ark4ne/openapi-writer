<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Objects;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Metadata\Field;
use Ark4ne\OpenApiWriter\Support\Undefined;

/**
 * License of the API. `url` and `identifier` are mutually exclusive.
 *
 * @see https://spec.openapis.org/oas/v3.2.0.html#license-object
 *
 * @property-read string|null $name
 * @property-read string|null $identifier
 * @property-read string|null $url
 */
final class License extends ExtensibleSpecObject
{
    #[Field] protected string|Undefined $name = Undefined::Value;
    #[Field(since: Version::V3_1_0)] protected string|Undefined $identifier = Undefined::Value;
    #[Field] protected string|Undefined $url = Undefined::Value;

    public function name(?string $name): static
    {
        return $this->with('name', $name);
    }

    /**
     * SPDX license expression.
     */
    public function identifier(?string $identifier): static
    {
        return $this->with('identifier', $identifier);
    }

    public function url(?string $url): static
    {
        return $this->with('url', $url);
    }
}
