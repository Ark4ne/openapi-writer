<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Enums;

/**
 * JSON Schema primitive types. `null` is converted for 3.0, which has no such type.
 */
enum SchemaType: string
{
    case String = 'string';
    case Number = 'number';
    case Integer = 'integer';
    case Boolean = 'boolean';
    case Array = 'array';
    case Object = 'object';
    case Null = 'null';
}
