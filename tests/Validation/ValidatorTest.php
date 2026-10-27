<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Validation;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Objects\Info;
use Ark4ne\OpenApiWriter\Objects\OpenApi;
use Ark4ne\OpenApiWriter\Objects\Operation;
use Ark4ne\OpenApiWriter\Objects\PathItem;
use Ark4ne\OpenApiWriter\Objects\Response;
use Ark4ne\OpenApiWriter\Serialization\Serializer;
use Ark4ne\OpenApiWriter\Tests\Support\Documents;
use Ark4ne\OpenApiWriter\Tests\Support\SummaryRequiredRule;
use Ark4ne\OpenApiWriter\Tests\TestCase;
use Ark4ne\OpenApiWriter\Validation\OpisJsonSchemaValidator;
use Ark4ne\OpenApiWriter\Validation\Rule;
use Ark4ne\OpenApiWriter\Validation\Rules;
use Ark4ne\OpenApiWriter\Validation\Severity;
use Ark4ne\OpenApiWriter\Validation\Validator;
use Ark4ne\OpenApiWriter\Validation\Violation;
use Ark4ne\OpenApiWriter\Validation\Violations;

/**
 * The Validator (Composite): writes the document for a version, runs the official schema when a JsonSchemaValidator
 * is given, then every rule. It never throws on an invalid document.
 */
final class ValidatorTest extends TestCase
{
    public function test_create_holds_every_built_in_rule(): void
    {
        self::assertSame([
            Rules\UniqueOperationIdRule::class,
            Rules\PathParametersRule::class,
            Rules\UniqueParametersRule::class,
            Rules\LocalReferencesRule::class,
            Rules\SecurityRequirementsRule::class,
            Rules\DeclaredTagsRule::class,
            Rules\TagParentsRule::class,
            Rules\EquivalentPathsRule::class,
            Rules\NonEmptyDocumentRule::class,
            Rules\ValueTypeRule::class,
        ], self::classes(Validator::create()));
        self::assertNull(Validator::create()->schemaValidator);
    }

    public function test_without_rules_disables_rules_by_class_name_on_a_copy(): void
    {
        $validator = Validator::create();

        $reduced = $validator->withoutRules(Rules\DeclaredTagsRule::class, Rules\ValueTypeRule::class);

        self::assertNotContains(Rules\DeclaredTagsRule::class, self::classes($reduced));
        self::assertNotContains(Rules\ValueTypeRule::class, self::classes($reduced));
        self::assertCount(8, $reduced->rules());
        self::assertCount(10, $validator->rules());
    }

    public function test_with_rules_appends_custom_rules_on_a_copy(): void
    {
        $custom = new SummaryRequiredRule();
        $validator = Validator::create();

        $extended = $validator->withRules($custom);

        self::assertSame($custom, $extended->rules()[10] ?? null);
        self::assertCount(10, $validator->rules());
    }

    public function test_a_valid_document_has_no_violation(): void
    {
        $violations = Validator::create(new OpisJsonSchemaValidator())->validate(Documents::tictactoe(), Version::V3_0_4);

        self::assertTrue($violations->isValid());
        self::assertSame([], $violations->all());
    }

    public function test_rules_run_on_the_document_written_for_the_version(): void
    {
        $document = self::documentWithDuplicateOperationIds();

        $violations = Validator::create()->validate($document, '3.1.2');

        self::assertSame(
            [['/paths/~1pets/get/operationId', Rules\UniqueOperationIdRule::class, Severity::Error]],
            self::summary($violations),
        );
    }

    public function test_warnings_do_not_make_a_document_invalid(): void
    {
        $document = OpenApi::create()
            ->info(Info::create()->title('Validation')->version('1.0.0'))
            ->paths(PathItem::create('/users')->get(Operation::get()->tags('undeclared')->responses(Response::ok())));

        $violations = Validator::create()->validate($document, Version::V3_1_2);

        self::assertTrue($violations->isValid());
        self::assertCount(1, $violations->warnings());
        self::assertSame([], $violations->errors());
    }

    public function test_the_official_schema_runs_before_the_rules(): void
    {
        $document = self::documentWithDuplicateOperationIds()->info(Info::create()->title('No version'));

        $violations = Validator::create(new OpisJsonSchemaValidator())->validate($document, Version::V3_1_2);

        self::assertSame([
            ['/info', OpisJsonSchemaValidator::class, Severity::Error],
            ['/paths/~1pets/get/operationId', Rules\UniqueOperationIdRule::class, Severity::Error],
        ], self::summary($violations));
    }

    public function test_a_feature_the_version_cannot_express_is_a_violation_instead_of_an_exception(): void
    {
        $document = Documents::webhookExample();

        $violations = Validator::create()->validate($document, Version::V3_0_4);

        self::assertSame([['/webhooks', Serializer::class, Severity::Error]], self::summary($violations));
    }

    public function test_the_version_defaults_to_the_version_of_the_document(): void
    {
        $violations = Validator::create()->validate(Documents::webhookExample()->openapi(Version::V3_0_4));

        self::assertSame([['/webhooks', Serializer::class, Severity::Error]], self::summary($violations));
    }

    public function test_a_custom_rule_reports_its_own_violations(): void
    {
        $violations = Validator::create()->withRules(new SummaryRequiredRule())->validate(Documents::tictactoe(), Version::V3_1_2);

        self::assertSame([['/info', 'summary-required', Severity::Warning]], self::summary($violations));
    }

    /**
     * @return list<string>
     */
    private static function classes(Validator $validator): array
    {
        return array_map(static fn(Rule $rule): string => $rule::class, $validator->rules());
    }

    /**
     * @return list<array{string, string, Severity}>
     */
    private static function summary(Violations $violations): array
    {
        return array_map(
            static fn(Violation $violation): array => [$violation->pointer->toString(), $violation->rule, $violation->severity],
            $violations->all(),
        );
    }

    private static function documentWithDuplicateOperationIds(): OpenApi
    {
        return OpenApi::create()
            ->info(Info::create()->title('Validation')->version('1.0.0'))
            ->paths(
                PathItem::create('/users')->get(Operation::get()->operationId('list')->responses(Response::ok())),
                PathItem::create('/pets')->get(Operation::get()->operationId('list')->responses(Response::ok())),
            );
    }
}
