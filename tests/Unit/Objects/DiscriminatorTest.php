<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Objects;

use Ark4ne\OpenApiWriter\Objects\Discriminator;

final class DiscriminatorTest extends ExtensibleSpecObjectTestCase
{
    protected static function objectClass(): string
    {
        return Discriminator::class;
    }

    public static function fields(): array
    {
        $mapping = ['dog' => '#/components/schemas/Dog', 'cat' => '#/components/schemas/Cat'];

        return [
            'propertyName' => [['petType'], 'petType'],
            'mapping' => [[$mapping], $mapping],
            'defaultMapping' => [['#/components/schemas/Pet'], '#/components/schemas/Pet'],
        ];
    }

    public function test_add_mapping_appends_after_the_existing_entries(): void
    {
        $discriminator = Discriminator::create()->mapping(['dog' => '#/components/schemas/Dog']);

        $extended = $discriminator->addMapping(['cat' => '#/components/schemas/Cat']);

        self::assertSame(['dog' => '#/components/schemas/Dog', 'cat' => '#/components/schemas/Cat'], $extended->mapping);
    }
}
