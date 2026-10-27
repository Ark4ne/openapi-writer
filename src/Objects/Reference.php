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
use Ark4ne\OpenApiWriter\Exceptions\InvalidArgumentException;
use Ark4ne\OpenApiWriter\Exceptions\MissingObjectIdException;
use Ark4ne\OpenApiWriter\Metadata\Field;
use Ark4ne\OpenApiWriter\Support\JsonPointer;
use Ark4ne\OpenApiWriter\Support\Undefined;

/**
 * A reference to another object, usable wherever the specification accepts a Reference Object.
 *
 * @see https://spec.openapis.org/oas/v3.2.0.html#reference-object
 *
 * @property-read string|null $ref
 * @property-read string|null $summary
 * @property-read string|null $description
 */
final class Reference extends SpecObject implements CallbackContract, ExampleContract, HeaderContract, LinkContract, MediaTypeContract, ParameterContract, PathItemContract, RequestBodyContract, ResponseContract, SchemaContract, SecuritySchemeContract
{
    #[Field(name: '$ref')] protected string|Undefined $ref = Undefined::Value;
    #[Field(since: Version::V3_1_0)] protected string|Undefined $summary = Undefined::Value;
    #[Field(since: Version::V3_1_0)] protected string|Undefined $description = Undefined::Value;

    /**
     * Raw reference, written as is: `Reference::to('#/components/schemas/User')`.
     *
     * @param string|null $objectId key of the reference in its parent
     */
    public static function to(string $ref, ?string $objectId = null): static
    {
        return static::create($objectId)->ref($ref);
    }

    /**
     * Reference to a component, from its objectId and its section (`#/components/schemas/<objectId>`).
     *
     * @throws MissingObjectIdException when the object has no objectId
     * @throws InvalidArgumentException when the object is not a component
     */
    public static function for(SpecObject $object): static
    {
        $section = match ($object::class) {
            Schema::class => 'schemas',
            Response::class => 'responses',
            Parameter::class => 'parameters',
            Example::class => 'examples',
            RequestBody::class => 'requestBodies',
            Header::class => 'headers',
            SecurityScheme::class => 'securitySchemes',
            Link::class => 'links',
            Callback::class => 'callbacks',
            PathItem::class => 'pathItems',
            MediaType::class => 'mediaTypes',
            default => throw InvalidArgumentException::forField(self::class, 'for', sprintf('%s is not a component', $object::class)),
        };
        $objectId = $object->objectId ?? throw MissingObjectIdException::forReference($object::class);

        return static::to('#' . JsonPointer::root()->append('components', $section, $objectId)->toString());
    }

    /**
     * Sets `$ref`, written as is (local, file or URL).
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
}
