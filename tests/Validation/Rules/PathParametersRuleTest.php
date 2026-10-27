<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Validation\Rules;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Tests\Support\Fixtures;
use Ark4ne\OpenApiWriter\Tests\Validation\RuleTestCase;
use Ark4ne\OpenApiWriter\Validation\Rule;
use Ark4ne\OpenApiWriter\Validation\Rules\PathParametersRule;
use Ark4ne\OpenApiWriter\Validation\Severity;
use Ark4ne\OpenApiWriter\Validation\Violations;

final class PathParametersRuleTest extends RuleTestCase
{
    protected static function rule(): Rule
    {
        return new PathParametersRule();
    }

    public function test_variables_declared_on_the_path_item_or_on_each_operation_are_valid(): void
    {
        self::assertSame([], self::check('path-parameters-valid', Version::V3_1_2));
    }

    public function test_an_operation_without_a_path_parameter_for_a_variable_is_reported(): void
    {
        self::assertSame([
            ['/paths/~1users~1{id}/get', Severity::Error],
            ['/paths/~1teams~1{team}/get', Severity::Error],
        ], self::check('path-parameters-invalid', Version::V3_1_2));
    }

    /**
     * A path item written as a `$ref` is reported at the reference. Operations and parameters written next to the
     * `$ref` count with those of its target.
     */
    public function test_references_to_parameters_and_path_items_are_followed(): void
    {
        self::assertSame([
            ['/paths/~1teams~1{team}', Severity::Error],
            ['/paths/~1apps~1{app}/get', Severity::Error],
            ['/paths/~1apps~1{app}', Severity::Error],
        ], self::check('path-parameters-references', Version::V3_1_2));
    }

    /**
     * An operation of the target is reported at the reference, but the message names it where it is written.
     */
    public function test_the_message_names_an_operation_of_the_target_where_it_is_written(): void
    {
        $violations = new Violations();

        self::rule()->check(Fixtures::document(Version::V3_1_2, 'validation/path-parameters-references'), Version::V3_1_2, $violations);

        self::assertSame([
            '/components/pathItems/Team/get has no required in: path parameter for {team}',
            '/paths/~1apps~1{app}/get has no required in: path parameter for {app}',
            '/components/pathItems/Plain/delete has no required in: path parameter for {app}',
        ], array_map(static fn($violation): string => $violation->message, $violations->all()));
    }
}
