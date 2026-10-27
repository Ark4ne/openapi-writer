<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Unit;

use Ark4ne\OpenApiWriter\Objects\ExtensibleSpecObject;
use Ark4ne\OpenApiWriter\Objects\OpenApi;
use Ark4ne\OpenApiWriter\Objects\SpecObject;
use Ark4ne\OpenApiWriter\Tests\Support\MarkdownExamples;
use Ark4ne\OpenApiWriter\Tests\TestCase;
use Ark4ne\OpenApiWriter\Validation\OpisJsonSchemaValidator;
use Ark4ne\OpenApiWriter\Validation\Validator;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\Attributes\DataProvider;
use ReflectionClass;
use ReflectionMethod;
use UnitEnum;

/**
 * The agent skill shipped with the package (`skills/openapi-writer/`): its examples run, the documents they build are
 * valid, and its reference names every public method and every enum case.
 */
#[CoversNothing]
final class SkillTest extends TestCase
{
    private const DIRECTORY = __DIR__ . '/../../skills/openapi-writer/';

    /**
     * @var array<string, array<string, mixed>> variables of the examples, by file, run once
     */
    private static array $variables = [];

    public function test_the_skill_declares_its_name_and_when_to_use_it(): void
    {
        $skill = self::read('SKILL.md');

        self::assertSame(1, preg_match('/\A---\nname: openapi-writer\ndescription: (.+)\n---\n/', $skill, $matches));
        self::assertLessThanOrEqual(1024, strlen($matches[1]));
        self::assertStringContainsString('Ark4ne\OpenApiWriter', $matches[1]);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function files(): array
    {
        return ['SKILL.md' => ['SKILL.md'], 'recipes.md' => ['recipes.md'], 'versions.md' => ['versions.md']];
    }

    #[DataProvider('files')]
    public function test_every_document_built_by_the_examples_is_valid_without_any_violation(string $file): void
    {
        $documents = array_filter(self::examples($file), static fn(mixed $value): bool => $value instanceof OpenApi);
        $validator = Validator::create(new OpisJsonSchemaValidator());

        self::assertNotSame([], $documents);

        foreach ($documents as $name => $document) {
            self::assertSame([], array_map('strval', $validator->validate($document)->all()), '$' . $name);
        }
    }

    public function test_the_quick_example_writes_3_1_and_3_0_without_error(): void
    {
        $variables = self::examples('SKILL.md');

        self::assertSame([], $variables['errors'] ?? null);
        self::assertIsString($variables['legacy'] ?? null);
        self::assertStringStartsWith('{"openapi":"3.0.4"', $variables['legacy']);
    }

    public function test_the_lenient_example_drops_what_3_0_cannot_write(): void
    {
        self::assertSame(
            ['[dropped] /info/summary: summary needs 3.1.0', '[dropped] /tags/1/parent: parent needs 3.2.0'],
            self::examples('versions.md')['lost'] ?? null,
        );
    }

    public function test_the_recipes_validate_in_every_version_and_run_a_custom_rule(): void
    {
        $variables = self::examples('recipes.md');

        self::assertSame([], $variables['messages'] ?? null);
        self::assertIsArray($variables['summaryWarnings'] ?? null);
        self::assertContains('[warning] /paths/~1books/get: the operation has no summary (operation-summary)', $variables['summaryWarnings']);
    }

    /**
     * @return iterable<string, array{class-string<SpecObject>}>
     */
    public static function objects(): iterable
    {
        foreach (self::phpFiles('src/Objects') as $file) {
            $class = 'Ark4ne\\OpenApiWriter\\Objects\\' . basename($file, '.php');

            if (is_subclass_of($class, SpecObject::class) && !(new ReflectionClass($class))->isAbstract()) {
                yield basename($file, '.php') => [$class];
            }
        }
    }

    /**
     * @param class-string<SpecObject> $class
     */
    #[DataProvider('objects')]
    public function test_the_reference_names_every_public_method_of_each_object(string $class): void
    {
        $name = (new ReflectionClass($class))->getShortName();
        $section = self::section($name);

        foreach ((new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (str_starts_with($method->name, '__') || in_array($method->getDeclaringClass()->name, [SpecObject::class, ExtensibleSpecObject::class], true)) {
                continue;
            }

            self::assertMatchesRegularExpression('/[`\s:]' . preg_quote($method->name, '/') . '[(`]/', $section, sprintf('%s::%s()', $name, $method->name));
        }
    }

    public function test_the_reference_names_every_enum_case(): void
    {
        $reference = self::read('reference.md');

        foreach (self::phpFiles('src/Enums') as $file) {
            $enum = 'Ark4ne\\OpenApiWriter\\Enums\\' . basename($file, '.php');

            if (!enum_exists($enum)) {
                continue;
            }

            self::assertSame(1, preg_match('/^\| `' . basename($file, '.php') . '` \| (.+) \|$/m', $reference, $row), $enum);
            self::assertTrue(is_subclass_of($enum, UnitEnum::class));

            foreach ($enum::cases() as $case) {
                self::assertStringContainsString('`' . $case->name . '`', $row[1] ?? '', $enum);
            }
        }
    }

    public function test_every_link_between_the_files_of_the_skill_resolves(): void
    {
        foreach (['SKILL.md', 'reference.md', 'recipes.md', 'versions.md'] as $file) {
            preg_match_all('/\]\(([a-z-]+\.md)\)/', self::read($file), $links);

            foreach ($links[1] as $link) {
                self::assertFileExists(self::DIRECTORY . $link, $file);
            }
        }
    }

    /**
     * The `###` section of reference.md whose heading names $object (`### OAuthFlows and OAuthFlow`).
     */
    private static function section(string $object): string
    {
        $sections = preg_split('/^(?=#{2,3} )/m', self::read('reference.md'));

        foreach ($sections === false ? [] : $sections as $section) {
            $heading = strtok($section, "\n");

            if (is_string($heading) && str_starts_with($heading, '### ') && in_array($object, explode(' and ', substr($heading, 4)), true)) {
                return $section;
            }
        }

        self::fail(sprintf('reference.md has no section for %s.', $object));
    }

    /**
     * @return array<string, mixed>
     */
    private static function examples(string $file): array
    {
        return self::$variables[$file] ??= MarkdownExamples::run(self::DIRECTORY . $file);
    }

    /**
     * @return list<string> the PHP files of a folder of the library
     */
    private static function phpFiles(string $folder): array
    {
        $files = glob(__DIR__ . '/../../' . $folder . '/*.php');

        return $files === false ? [] : $files;
    }

    private static function read(string $file): string
    {
        return (string) file_get_contents(self::DIRECTORY . $file);
    }
}
