<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Objects;

use Ark4ne\OpenApiWriter\Contracts\CallbackContract;
use Ark4ne\OpenApiWriter\Contracts\ParameterContract;
use Ark4ne\OpenApiWriter\Contracts\RequestBodyContract;
use Ark4ne\OpenApiWriter\Contracts\ResponseContract;
use Ark4ne\OpenApiWriter\Enums\HttpMethod;
use Ark4ne\OpenApiWriter\Exceptions\DuplicateObjectIdException;
use Ark4ne\OpenApiWriter\Exceptions\MissingObjectIdException;
use Ark4ne\OpenApiWriter\Metadata\Field;
use Ark4ne\OpenApiWriter\Support\Undefined;

/**
 * One API operation on a path. Its objectId is the HTTP method (`Operation::get()`).
 *
 * @see https://spec.openapis.org/oas/v3.2.0.html#operation-object
 *
 * @property-read list<string>|null $tags
 * @property-read string|null $summary
 * @property-read string|null $description
 * @property-read ExternalDocumentation|null $externalDocs
 * @property-read string|null $operationId
 * @property-read list<ParameterContract>|null $parameters
 * @property-read RequestBodyContract|null $requestBody
 * @property-read Responses|null $responses
 * @property-read array<string, CallbackContract>|null $callbacks
 * @property-read bool|null $deprecated
 * @property-read list<SecurityRequirement>|null $security
 * @property-read list<Server>|null $servers
 */
final class Operation extends ExtensibleSpecObject
{
    /** @var list<string>|Undefined */
    #[Field] protected array|Undefined $tags = Undefined::Value;
    #[Field] protected string|Undefined $summary = Undefined::Value;
    #[Field] protected string|Undefined $description = Undefined::Value;
    #[Field] protected ExternalDocumentation|Undefined $externalDocs = Undefined::Value;
    #[Field] protected string|Undefined $operationId = Undefined::Value;
    /** @var list<ParameterContract>|Undefined */
    #[Field] protected array|Undefined $parameters = Undefined::Value;
    #[Field] protected RequestBodyContract|Undefined $requestBody = Undefined::Value;
    #[Field] protected Responses|Undefined $responses = Undefined::Value;
    /** @var array<string, CallbackContract>|Undefined */
    #[Field(map: true)] protected array|Undefined $callbacks = Undefined::Value;
    #[Field] protected bool|Undefined $deprecated = Undefined::Value;
    /** @var list<SecurityRequirement>|Undefined */
    #[Field] protected array|Undefined $security = Undefined::Value;
    /** @var list<Server>|Undefined */
    #[Field] protected array|Undefined $servers = Undefined::Value;

    public static function get(): static
    {
        return static::create(HttpMethod::Get->value);
    }

    public static function put(): static
    {
        return static::create(HttpMethod::Put->value);
    }

    public static function post(): static
    {
        return static::create(HttpMethod::Post->value);
    }

    public static function delete(): static
    {
        return static::create(HttpMethod::Delete->value);
    }

    public static function options(): static
    {
        return static::create(HttpMethod::Options->value);
    }

    public static function head(): static
    {
        return static::create(HttpMethod::Head->value);
    }

    public static function patch(): static
    {
        return static::create(HttpMethod::Patch->value);
    }

    public static function trace(): static
    {
        return static::create(HttpMethod::Trace->value);
    }

    public static function query(): static
    {
        return static::create(HttpMethod::Query->value);
    }

    public function tags(string ...$tags): static
    {
        return $this->with('tags', $tags);
    }

    public function addTags(string ...$tags): static
    {
        return $this->withAdded('tags', $tags);
    }

    public function summary(?string $summary): static
    {
        return $this->with('summary', $summary);
    }

    public function description(?string $description): static
    {
        return $this->with('description', $description);
    }

    public function externalDocs(?ExternalDocumentation $externalDocs): static
    {
        return $this->with('externalDocs', $externalDocs);
    }

    public function operationId(?string $operationId): static
    {
        return $this->with('operationId', $operationId);
    }

    public function parameters(ParameterContract ...$parameters): static
    {
        return $this->with('parameters', $parameters);
    }

    public function addParameters(ParameterContract ...$parameters): static
    {
        return $this->withAdded('parameters', $parameters);
    }

    public function requestBody(?RequestBodyContract $requestBody): static
    {
        return $this->with('requestBody', $requestBody);
    }

    /**
     * Keyed by the objectId of each entry.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function callbacks(CallbackContract ...$callbacks): static
    {
        return $this->with('callbacks', $callbacks);
    }

    /**
     * Appends entries keyed by their objectId; an objectId already present throws.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function addCallbacks(CallbackContract ...$callbacks): static
    {
        return $this->withAdded('callbacks', $callbacks);
    }

    public function deprecated(?bool $deprecated = true): static
    {
        return $this->with('deprecated', $deprecated);
    }

    /**
     * An empty call writes `security: []`, which removes the document-level requirements.
     */
    public function security(SecurityRequirement ...$security): static
    {
        return $this->with('security', $security);
    }

    public function addSecurity(SecurityRequirement ...$security): static
    {
        return $this->withAdded('security', $security);
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
     * Sets the responses: either one Responses object, or responses keyed by their objectId (the status code), wrapped
     * in a new Responses. Unlike the other setters it does not map one-to-one to `with()`, because of this wrapping.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function responses(Responses|ResponseContract ...$responses): static
    {
        return clone $this;
    }

    /**
     * Appends responses keyed by their objectId (the status code).
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function addResponses(ResponseContract ...$responses): static
    {
        return clone $this;
    }
}
