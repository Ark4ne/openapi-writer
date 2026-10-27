<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Objects;

use Ark4ne\OpenApiWriter\Contracts\ParameterContract;
use Ark4ne\OpenApiWriter\Contracts\PathItemContract;
use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Exceptions\DuplicateObjectIdException;
use Ark4ne\OpenApiWriter\Exceptions\MissingObjectIdException;
use Ark4ne\OpenApiWriter\Metadata\Field;
use Ark4ne\OpenApiWriter\Support\Undefined;

/**
 * Operations available on one path; its objectId is the path (or the expression in a Callback).
 *
 * @see https://spec.openapis.org/oas/v3.2.0.html#path-item-object
 *
 * @property-read string|null $ref
 * @property-read string|null $summary
 * @property-read string|null $description
 * @property-read Operation|null $get
 * @property-read Operation|null $put
 * @property-read Operation|null $post
 * @property-read Operation|null $delete
 * @property-read Operation|null $options
 * @property-read Operation|null $head
 * @property-read Operation|null $patch
 * @property-read Operation|null $trace
 * @property-read Operation|null $query
 * @property-read array<string, Operation>|null $additionalOperations
 * @property-read list<Server>|null $servers
 * @property-read list<ParameterContract>|null $parameters
 */
final class PathItem extends ExtensibleSpecObject implements PathItemContract
{
    #[Field(name: '$ref')] protected string|Undefined $ref = Undefined::Value;
    #[Field] protected string|Undefined $summary = Undefined::Value;
    #[Field] protected string|Undefined $description = Undefined::Value;
    #[Field] protected Operation|Undefined $get = Undefined::Value;
    #[Field] protected Operation|Undefined $put = Undefined::Value;
    #[Field] protected Operation|Undefined $post = Undefined::Value;
    #[Field] protected Operation|Undefined $delete = Undefined::Value;
    #[Field] protected Operation|Undefined $options = Undefined::Value;
    #[Field] protected Operation|Undefined $head = Undefined::Value;
    #[Field] protected Operation|Undefined $patch = Undefined::Value;
    #[Field] protected Operation|Undefined $trace = Undefined::Value;
    #[Field(since: Version::V3_2_0)] protected Operation|Undefined $query = Undefined::Value;
    /** @var array<string, Operation>|Undefined */
    #[Field(since: Version::V3_2_0, map: true)] protected array|Undefined $additionalOperations = Undefined::Value;
    /** @var list<Server>|Undefined */
    #[Field] protected array|Undefined $servers = Undefined::Value;
    /** @var list<ParameterContract>|Undefined */
    #[Field] protected array|Undefined $parameters = Undefined::Value;

    /**
     * Sets `$ref`, a reference to a Path Item Object defined elsewhere.
     */
    public function ref(?string $ref): static
    {
        return $this->with('ref', $ref);
    }

    public function summary(?string $summary): static
    {
        return $this->with('summary', $summary);
    }

    public function description(?string $description): static
    {
        return $this->with('description', $description);
    }

    public function get(?Operation $get): static
    {
        return $this->with('get', $get);
    }

    public function put(?Operation $put): static
    {
        return $this->with('put', $put);
    }

    public function post(?Operation $post): static
    {
        return $this->with('post', $post);
    }

    public function delete(?Operation $delete): static
    {
        return $this->with('delete', $delete);
    }

    public function options(?Operation $options): static
    {
        return $this->with('options', $options);
    }

    public function head(?Operation $head): static
    {
        return $this->with('head', $head);
    }

    public function patch(?Operation $patch): static
    {
        return $this->with('patch', $patch);
    }

    public function trace(?Operation $trace): static
    {
        return $this->with('trace', $trace);
    }

    public function query(?Operation $query): static
    {
        return $this->with('query', $query);
    }

    /**
     * Operations of other HTTP methods, keyed by their objectId (the method name, e.g. `COPY`).
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function additionalOperations(Operation ...$additionalOperations): static
    {
        return $this->with('additionalOperations', $additionalOperations);
    }

    /**
     * Appends entries keyed by their objectId; an objectId already present throws.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function addAdditionalOperations(Operation ...$additionalOperations): static
    {
        return $this->withAdded('additionalOperations', $additionalOperations);
    }

    public function servers(Server ...$servers): static
    {
        return $this->with('servers', $servers);
    }

    public function addServers(Server ...$servers): static
    {
        return $this->withAdded('servers', $servers);
    }

    public function parameters(ParameterContract ...$parameters): static
    {
        return $this->with('parameters', $parameters);
    }

    public function addParameters(ParameterContract ...$parameters): static
    {
        return $this->withAdded('parameters', $parameters);
    }

    /**
     * Places each operation under the field named by its objectId (`Operation::get()` → `get`); an operation whose
     * objectId is not a fixed field goes to `additionalOperations`.
     *
     * @throws MissingObjectIdException
     */
    public function operations(Operation ...$operations): static
    {
        return clone $this;
    }
}
