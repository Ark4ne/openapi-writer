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

    /**
     * A `$ref` inside a schema with an `$id` resolves in that schema. Free values are not read: `example`,
     * extensions of Responses, request body of a Link; a component named like a map field (`content`)
     * is still an object.
     */
    public function test_operation_refs_mappings_and_refs_of_schema_resources_are_checked(): void
    {
        self::assertSame([
            ['/paths/~1users/get/responses/200/links/broken/operationRef', Severity::Error],
            ['/components/schemas/Tree/properties/missing', Severity::Error],
            ['/components/schemas/Pet/discriminator/mapping/cat', Severity::Error],
            ['/components/schemas/Sample/properties/default', Severity::Error],
        ], self::check('local-references-nested', Version::V3_1_2));
    }
}
