<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Validation\Rules;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Tests\Validation\RuleTestCase;
use Ark4ne\OpenApiWriter\Validation\Rule;
use Ark4ne\OpenApiWriter\Validation\Rules\LocalReferencesRule;
use Ark4ne\OpenApiWriter\Validation\Severity;

final class LocalReferencesRuleTest extends RuleTestCase
{
    protected static function rule(): Rule
    {
        return new LocalReferencesRule();
    }

    public function test_local_references_and_operation_refs_that_resolve_are_valid(): void
    {
        self::assertSame([], self::check('local-references-valid', Version::V3_1_2));
    }

    public function test_a_local_reference_to_nothing_is_reported_and_an_external_one_is_ignored(): void
    {
        self::assertSame([
            ['/paths/~1users/get/responses/200/content/application~1json/schema', Severity::Error],
            ['/paths/~1users/get/responses/404', Severity::Error],
            ['/components/schemas/User/properties/team', Severity::Error],
        ], self::check('local-references-invalid', Version::V3_1_2));
    }
}
