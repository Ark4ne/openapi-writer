<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Support;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Objects;
use JsonException;
use RuntimeException;
use stdClass;

/**
 * Expected outputs, stored as JSON files in `tests/fixtures/<line>/` (`3.0`, `3.1`, `3.2`).
 *
 * A fixture is written for the latest patch version of its line; a document fixture is compared to another version
 * of the same line after its `openapi` field is set to that version (within a line the output is otherwise
 * identical). With `OPENAPI_WRITER_UPDATE_FIXTURES=1` (`composer fixtures:update`), the helpers rewrite the
 * fixtures from the actual output instead of failing; every rewrite goes through review.
 */
final class Fixtures
{
    public const DIRECTORY = __DIR__ . '/../fixtures/';

    private const OPENAPI_FIELD = '/^    "openapi": "[^"]*"/m';

    /**
     * `3.0`, `3.1` or `3.2`, computed here so that the helpers do not depend on the code under test.
     */
    public static function line(Version $version): string
    {
        return implode('.', array_slice(explode('.', $version->value), 0, 2));
    }

    /**
     * The latest patch version of the line of $version: the version the fixtures are written for.
     */
    public static function latest(Version $version): Version
    {
        $latest = $version;

        foreach (Version::cases() as $case) {
            if (self::line($case) === self::line($version) && version_compare($case->value, $latest->value, '>')) {
                $latest = $case;
            }
        }

        return $latest;
    }

    public static function path(Version $version, string $name): string
    {
        return self::DIRECTORY . self::line($version) . '/' . $name . '.json';
    }

    /**
     * The expected pretty-printed JSON, ending with a new line, for $version.
     */
    public static function expected(Version $version, string $name): string
    {
        $path = self::path($version, $name);

        if (!is_file($path)) {
            throw new RuntimeException(sprintf('Missing fixture %s.', $path));
        }

        return self::withOpenApiVersion((string) file_get_contents($path), $version);
    }

    /**
     * The fixture as `toArray()` writes it: JSON objects become arrays, empty ones `\stdClass`.
     *
     * @return array<string, mixed>
     */
    public static function document(Version $version, string $name): array
    {
        try {
            $document = self::toArray(json_decode(self::expected($version, $name), false, 512, JSON_THROW_ON_ERROR));
        } catch (JsonException $exception) {
            throw new RuntimeException(sprintf('Invalid fixture %s: %s', $name, $exception->getMessage()), 0, $exception);
        }

        if (!is_array($document)) {
            throw new RuntimeException(sprintf('The fixture %s is not a JSON object.', $name));
        }

        /** @var array<string, mixed> $document */
        return $document;
    }

    public static function updating(): bool
    {
        return getenv('OPENAPI_WRITER_UPDATE_FIXTURES') === '1';
    }

    /**
     * Rewrites a fixture from the actual output, for the latest version of the line. Output that is not valid against
     * the official schema (once placed in a minimal document) is never written, so a correct fixture is never
     * replaced by a broken output.
     *
     * @param class-string<Objects\SpecObject> $class class of the serialized object
     */
    public static function update(Version $version, string $name, string $actual, string $class): void
    {
        if (OfficialSchema::errors(Envelope::wrap($actual, $class, $version), $version) !== []) {
            return;
        }

        $path = self::path($version, $name);

        if (!is_dir(dirname($path))) {
            mkdir(dirname($path), 0o777, true);
        }

        file_put_contents($path, self::withOpenApiVersion($actual, self::latest($version)));
    }

    private static function withOpenApiVersion(string $json, Version $version): string
    {
        return (string) preg_replace(self::OPENAPI_FIELD, '    "openapi": "' . $version->value . '"', $json, 1);
    }

    private static function toArray(mixed $value): mixed
    {
        if ($value instanceof stdClass) {
            $properties = get_object_vars($value);

            return $properties === [] ? $value : array_map(self::toArray(...), $properties);
        }

        return is_array($value) ? array_map(self::toArray(...), $value) : $value;
    }
}
