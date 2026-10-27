<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Objects;

use Ark4ne\OpenApiWriter\Contracts\LinkContract;
use Ark4ne\OpenApiWriter\Exceptions\DuplicateObjectIdException;
use Ark4ne\OpenApiWriter\Metadata\Field;
use Ark4ne\OpenApiWriter\Support\Undefined;

/**
 * A design-time link from a response to an operation. `operationRef` and `operationId` are mutually exclusive.
 *
 * @see https://spec.openapis.org/oas/v3.2.0.html#link-object
 *
 * @property-read string|null $operationRef
 * @property-read string|null $operationId
 * @property-read array<string, mixed>|null $parameters
 * @property-read mixed $requestBody
 * @property-read string|null $description
 * @property-read Server|null $server
 */
final class Link extends ExtensibleSpecObject implements LinkContract
{
    #[Field] protected string|Undefined $operationRef = Undefined::Value;
    #[Field] protected string|Undefined $operationId = Undefined::Value;
    /** @var array<string, mixed>|Undefined */
    #[Field(map: true, keyedByObjectId: false)] protected array|Undefined $parameters = Undefined::Value;
    #[Field] protected mixed $requestBody = Undefined::Value;
    #[Field] protected string|Undefined $description = Undefined::Value;
    #[Field] protected Server|Undefined $server = Undefined::Value;

    /**
     * Reference to `#/components/links/<name>`. The path is built here, since a Reference does not know its
     * section; a value containing `#` or `/` is written as is.
     *
     * @param string|null $objectId key of the reference in its parent
     */
    public static function ref(string $ref, ?string $objectId = null): Reference
    {
        return Reference::create($objectId);
    }

    public function operationRef(?string $operationRef): static
    {
        return $this->with('operationRef', $operationRef);
    }

    public function operationId(?string $operationId): static
    {
        return $this->with('operationId', $operationId);
    }

    /**
     * Values or runtime expressions, keyed by parameter name.
     *
     * @param array<string, mixed> $parameters
     */
    public function parameters(array $parameters): static
    {
        return $this->with('parameters', $parameters);
    }

    /**
     * Appends entries; a key already present throws.
     *
     * @param array<string, mixed> $parameters
     *
     * @throws DuplicateObjectIdException
     */
    public function addParameters(array $parameters): static
    {
        return $this->withAdded('parameters', $parameters);
    }

    /**
     * `null` is written as a value; `without('requestBody')` removes the field.
     */
    public function requestBody(mixed $requestBody): static
    {
        return $this->with('requestBody', $requestBody);
    }

    public function description(?string $description): static
    {
        return $this->with('description', $description);
    }

    public function server(?Server $server): static
    {
        return $this->with('server', $server);
    }
}
