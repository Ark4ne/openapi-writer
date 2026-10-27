<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Objects;

use Ark4ne\OpenApiWriter\Metadata\Field;
use Ark4ne\OpenApiWriter\Support\Undefined;

/**
 * A variable of a server URL template; its objectId is the variable name.
 *
 * @see https://spec.openapis.org/oas/v3.2.0.html#server-variable-object
 *
 * @property-read list<string>|null $enum
 * @property-read string|null $default
 * @property-read string|null $description
 */
final class ServerVariable extends ExtensibleSpecObject
{
    /** @var list<string>|Undefined */
    #[Field] protected array|Undefined $enum = Undefined::Value;
    #[Field] protected string|Undefined $default = Undefined::Value;
    #[Field] protected string|Undefined $description = Undefined::Value;

    public function enum(string ...$enum): static
    {
        return $this->with('enum', $enum);
    }

    public function addEnum(string ...$enum): static
    {
        return $this->withAdded('enum', $enum);
    }

    public function default(?string $default): static
    {
        return $this->with('default', $default);
    }

    public function description(?string $description): static
    {
        return $this->with('description', $description);
    }
}
