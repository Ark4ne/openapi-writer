<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\References;

use Ark4ne\OpenApiWriter\Exceptions\DuplicateObjectIdException;
use Ark4ne\OpenApiWriter\Objects\Components;
use Ark4ne\OpenApiWriter\Objects\Reference;
use Ark4ne\OpenApiWriter\Objects\Response;
use Ark4ne\OpenApiWriter\Objects\Schema;
use Ark4ne\OpenApiWriter\Tests\TestCase;

final class ComponentsTest extends TestCase
{
    public function test_two_components_with_the_same_object_id_in_one_section_are_rejected(): void
    {
        $this->expectException(DuplicateObjectIdException::class);
        $this->expectExceptionMessage('Components::schemas');

        Components::create()->schemas(Schema::object('User'), Schema::object('User'));
    }

    public function test_a_reference_and_a_component_with_the_same_object_id_are_rejected(): void
    {
        $this->expectException(DuplicateObjectIdException::class);

        Components::create()->schemas(Schema::object('User'))->addSchemas(Reference::to('#/components/schemas/Person', 'User'));
    }

    public function test_a_duplicate_in_one_section_does_not_change_the_original_components(): void
    {
        $components = Components::create()->responses(Response::create('NotFound')->description('Not found'));

        try {
            $components->addResponses(Response::create('NotFound')->description('Missing'));
        } catch (DuplicateObjectIdException) {
        }

        $notFound = $components->responses['NotFound'] ?? null;
        self::assertInstanceOf(Response::class, $notFound);
        self::assertSame('Not found', $notFound->description);
    }

    public function test_a_component_is_referenced_by_its_object_id_and_section(): void
    {
        $user = Schema::object('User');

        $components = Components::create()->schemas($user);

        self::assertSame($user, $components->schemas['User'] ?? null);
        self::assertSame('#/components/schemas/User', Reference::for($user)->ref);
    }
}
