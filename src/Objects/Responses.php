<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Objects;

use Ark4ne\OpenApiWriter\Contracts\ResponseContract;
use Ark4ne\OpenApiWriter\Exceptions\DuplicateObjectIdException;
use Ark4ne\OpenApiWriter\Exceptions\MissingObjectIdException;
use Ark4ne\OpenApiWriter\Metadata\Field;
use Ark4ne\OpenApiWriter\Support\Undefined;

/**
 * Expected responses of an operation, keyed by status code.
 *
 * @see https://spec.openapis.org/oas/v3.2.0.html#responses-object
 *
 * @property-read ResponseContract|null $default
 * @property-read array<string, ResponseContract>|null $responses
 */
final class Responses extends ExtensibleSpecObject
{
    #[Field] protected ResponseContract|Undefined $default = Undefined::Value;
    /** @var array<string, ResponseContract>|Undefined */
    #[Field(map: true, patterned: true)] protected array|Undefined $responses = Undefined::Value;

    /**
     * Also set by a response whose objectId is `default` in `responses()`.
     */
    public function default(?ResponseContract $default): static
    {
        return $this->with('default', $default);
    }

    /**
     * Keyed by the objectId of each response: a status code (`200`) or range (`2XX`). A response whose objectId is `default` goes to the `default` field; it throws DuplicateObjectIdException when that field is already set.
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
}
