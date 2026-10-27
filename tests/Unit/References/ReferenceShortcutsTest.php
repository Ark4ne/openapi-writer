<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\References;

use Ark4ne\OpenApiWriter\Exceptions\InvalidArgumentException;
use Ark4ne\OpenApiWriter\Exceptions\MissingObjectIdException;
use Ark4ne\OpenApiWriter\Objects\Callback;
use Ark4ne\OpenApiWriter\Objects\Example;
use Ark4ne\OpenApiWriter\Objects\Header;
use Ark4ne\OpenApiWriter\Objects\Info;
use Ark4ne\OpenApiWriter\Objects\Link;
use Ark4ne\OpenApiWriter\Objects\MediaType;
use Ark4ne\OpenApiWriter\Objects\Parameter;
use Ark4ne\OpenApiWriter\Objects\PathItem;
use Ark4ne\OpenApiWriter\Objects\Reference;
use Ark4ne\OpenApiWriter\Objects\RequestBody;
use Ark4ne\OpenApiWriter\Objects\Response;
use Ark4ne\OpenApiWriter\Objects\Schema;
use Ark4ne\OpenApiWriter\Objects\SecurityScheme;
use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Tests\TestCase;
use Closure;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionMethod;

final class ReferenceShortcutsTest extends TestCase
{
    public function test_to_writes_the_reference_as_given(): void
    {
        self::assertSame('#/components/schemas/User', Reference::to('#/components/schemas/User')->ref);
        self::assertSame('https://example.com/schemas/user.json', Reference::to('https://example.com/schemas/user.json')->ref);
        self::assertSame('definitions.json#/User', Reference::to('definitions.json#/User')->ref);
    }

    public function test_to_takes_an_optional_object_id(): void
    {
        self::assertSame('owner', Reference::to('#/components/schemas/User', 'owner')->objectId);
        self::assertNull(Reference::to('#/components/schemas/User')->objectId);
    }

    /**
     * @return iterable<string, array{Closure(string, ?string): SpecObject, string}>
     */
    public static function shortcuts(): iterable
    {
        yield 'schema' => [Schema::ref(...), 'schemas'];
        yield 'response' => [Response::ref(...), 'responses'];
        yield 'parameter' => [Parameter::ref(...), 'parameters'];
        yield 'example' => [Example::ref(...), 'examples'];
        yield 'request body' => [RequestBody::ref(...), 'requestBodies'];
        yield 'header' => [Header::ref(...), 'headers'];
        yield 'security scheme' => [SecurityScheme::ref(...), 'securitySchemes'];
        yield 'link' => [Link::ref(...), 'links'];
        yield 'callback' => [Callback::ref(...), 'callbacks'];
        yield 'media type' => [MediaType::ref(...), 'mediaTypes'];
    }

    /**
     * @param Closure(string, ?string): SpecObject $ref
     */
    #[DataProvider('shortcuts')]
    public function test_each_shortcut_builds_the_path_of_its_component_section(Closure $ref, string $section): void
    {
        $reference = $ref('Item', 'key');

        self::assertSame('#/components/' . $section . '/Item', $reference->__get('ref'));
        self::assertSame('key', $reference->objectId);
    }

    /**
     * @param Closure(string, ?string): SpecObject $ref
     */
    #[DataProvider('shortcuts')]
    public function test_each_shortcut_writes_a_full_reference_as_given(Closure $ref, string $section): void
    {
        self::assertSame('#/components/' . $section . '/Other', $ref('#/components/' . $section . '/Other', null)->__get('ref'));
        self::assertSame('https://example.com/components.json#/Item', $ref('https://example.com/components.json#/Item', null)->__get('ref'));
    }

    public function test_shortcuts_other_than_schema_return_a_reference(): void
    {
        $method = static fn(string $class): string => (string) (new ReflectionMethod($class, 'ref'))->getReturnType();

        self::assertSame(Reference::class, $method(Response::class));
        self::assertSame(Reference::class, $method(MediaType::class));
        self::assertSame('static', $method(Schema::class));
    }

    /**
     * @return iterable<string, array{SpecObject, string}>
     */
    public static function components(): iterable
    {
        yield 'schema' => [Schema::object('User'), '#/components/schemas/User'];
        yield 'response' => [Response::create('NotFound'), '#/components/responses/NotFound'];
        yield 'parameter' => [Parameter::query('page', 'Page'), '#/components/parameters/Page'];
        yield 'example' => [Example::create('Alice'), '#/components/examples/Alice'];
        yield 'request body' => [RequestBody::create('UserBody'), '#/components/requestBodies/UserBody'];
        yield 'header' => [Header::create('RateLimit'), '#/components/headers/RateLimit'];
        yield 'security scheme' => [SecurityScheme::apiKey('ApiKey'), '#/components/securitySchemes/ApiKey'];
        yield 'link' => [Link::create('UserRepositories'), '#/components/links/UserRepositories'];
        yield 'callback' => [Callback::create('onEvent'), '#/components/callbacks/onEvent'];
        yield 'path item' => [PathItem::create('Ping'), '#/components/pathItems/Ping'];
        yield 'media type' => [MediaType::create('UserJson'), '#/components/mediaTypes/UserJson'];
        yield 'object id escaped' => [Schema::object('a/b~c'), '#/components/schemas/a~1b~0c'];
    }

    #[DataProvider('components')]
    public function test_for_points_to_the_section_of_the_object_under_its_object_id(SpecObject $object, string $ref): void
    {
        self::assertSame($ref, Reference::for($object)->ref);
    }

    public function test_for_rejects_an_object_without_object_id(): void
    {
        $this->expectException(MissingObjectIdException::class);

        Reference::for(Schema::object());
    }

    public function test_for_rejects_an_object_that_is_not_a_component(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Reference::for(Info::create('info'));
    }

    public function test_a_reference_can_be_built_before_its_component_exists(): void
    {
        $owner = Schema::object('Pet')->properties(Schema::ref('User', 'owner'));

        $property = $owner->properties['owner'] ?? null;
        self::assertInstanceOf(Schema::class, $property);
        self::assertSame('#/components/schemas/User', $property->ref);
    }

    public function test_summary_and_description_override_the_target_on_a_copy(): void
    {
        $reference = Reference::to('#/components/schemas/User');

        $overridden = $reference->summary('Owner')->description('The owner of the pet');

        self::assertSame('Owner', $overridden->summary);
        self::assertSame('The owner of the pet', $overridden->description);
        self::assertFalse($reference->has('summary'));
    }
}
