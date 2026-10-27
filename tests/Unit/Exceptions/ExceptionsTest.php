<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit\Exceptions;

use Ark4ne\OpenApiWriter\Enums\ParameterLocation;
use Ark4ne\OpenApiWriter\Exceptions\DuplicateObjectIdException;
use Ark4ne\OpenApiWriter\Exceptions\InvalidArgumentException;
use Ark4ne\OpenApiWriter\Exceptions\MissingDependencyException;
use Ark4ne\OpenApiWriter\Exceptions\MissingObjectIdException;
use Ark4ne\OpenApiWriter\Exceptions\OpenApiWriterException;
use Ark4ne\OpenApiWriter\Exceptions\UnsupportedFeatureException;
use Ark4ne\OpenApiWriter\Objects\Components;
use Ark4ne\OpenApiWriter\Objects\License;
use Ark4ne\OpenApiWriter\Objects\Parameter;
use Ark4ne\OpenApiWriter\Objects\Schema;
use Ark4ne\OpenApiWriter\Serialization\Diagnostic;
use Ark4ne\OpenApiWriter\Serialization\DiagnosticLevel;
use Ark4ne\OpenApiWriter\Serialization\Diagnostics;
use Ark4ne\OpenApiWriter\Support\JsonPointer;
use Ark4ne\OpenApiWriter\Tests\TestCase;
use LogicException;
use RuntimeException;

final class ExceptionsTest extends TestCase
{
    public function test_for_field_names_the_target_first(): void
    {
        $exception = InvalidArgumentException::forField(License::class, 'identifier', 'url and identifier are mutually exclusive');

        self::assertSame('License::identifier: url and identifier are mutually exclusive', $exception->getMessage());
        self::assertContains(\InvalidArgumentException::class, class_parents($exception));
        self::assertContains(OpenApiWriterException::class, class_implements($exception));
    }

    public function test_for_enum_value_lists_the_accepted_values(): void
    {
        $exception = InvalidArgumentException::forEnumValue(Parameter::class, 'in', ParameterLocation::class, 'body');

        self::assertSame('Parameter::in: "body" is not one of query, header, path, cookie, querystring', $exception->getMessage());
    }

    public function test_a_duplicate_object_id_names_the_map_and_the_key(): void
    {
        $exception = DuplicateObjectIdException::forField(Components::class, 'schemas', 'User');

        self::assertSame('Components::schemas: an entry with the objectId "User" already exists', $exception->getMessage());
        self::assertContains(\InvalidArgumentException::class, class_parents($exception));
        self::assertContains(OpenApiWriterException::class, class_implements($exception));
    }

    public function test_a_missing_object_id_names_the_map(): void
    {
        $exception = MissingObjectIdException::forField(Components::class, 'schemas');

        self::assertSame('Components::schemas: an entry has no objectId', $exception->getMessage());
        self::assertContains(\InvalidArgumentException::class, class_parents($exception));
        self::assertContains(OpenApiWriterException::class, class_implements($exception));
    }

    public function test_a_reference_to_an_object_without_object_id_names_its_class(): void
    {
        $exception = MissingObjectIdException::forReference(Schema::class);

        self::assertSame('Reference::for: the Schema has no objectId', $exception->getMessage());
    }

    public function test_an_unsupported_feature_exception_lists_every_diagnostic(): void
    {
        $diagnostics = new Diagnostics();
        $diagnostics->add(new Diagnostic(DiagnosticLevel::Unsupported, JsonPointer::fromString('/webhooks'), 'webhooks needs 3.1.0'));
        $diagnostics->add(new Diagnostic(DiagnosticLevel::Unsupported, JsonPointer::fromString('/info/summary'), 'summary needs 3.1.0'));

        $exception = UnsupportedFeatureException::fromDiagnostics($diagnostics);

        self::assertSame(
            "2 features cannot be written for this version:\n- /webhooks: webhooks needs 3.1.0\n- /info/summary: summary needs 3.1.0",
            $exception->getMessage(),
        );
        self::assertSame($diagnostics, $exception->diagnostics());
        self::assertContains(RuntimeException::class, class_parents($exception));
        self::assertContains(OpenApiWriterException::class, class_implements($exception));
    }

    public function test_a_missing_dependency_tells_how_to_install_it(): void
    {
        $exception = MissingDependencyException::forPackage('symfony/yaml', 'write YAML');

        self::assertSame('symfony/yaml is required to write YAML: composer require symfony/yaml', $exception->getMessage());
        self::assertContains(LogicException::class, class_parents($exception));
        self::assertContains(OpenApiWriterException::class, class_implements($exception));
    }
}
