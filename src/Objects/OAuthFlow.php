<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Objects;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Exceptions\DuplicateObjectIdException;
use Ark4ne\OpenApiWriter\Metadata\Field;
use Ark4ne\OpenApiWriter\Support\Undefined;

/**
 * Configuration of one OAuth flow.
 *
 * @see https://spec.openapis.org/oas/v3.2.0.html#oauth-flow-object
 *
 * @property-read string|null $authorizationUrl
 * @property-read string|null $deviceAuthorizationUrl
 * @property-read string|null $tokenUrl
 * @property-read string|null $refreshUrl
 * @property-read array<string, string>|null $scopes
 */
final class OAuthFlow extends ExtensibleSpecObject
{
    #[Field] protected string|Undefined $authorizationUrl = Undefined::Value;
    #[Field(since: Version::V3_2_0)] protected string|Undefined $deviceAuthorizationUrl = Undefined::Value;
    #[Field] protected string|Undefined $tokenUrl = Undefined::Value;
    #[Field] protected string|Undefined $refreshUrl = Undefined::Value;
    /** @var array<string, string>|Undefined */
    #[Field(map: true, keyedByObjectId: false)] protected array|Undefined $scopes = Undefined::Value;

    public function authorizationUrl(?string $authorizationUrl): static
    {
        return $this->with('authorizationUrl', $authorizationUrl);
    }

    public function deviceAuthorizationUrl(?string $deviceAuthorizationUrl): static
    {
        return $this->with('deviceAuthorizationUrl', $deviceAuthorizationUrl);
    }

    public function tokenUrl(?string $tokenUrl): static
    {
        return $this->with('tokenUrl', $tokenUrl);
    }

    public function refreshUrl(?string $refreshUrl): static
    {
        return $this->with('refreshUrl', $refreshUrl);
    }

    /**
     * Descriptions keyed by scope name; written `{}` when empty.
     *
     * @param array<string, string> $scopes
     */
    public function scopes(array $scopes): static
    {
        return $this->with('scopes', $scopes);
    }

    /**
     * Appends entries; a key already present throws.
     *
     * @param array<string, string> $scopes
     *
     * @throws DuplicateObjectIdException
     */
    public function addScopes(array $scopes): static
    {
        return $this->withAdded('scopes', $scopes);
    }
}
