<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Objects;

use Ark4ne\OpenApiWriter\Contracts\MediaTypeContract;
use Ark4ne\OpenApiWriter\Contracts\RequestBodyContract;
use Ark4ne\OpenApiWriter\Exceptions\DuplicateObjectIdException;
use Ark4ne\OpenApiWriter\Exceptions\MissingObjectIdException;
use Ark4ne\OpenApiWriter\Metadata\Field;
use Ark4ne\OpenApiWriter\Support\Undefined;

/**
 * The body of a request.
 *
 * @see https://spec.openapis.org/oas/v3.2.0.html#request-body-object
 *
 * @property-read string|null $description
 * @property-read array<string, MediaTypeContract>|null $content
 * @property-read bool|null $required
 */
final class RequestBody extends ExtensibleSpecObject implements RequestBodyContract
{
    #[Field] protected string|Undefined $description = Undefined::Value;
    /** @var array<string, MediaTypeContract>|Undefined */
    #[Field(map: true)] protected array|Undefined $content = Undefined::Value;
    #[Field] protected bool|Undefined $required = Undefined::Value;

    /**
     * Reference to `#/components/requestBodies/<name>`. The path is built here, since a Reference does not know its
     * section; a value containing `#` or `/` is written as is.
     *
     * @param string|null $objectId key of the reference in its parent
     */
    public static function ref(string $ref, ?string $objectId = null): Reference
    {
        return Reference::to(self::componentPath('requestBodies', $ref), $objectId);
    }

    public function description(?string $description): static
    {
        return $this->with('description', $description);
    }

    /**
     * Keyed by the objectId of each entry.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function content(MediaTypeContract ...$content): static
    {
        return $this->with('content', $content);
    }

    /**
     * Appends entries keyed by their objectId; an objectId already present throws.
     *
     * @throws MissingObjectIdException
     * @throws DuplicateObjectIdException
     */
    public function addContent(MediaTypeContract ...$content): static
    {
        return $this->withAdded('content', $content);
    }

    public function required(?bool $required = true): static
    {
        return $this->with('required', $required);
    }
}
