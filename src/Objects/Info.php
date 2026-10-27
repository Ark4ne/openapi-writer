<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Objects;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Metadata\Field;
use Ark4ne\OpenApiWriter\Support\Undefined;

/**
 * Metadata about the API.
 *
 * @see https://spec.openapis.org/oas/v3.2.0.html#info-object
 *
 * @property-read string|null $title
 * @property-read string|null $summary
 * @property-read string|null $description
 * @property-read string|null $termsOfService
 * @property-read Contact|null $contact
 * @property-read License|null $license
 * @property-read string|null $version
 */
final class Info extends ExtensibleSpecObject
{
    #[Field] protected string|Undefined $title = Undefined::Value;
    #[Field(since: Version::V3_1_0)] protected string|Undefined $summary = Undefined::Value;
    #[Field] protected string|Undefined $description = Undefined::Value;
    #[Field] protected string|Undefined $termsOfService = Undefined::Value;
    #[Field] protected Contact|Undefined $contact = Undefined::Value;
    #[Field] protected License|Undefined $license = Undefined::Value;
    #[Field] protected string|Undefined $version = Undefined::Value;

    public function title(?string $title): static
    {
        return $this->with('title', $title);
    }

    public function summary(?string $summary): static
    {
        return $this->with('summary', $summary);
    }

    public function description(?string $description): static
    {
        return $this->with('description', $description);
    }

    public function termsOfService(?string $termsOfService): static
    {
        return $this->with('termsOfService', $termsOfService);
    }

    public function contact(?Contact $contact): static
    {
        return $this->with('contact', $contact);
    }

    public function license(?License $license): static
    {
        return $this->with('license', $license);
    }

    public function version(?string $version): static
    {
        return $this->with('version', $version);
    }
}
