<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Exceptions\UnsupportedFeatureException;
use Ark4ne\OpenApiWriter\Objects\OpenApi;
use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Serialization\Diagnostic;
use Ark4ne\OpenApiWriter\Serialization\DiagnosticLevel;
use Ark4ne\OpenApiWriter\Serialization\Diagnostics;
use Ark4ne\OpenApiWriter\Serialization\SerializationResult;
use Ark4ne\OpenApiWriter\Tests\Support\Envelope;
use Ark4ne\OpenApiWriter\Tests\Support\Fixtures;
use Ark4ne\OpenApiWriter\Tests\Support\OfficialSchema;
use Closure;
use PHPUnit\Framework\TestCase as PHPUnitTestCase;
use Throwable;

/**
 * Shared assertions. Every expected output is a fixture file, and every fixture is checked against the official
 * schema of its version before the comparison.
 */
abstract class TestCase extends PHPUnitTestCase
{
    /**
     * The object, written in strict mode as pretty-printed JSON for $version, is byte for byte the fixture.
     */
    protected static function assertSerializesTo(SpecObject $object, Version $version, string $fixture): void
    {
        self::assertJsonMatchesFixture($object->toJson($version, JSON_PRETTY_PRINT), $object::class, $version, $fixture);
    }

    /**
     * The result of a serialization (lenient mode included) is byte for byte the fixture.
     *
     * @param class-string<SpecObject> $class class of the serialized object
     */
    protected static function assertResultSerializesTo(SerializationResult $result, string $class, Version $version, string $fixture): void
    {
        self::assertJsonMatchesFixture($result->toJson(JSON_PRETTY_PRINT), $class, $version, $fixture);
    }

    /**
     * The document is valid against the official schema of $version.
     */
    protected static function assertValidOpenApi(string $json, Version $version): void
    {
        $errors = OfficialSchema::errors($json, $version);

        self::assertSame([], $errors, sprintf('The document is not a valid OpenAPI %s document.', $version->value));
    }

    /**
     * The JSON of one object is valid against the official schema of $version, once placed in a minimal document.
     *
     * @param class-string<SpecObject> $class
     */
    protected static function assertValidOpenApiObject(string $json, string $class, Version $version): void
    {
        self::assertValidOpenApi($class === OpenApi::class ? $json : Envelope::wrap($json, $class, $version), $version);
    }

    /**
     * The diagnostics are exactly these, in this order.
     *
     * @param list<array{DiagnosticLevel, string}> $expected level and JSON Pointer of each diagnostic
     */
    protected static function assertDiagnostics(array $expected, Diagnostics $diagnostics): void
    {
        $actual = array_map(
            static fn(Diagnostic $diagnostic): array => [$diagnostic->level, $diagnostic->pointer->toString()],
            $diagnostics->all(),
        );

        self::assertSame($expected, $actual);
    }

    /**
     * The serialization, in strict mode, throws one UnsupportedFeatureException that lists exactly these pointers.
     *
     * @param list<string> $pointers
     * @param Closure(): mixed $serialize
     */
    protected static function assertUnsupported(array $pointers, Closure $serialize): void
    {
        try {
            $serialize();
        } catch (UnsupportedFeatureException $exception) {
            self::assertDiagnostics(
                array_map(static fn(string $pointer): array => [DiagnosticLevel::Unsupported, $pointer], $pointers),
                $exception->diagnostics(),
            );

            return;
        }

        self::fail(sprintf('Expected an UnsupportedFeatureException for %s.', implode(', ', $pointers)));
    }

    /**
     * The action throws an exception of this class. Unlike `expectException()`, it can be used several times in a
     * test, and any other exception is an assertion failure rather than an error.
     *
     * @param class-string<Throwable> $exception
     * @param Closure(): mixed $action
     */
    protected static function assertThrows(string $exception, Closure $action, string $message = ''): void
    {
        try {
            $action();
        } catch (Throwable $thrown) {
            self::assertInstanceOf($exception, $thrown, trim($message . ' ' . $thrown->getMessage()));

            return;
        }

        self::fail(trim(sprintf('Expected %s. %s', $exception, $message)));
    }

    /**
     * The JSON, written with `JSON_PRETTY_PRINT`, is byte for byte the fixture, itself valid against the official
     * schema of $version.
     *
     * @param class-string<SpecObject> $class class of the serialized object
     */
    protected static function assertJsonMatchesFixture(string $actual, string $class, Version $version, string $fixture): void
    {
        if (Fixtures::updating()) {
            Fixtures::update($version, $fixture, $actual . "\n", $class);
        }

        $expected = Fixtures::expected($version, $fixture);

        self::assertValidOpenApiObject($expected, $class, $version);
        self::assertSame($expected, $actual . "\n", sprintf('The output differs from the fixture %s/%s.json.', Fixtures::line($version), $fixture));
    }
}
