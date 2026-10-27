<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Objects;

use Ark4ne\OpenApiWriter\Contracts\CallbackContract;
use Ark4ne\OpenApiWriter\Contracts\ExampleContract;
use Ark4ne\OpenApiWriter\Contracts\HeaderContract;
use Ark4ne\OpenApiWriter\Contracts\LinkContract;
use Ark4ne\OpenApiWriter\Contracts\MediaTypeContract;
use Ark4ne\OpenApiWriter\Contracts\ParameterContract;
use Ark4ne\OpenApiWriter\Contracts\PathItemContract;
use Ark4ne\OpenApiWriter\Contracts\RequestBodyContract;
use Ark4ne\OpenApiWriter\Contracts\ResponseContract;
use Ark4ne\OpenApiWriter\Contracts\SchemaContract;
use Ark4ne\OpenApiWriter\Contracts\SecuritySchemeContract;
use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Exceptions\DuplicateObjectIdException;
use Ark4ne\OpenApiWriter\Exceptions\MissingObjectIdException;
use Ark4ne\OpenApiWriter\Metadata\Field;
use Ark4ne\OpenApiWriter\Support\Undefined;

/**
 * Reusable objects, each section keyed by the objectId of its entries.
 *
 * @see https://spec.openapis.org/oas/v3.2.0.html#components-object
 *
 * @property-read array<string, SchemaContract>|null $schemas
 * @property-read array<string, ResponseContract>|null $responses
 * @property-read array<string, ParameterContract>|null $parameters
 * @property-read array<string, ExampleContract>|null $examples
 * @property-read array<string, RequestBodyContract>|null $requestBodies
 * @property-read array<string, HeaderContract>|null $headers
 * @property-read array<string, SecuritySchemeContract>|null $securitySchemes
 * @property-read array<string, LinkContract>|null $links
 * @property-read array<string, CallbackContract>|null $callbacks
 * @property-read array<string, PathItemContract>|null $pathItems
 * @property-read array<string, MediaTypeContract>|null $mediaTypes
 */
final class Components extends ExtensibleSpecObject
{
    /** @var array<string, SchemaContract>|Undefined */
    #[Field(map: true)] protected array|Undefined $schemas = Undefined::Value;
    /** @var array<string, ResponseContract>|Undefined */
    #[Field(map: true)] protected array|Undefined $responses = Undefined::Value;
    /** @var array<string, ParameterContract>|Undefined */
    #[Field(map: true)] protected array|Undefined $parameters = Undefined::Value;
    /** @var array<string, ExampleContract>|Undefined */
    #[Field(map: true)] protected array|Undefined $examples = Undefined::Value;
    /** @var array<string, RequestBodyContract>|Undefined */
    #[Field(map: true)] protected array|Undefined $requestBodies = Undefined::Value;
    /** @var array<string, HeaderContract>|Undefined */
    #[Field(map: true)] protected array|Undefined $headers = Undefined::Value;
    /** @var array<string, SecuritySchemeContract>|Undefined */
    #[Field(map: true)] protected array|Undefined $securitySchemes = Undefined::Value;
    /** @var array<string, LinkContract>|Undefined */
    #[Field(map: true)] protected array|Undefined $links = Undefined::Value;
    /** @var array<string, CallbackContract>|Undefined */
    #[Field(map: true)] protected array|Undefined $callbacks = Undefined::Value;
    /** @var array<string, PathItemContract>|Undefined */
    #[Field(since: Version::V3_1_0, map: true)] protected array|Undefined $pathItems = Undefined::Value;
    /** @var array<string, MediaTypeContract>|Undefined */
    #[Field(since: Version::V3_2_0, map: true)] protected array|Undefined $mediaTypes = Undefined::Value;

    /**
     * Keyed by the objectId of each entry.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function schemas(SchemaContract ...$schemas): static
    {
        return $this->with('schemas', $schemas);
    }

    /**
     * Appends entries keyed by their objectId; an objectId already present throws.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function addSchemas(SchemaContract ...$schemas): static
    {
        return $this->withAdded('schemas', $schemas);
    }

    /**
     * Keyed by the objectId of each entry.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function responses(ResponseContract ...$responses): static
    {
        return $this->with('responses', $responses);
    }

    /**
     * Appends entries keyed by their objectId; an objectId already present throws.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function addResponses(ResponseContract ...$responses): static
    {
        return $this->withAdded('responses', $responses);
    }

    /**
     * Keyed by the objectId of each entry.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function parameters(ParameterContract ...$parameters): static
    {
        return $this->with('parameters', $parameters);
    }

    /**
     * Appends entries keyed by their objectId; an objectId already present throws.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function addParameters(ParameterContract ...$parameters): static
    {
        return $this->withAdded('parameters', $parameters);
    }

    /**
     * Keyed by the objectId of each entry.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function examples(ExampleContract ...$examples): static
    {
        return $this->with('examples', $examples);
    }

    /**
     * Appends entries keyed by their objectId; an objectId already present throws.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function addExamples(ExampleContract ...$examples): static
    {
        return $this->withAdded('examples', $examples);
    }

    /**
     * Keyed by the objectId of each entry.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function requestBodies(RequestBodyContract ...$requestBodies): static
    {
        return $this->with('requestBodies', $requestBodies);
    }

    /**
     * Appends entries keyed by their objectId; an objectId already present throws.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function addRequestBodies(RequestBodyContract ...$requestBodies): static
    {
        return $this->withAdded('requestBodies', $requestBodies);
    }

    /**
     * Keyed by the objectId of each entry.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function headers(HeaderContract ...$headers): static
    {
        return $this->with('headers', $headers);
    }

    /**
     * Appends entries keyed by their objectId; an objectId already present throws.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function addHeaders(HeaderContract ...$headers): static
    {
        return $this->withAdded('headers', $headers);
    }

    /**
     * Keyed by the objectId of each entry.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function securitySchemes(SecuritySchemeContract ...$securitySchemes): static
    {
        return $this->with('securitySchemes', $securitySchemes);
    }

    /**
     * Appends entries keyed by their objectId; an objectId already present throws.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function addSecuritySchemes(SecuritySchemeContract ...$securitySchemes): static
    {
        return $this->withAdded('securitySchemes', $securitySchemes);
    }

    /**
     * Keyed by the objectId of each entry.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function links(LinkContract ...$links): static
    {
        return $this->with('links', $links);
    }

    /**
     * Appends entries keyed by their objectId; an objectId already present throws.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function addLinks(LinkContract ...$links): static
    {
        return $this->withAdded('links', $links);
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

    /**
     * Keyed by the objectId of each entry.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function pathItems(PathItemContract ...$pathItems): static
    {
        return $this->with('pathItems', $pathItems);
    }

    /**
     * Appends entries keyed by their objectId; an objectId already present throws.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function addPathItems(PathItemContract ...$pathItems): static
    {
        return $this->withAdded('pathItems', $pathItems);
    }

    /**
     * Keyed by the objectId of each entry.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function mediaTypes(MediaTypeContract ...$mediaTypes): static
    {
        return $this->with('mediaTypes', $mediaTypes);
    }

    /**
     * Appends entries keyed by their objectId; an objectId already present throws.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function addMediaTypes(MediaTypeContract ...$mediaTypes): static
    {
        return $this->withAdded('mediaTypes', $mediaTypes);
    }
}
