<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Objects;

use Ark4ne\OpenApiWriter\Contracts\SecuritySchemeContract;
use Ark4ne\OpenApiWriter\Enums\ApiKeyLocation;
use Ark4ne\OpenApiWriter\Enums\SecuritySchemeType;
use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Exceptions\InvalidArgumentException;
use Ark4ne\OpenApiWriter\Metadata\Field;
use Ark4ne\OpenApiWriter\Support\Undefined;

/**
 * A security scheme usable by operations; its objectId is the scheme name.
 *
 * @see https://spec.openapis.org/oas/v3.2.0.html#security-scheme-object
 *
 * @property-read SecuritySchemeType|null $type
 * @property-read string|null $description
 * @property-read string|null $name
 * @property-read ApiKeyLocation|null $in
 * @property-read string|null $scheme
 * @property-read string|null $bearerFormat
 * @property-read OAuthFlows|null $flows
 * @property-read string|null $openIdConnectUrl
 * @property-read string|null $oauth2MetadataUrl
 * @property-read bool|null $deprecated
 */
final class SecurityScheme extends ExtensibleSpecObject implements SecuritySchemeContract
{
    #[Field] protected SecuritySchemeType|Undefined $type = Undefined::Value;
    #[Field] protected string|Undefined $description = Undefined::Value;
    #[Field] protected string|Undefined $name = Undefined::Value;
    #[Field] protected ApiKeyLocation|Undefined $in = Undefined::Value;
    #[Field] protected string|Undefined $scheme = Undefined::Value;
    #[Field] protected string|Undefined $bearerFormat = Undefined::Value;
    #[Field] protected OAuthFlows|Undefined $flows = Undefined::Value;
    #[Field] protected string|Undefined $openIdConnectUrl = Undefined::Value;
    #[Field(since: Version::V3_2_0)] protected string|Undefined $oauth2MetadataUrl = Undefined::Value;
    #[Field(since: Version::V3_2_0)] protected bool|Undefined $deprecated = Undefined::Value;

    public static function apiKey(?string $objectId = null): static
    {
        return static::create($objectId)->type(SecuritySchemeType::ApiKey);
    }

    public static function http(?string $objectId = null): static
    {
        return static::create($objectId)->type(SecuritySchemeType::Http);
    }

    public static function oauth2(?string $objectId = null): static
    {
        return static::create($objectId)->type(SecuritySchemeType::OAuth2);
    }

    public static function openIdConnect(?string $objectId = null): static
    {
        return static::create($objectId)->type(SecuritySchemeType::OpenIdConnect);
    }

    public static function mutualTLS(?string $objectId = null): static
    {
        return static::create($objectId)->type(SecuritySchemeType::MutualTLS);
    }

    /**
     * Reference to `#/components/securitySchemes/<name>`. The path is built here, since a Reference does not know its
     * section; a value containing `#` or `/` is written as is.
     *
     * @param string|null $objectId key of the reference in its parent
     */
    public static function ref(string $ref, ?string $objectId = null): Reference
    {
        return Reference::create($objectId);
    }

    /**
     * @throws InvalidArgumentException when the string is not a value of the enum
     */
    public function type(SecuritySchemeType|string|null $type): static
    {
        return $this->with('type', $type);
    }

    public function description(?string $description): static
    {
        return $this->with('description', $description);
    }

    public function name(?string $name): static
    {
        return $this->with('name', $name);
    }

    /**
     * @throws InvalidArgumentException when the string is not a value of the enum
     */
    public function in(ApiKeyLocation|string|null $in): static
    {
        return $this->with('in', $in);
    }

    public function scheme(?string $scheme): static
    {
        return $this->with('scheme', $scheme);
    }

    public function bearerFormat(?string $bearerFormat): static
    {
        return $this->with('bearerFormat', $bearerFormat);
    }

    public function flows(?OAuthFlows $flows): static
    {
        return $this->with('flows', $flows);
    }

    public function openIdConnectUrl(?string $openIdConnectUrl): static
    {
        return $this->with('openIdConnectUrl', $openIdConnectUrl);
    }

    public function oauth2MetadataUrl(?string $oauth2MetadataUrl): static
    {
        return $this->with('oauth2MetadataUrl', $oauth2MetadataUrl);
    }

    public function deprecated(?bool $deprecated = true): static
    {
        return $this->with('deprecated', $deprecated);
    }
}
