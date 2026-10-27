<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Objects;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Metadata\Field;
use Ark4ne\OpenApiWriter\Support\Undefined;

/**
 * The OAuth flows supported by an `oauth2` security scheme.
 *
 * @see https://spec.openapis.org/oas/v3.2.0.html#oauth-flows-object
 *
 * @property-read OAuthFlow|null $implicit
 * @property-read OAuthFlow|null $password
 * @property-read OAuthFlow|null $clientCredentials
 * @property-read OAuthFlow|null $authorizationCode
 * @property-read OAuthFlow|null $deviceAuthorization
 */
final class OAuthFlows extends ExtensibleSpecObject
{
    #[Field] protected OAuthFlow|Undefined $implicit = Undefined::Value;
    #[Field] protected OAuthFlow|Undefined $password = Undefined::Value;
    #[Field] protected OAuthFlow|Undefined $clientCredentials = Undefined::Value;
    #[Field] protected OAuthFlow|Undefined $authorizationCode = Undefined::Value;
    #[Field(since: Version::V3_2_0)] protected OAuthFlow|Undefined $deviceAuthorization = Undefined::Value;

    public function implicit(?OAuthFlow $implicit): static
    {
        return $this->with('implicit', $implicit);
    }

    public function password(?OAuthFlow $password): static
    {
        return $this->with('password', $password);
    }

    public function clientCredentials(?OAuthFlow $clientCredentials): static
    {
        return $this->with('clientCredentials', $clientCredentials);
    }

    public function authorizationCode(?OAuthFlow $authorizationCode): static
    {
        return $this->with('authorizationCode', $authorizationCode);
    }

    public function deviceAuthorization(?OAuthFlow $deviceAuthorization): static
    {
        return $this->with('deviceAuthorization', $deviceAuthorization);
    }
}
