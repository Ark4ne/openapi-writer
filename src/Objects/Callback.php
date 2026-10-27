<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Objects;

use Ark4ne\OpenApiWriter\Contracts\CallbackContract;
use Ark4ne\OpenApiWriter\Contracts\PathItemContract;
use Ark4ne\OpenApiWriter\Exceptions\DuplicateObjectIdException;
use Ark4ne\OpenApiWriter\Exceptions\MissingObjectIdException;
use Ark4ne\OpenApiWriter\Metadata\Field;
use Ark4ne\OpenApiWriter\Support\Undefined;

/**
 * Out-of-band requests, keyed by runtime expression; its objectId is the callback name.
 *
 * @see https://spec.openapis.org/oas/v3.2.0.html#callback-object
 *
 * @property-read array<string, PathItemContract>|null $pathItems
 */
final class Callback extends ExtensibleSpecObject implements CallbackContract
{
    /** @var array<string, PathItemContract>|Undefined */
    #[Field(map: true, patterned: true)] protected array|Undefined $pathItems = Undefined::Value;

    /**
     * Reference to `#/components/callbacks/<name>`. The path is built here, since a Reference does not know its
     * section; a value containing `#` or `/` is written as is.
     *
     * @param string|null $objectId key of the reference in its parent
     */
    public static function ref(string $ref, ?string $objectId = null): Reference
    {
        return Reference::create($objectId);
    }

    /**
     * Keyed by the objectId of each path item: the runtime expression.
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
}
