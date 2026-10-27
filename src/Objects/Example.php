<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Objects;

use Ark4ne\OpenApiWriter\Contracts\ExampleContract;
use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Metadata\Field;
use Ark4ne\OpenApiWriter\Support\Undefined;

/**
 * An example value. `value` and `externalValue` are mutually exclusive.
 *
 * @see https://spec.openapis.org/oas/v3.2.0.html#example-object
 *
 * @property-read string|null $summary
 * @property-read string|null $description
 * @property-read mixed $dataValue
 * @property-read string|null $serializedValue
 * @property-read mixed $value
 * @property-read string|null $externalValue
 */
final class Example extends ExtensibleSpecObject implements ExampleContract
{
    #[Field] protected string|Undefined $summary = Undefined::Value;
    #[Field] protected string|Undefined $description = Undefined::Value;
    #[Field(since: Version::V3_2_0)] protected mixed $dataValue = Undefined::Value;
    #[Field(since: Version::V3_2_0)] protected string|Undefined $serializedValue = Undefined::Value;
    #[Field] protected mixed $value = Undefined::Value;
    #[Field] protected string|Undefined $externalValue = Undefined::Value;

    /**
     * Reference to `#/components/examples/<name>`. The path is built here, since a Reference does not know its
     * section; a value containing `#` or `/` is written as is.
     *
     * @param string|null $objectId key of the reference in its parent
     */
    public static function ref(string $ref, ?string $objectId = null): Reference
    {
        return Reference::to(self::componentPath('examples', $ref), $objectId);
    }

    public function summary(?string $summary): static
    {
        return $this->with('summary', $summary);
    }

    public function description(?string $description): static
    {
        return $this->with('description', $description);
    }

    /**
     * `null` is written as a value; `without('dataValue')` removes the field.
     */
    public function dataValue(mixed $dataValue): static
    {
        return $this->with('dataValue', $dataValue);
    }

    public function serializedValue(?string $serializedValue): static
    {
        return $this->with('serializedValue', $serializedValue);
    }

    /**
     * `null` is written as a value; `without('value')` removes the field.
     */
    public function value(mixed $value): static
    {
        $this->exclusiveOf('value', 'externalValue');

        return $this->with('value', $value);
    }

    public function externalValue(?string $externalValue): static
    {
        if ($externalValue !== null) {
            $this->exclusiveOf('externalValue', 'value');
        }

        return $this->with('externalValue', $externalValue);
    }
}
