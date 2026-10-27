<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Objects;

use Ark4ne\OpenApiWriter\Exceptions\DuplicateObjectIdException;
use Ark4ne\OpenApiWriter\Metadata\Field;
use Ark4ne\OpenApiWriter\Support\Undefined;

/**
 * Security schemes required together. An empty requirement (`{}`) makes security optional.
 *
 * @see https://spec.openapis.org/oas/v3.2.0.html#security-requirement-object
 *
 * @property-read array<string, list<string>>|null $schemes
 */
final class SecurityRequirement extends SpecObject
{
    /** @var array<string, list<string>>|Undefined */
    #[Field(map: true, keyedByObjectId: false, patterned: true)] protected array|Undefined $schemes = Undefined::Value;

    /**
     * Required scopes (or roles), keyed by security scheme name.
     *
     * @param array<string, list<string>> $schemes
     */
    public function schemes(array $schemes): static
    {
        return $this->with('schemes', $schemes);
    }

    /**
     * Appends entries; a key already present throws.
     *
     * @param array<string, list<string>> $schemes
     *
     * @throws DuplicateObjectIdException
     */
    public function addSchemes(array $schemes): static
    {
        return $this->withAdded('schemes', $schemes);
    }
}
