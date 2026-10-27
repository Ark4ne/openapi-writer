<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Objects;

use Ark4ne\OpenApiWriter\Objects\Reference;

/**
 * The model of a Reference; its constructors and shortcuts are tested in `tests/Unit/References/`.
 */
final class ReferenceTest extends SpecObjectTestCase
{
    protected static function objectClass(): string
    {
        return Reference::class;
    }

    public static function fields(): array
    {
        return [
            'ref' => [['#/components/schemas/User'], '#/components/schemas/User'],
            'summary' => [['A user'], 'A user'],
            'description' => [['The user that owns the resource'], 'The user that owns the resource'],
        ];
    }

    protected static function extensible(): bool
    {
        return false;
    }
}
