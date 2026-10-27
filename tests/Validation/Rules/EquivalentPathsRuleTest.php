<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Validation\Rules;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Tests\Validation\RuleTestCase;
use Ark4ne\OpenApiWriter\Validation\Rule;
use Ark4ne\OpenApiWriter\Validation\Rules\EquivalentPathsRule;
use Ark4ne\OpenApiWriter\Validation\Severity;

final class EquivalentPathsRuleTest extends RuleTestCase
{
    protected static function rule(): Rule
    {
        return new EquivalentPathsRule();
    }

    public function test_a_template_next_to_a_concrete_path_is_valid(): void
    {
        self::assertSame([], self::check('equivalent-paths-valid', Version::V3_1_2));
    }

    public function test_two_templates_that_differ_only_by_variable_names_are_reported(): void
    {
        self::assertSame([['/paths/~1users~1{uuid}', Severity::Error]], self::check('equivalent-paths-invalid', Version::V3_1_2));
    }
}
