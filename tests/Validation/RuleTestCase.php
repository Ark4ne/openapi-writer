<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Validation;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Tests\Support\Fixtures;
use Ark4ne\OpenApiWriter\Tests\TestCase;
use Ark4ne\OpenApiWriter\Validation\Rule;
use Ark4ne\OpenApiWriter\Validation\Severity;
use Ark4ne\OpenApiWriter\Validation\Violation;
use Ark4ne\OpenApiWriter\Validation\Violations;

/**
 * A rule checks the written document: its input is a fixture (`tests/fixtures/<line>/validation/<case>.json`),
 * valid against the official schema, as `toArray()` would write it.
 */
abstract class RuleTestCase extends TestCase
{
    abstract protected static function rule(): Rule;

    /**
     * The violations found in the fixture, as pointer and severity, after checking that every one of them names
     * the rule.
     *
     * @return list<array{string, Severity}>
     */
    protected static function check(string $fixture, Version $version): array
    {
        self::assertValidOpenApi(Fixtures::expected($version, 'validation/' . $fixture), $version);

        $violations = new Violations();
        static::rule()->check(Fixtures::document($version, 'validation/' . $fixture), $version, $violations);

        foreach ($violations as $violation) {
            self::assertSame(static::rule()::class, $violation->rule);
            self::assertNotSame('', $violation->message);
        }

        return array_map(
            static fn(Violation $violation): array => [$violation->pointer->toString(), $violation->severity],
            $violations->all(),
        );
    }
}
