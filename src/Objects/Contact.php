<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Objects;

use Ark4ne\OpenApiWriter\Metadata\Field;
use Ark4ne\OpenApiWriter\Support\Undefined;

/**
 * Contact information for the API.
 *
 * @see https://spec.openapis.org/oas/v3.2.0.html#contact-object
 *
 * @property-read string|null $name
 * @property-read string|null $url
 * @property-read string|null $email
 */
final class Contact extends ExtensibleSpecObject
{
    #[Field] protected string|Undefined $name = Undefined::Value;
    #[Field] protected string|Undefined $url = Undefined::Value;
    #[Field] protected string|Undefined $email = Undefined::Value;

    public function name(?string $name): static
    {
        return $this->with('name', $name);
    }

    public function url(?string $url): static
    {
        return $this->with('url', $url);
    }

    public function email(?string $email): static
    {
        return $this->with('email', $email);
    }
}
