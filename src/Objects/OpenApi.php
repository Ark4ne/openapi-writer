<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Objects;

use Ark4ne\OpenApiWriter\Contracts\PathItemContract;
use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Exceptions\DuplicateObjectIdException;
use Ark4ne\OpenApiWriter\Exceptions\InvalidArgumentException;
use Ark4ne\OpenApiWriter\Exceptions\MissingObjectIdException;
use Ark4ne\OpenApiWriter\Metadata\Field;
use Ark4ne\OpenApiWriter\Support\Undefined;
use JsonSerializable;

/**
 * Root object of an OpenAPI document.
 *
 * @see https://spec.openapis.org/oas/v3.2.0.html#openapi-object
 *
 * @property-read Version|null $openapi
 * @property-read string|null $self
 * @property-read Info|null $info
 * @property-read string|null $jsonSchemaDialect
 * @property-read list<Server>|null $servers
 * @property-read array<string, PathItem>|null $paths
 * @property-read array<string, PathItemContract>|null $webhooks
 * @property-read Components|null $components
 * @property-read list<SecurityRequirement>|null $security
 * @property-read list<Tag>|null $tags
 * @property-read ExternalDocumentation|null $externalDocs
 */
final class OpenApi extends ExtensibleSpecObject implements JsonSerializable
{
    #[Field(emit: false)] protected Version|Undefined $openapi = Undefined::Value;
    #[Field(name: '$self', since: Version::V3_2_0)] protected string|Undefined $self = Undefined::Value;
    #[Field] protected Info|Undefined $info = Undefined::Value;
    #[Field(since: Version::V3_1_0)] protected string|Undefined $jsonSchemaDialect = Undefined::Value;
    /** @var list<Server>|Undefined */
    #[Field] protected array|Undefined $servers = Undefined::Value;
    /** @var array<string, PathItem>|Undefined */
    #[Field(map: true)] protected array|Undefined $paths = Undefined::Value;
    /** @var array<string, PathItemContract>|Undefined */
    #[Field(since: Version::V3_1_0, map: true)] protected array|Undefined $webhooks = Undefined::Value;
    #[Field] protected Components|Undefined $components = Undefined::Value;
    /** @var list<SecurityRequirement>|Undefined */
    #[Field] protected array|Undefined $security = Undefined::Value;
    /** @var list<Tag>|Undefined */
    #[Field] protected array|Undefined $tags = Undefined::Value;
    #[Field] protected ExternalDocumentation|Undefined $externalDocs = Undefined::Value;

    /**
     * Version declared by the document, used by default to write it (`jsonSerialize()`, `toJson()`…). The written `openapi` field is always the target version.
     *
     * @throws InvalidArgumentException when the string is not a value of the enum
     */
    public function openapi(Version|string|null $openapi): static
    {
        return $this->with('openapi', $openapi);
    }

    /**
     * Sets `$self`, the URI of this document.
     */
    public function self(?string $self): static
    {
        return $this->with('self', $self);
    }

    public function info(?Info $info): static
    {
        return $this->with('info', $info);
    }

    public function jsonSchemaDialect(?string $jsonSchemaDialect): static
    {
        return $this->with('jsonSchemaDialect', $jsonSchemaDialect);
    }

    public function servers(Server ...$servers): static
    {
        return $this->with('servers', $servers);
    }

    public function addServers(Server ...$servers): static
    {
        return $this->withAdded('servers', $servers);
    }

    /**
     * Keyed by the objectId of each entry.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function paths(PathItem ...$paths): static
    {
        return $this->with('paths', $paths);
    }

    /**
     * Appends entries keyed by their objectId; an objectId already present throws.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function addPaths(PathItem ...$paths): static
    {
        return $this->withAdded('paths', $paths);
    }

    /**
     * Keyed by the objectId of each entry.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function webhooks(PathItemContract ...$webhooks): static
    {
        return $this->with('webhooks', $webhooks);
    }

    /**
     * Appends entries keyed by their objectId; an objectId already present throws.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function addWebhooks(PathItemContract ...$webhooks): static
    {
        return $this->withAdded('webhooks', $webhooks);
    }

    public function components(?Components $components): static
    {
        return $this->with('components', $components);
    }

    public function security(SecurityRequirement ...$security): static
    {
        return $this->with('security', $security);
    }

    public function addSecurity(SecurityRequirement ...$security): static
    {
        return $this->withAdded('security', $security);
    }

    public function tags(Tag ...$tags): static
    {
        return $this->with('tags', $tags);
    }

    public function addTags(Tag ...$tags): static
    {
        return $this->withAdded('tags', $tags);
    }

    public function externalDocs(?ExternalDocumentation $externalDocs): static
    {
        return $this->with('externalDocs', $externalDocs);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
