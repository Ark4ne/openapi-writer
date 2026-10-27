<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Objects\OpenApi;
use Ark4ne\OpenApiWriter\Tests\Support\MarkdownExamples;
use Ark4ne\OpenApiWriter\Tests\TestCase;
use Ark4ne\OpenApiWriter\Validation\Violations;
use PHPUnit\Framework\Attributes\CoversNothing;

/**
 * The PHP examples of the README run, in order, as one script.
 */
#[CoversNothing]
final class ReadmeTest extends TestCase
{
    private const README = __DIR__ . '/../../README.md';

    /**
     * @var array<string, mixed>|null the variables of the examples, run once for the three tests
     */
    private static ?array $variables = null;

    public function test_the_quick_start_writes_the_expected_document(): void
    {
        $openApi = self::examples()['openApi'] ?? null;

        self::assertInstanceOf(OpenApi::class, $openApi);
        self::assertSerializesTo($openApi, Version::V3_1_2, 'readme/quick-start');
    }

    public function test_the_document_built_by_the_examples_is_valid_in_every_line(): void
    {
        $variables = self::examples();
        $document = $variables['document'] ?? null;
        $violations = $variables['violations'] ?? null;

        self::assertInstanceOf(OpenApi::class, $document);
        self::assertInstanceOf(Violations::class, $violations);
        self::assertSame([], array_map('strval', $violations->all()));

        foreach ([Version::V3_0_4, Version::V3_1_2, Version::V3_2_0] as $version) {
            self::assertValidOpenApi($document->toJson($version), $version);
        }
    }

    public function test_the_lenient_mode_example_reports_the_dropped_webhooks_and_the_conversions(): void
    {
        $variables = self::examples();

        self::assertSame(['[unsupported] /webhooks: webhooks needs 3.1.0'], $variables['reasons'] ?? null);
        self::assertSame(
            [
                '[dropped] /webhooks: webhooks needs 3.1.0',
                '[converted] /components/schemas/Pet/properties/birthday: nullable written as nullable: true',
            ],
            $variables['diagnostics'] ?? null,
        );
        self::assertIsString($variables['json30'] ?? null);
        self::assertValidOpenApi($variables['json30'], Version::V3_0_4);
    }

    /**
     * The variables defined by the `php` blocks of the README, run in order as one script.
     *
     * @return array<string, mixed>
     */
    private static function examples(): array
    {
        return self::$variables ??= MarkdownExamples::run(self::README);
    }
}
