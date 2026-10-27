<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Objects;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Exceptions\DuplicateObjectIdException;
use Ark4ne\OpenApiWriter\Exceptions\MissingObjectIdException;
use Ark4ne\OpenApiWriter\Metadata\Field;
use Ark4ne\OpenApiWriter\Support\Undefined;

/**
 * A server hosting the API.
 *
 * @see https://spec.openapis.org/oas/v3.2.0.html#server-object
 *
 * @property-read string|null $url
 * @property-read string|null $description
 * @property-read string|null $name
 * @property-read array<string, ServerVariable>|null $variables
 */
final class Server extends ExtensibleSpecObject
{
    #[Field] protected string|Undefined $url = Undefined::Value;
    #[Field] protected string|Undefined $description = Undefined::Value;
    #[Field(since: Version::V3_2_0)] protected string|Undefined $name = Undefined::Value;
    /** @var array<string, ServerVariable>|Undefined */
    #[Field(map: true)] protected array|Undefined $variables = Undefined::Value;

    public function url(?string $url): static
    {
        return $this->with('url', $url);
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
     * Keyed by the objectId of each entry.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function variables(ServerVariable ...$variables): static
    {
        return $this->with('variables', $variables);
    }

    /**
     * Appends entries keyed by their objectId; an objectId already present throws.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function addVariables(ServerVariable ...$variables): static
    {
        return $this->withAdded('variables', $variables);
    }
}
