<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Validation;

use Ark4ne\OpenApiWriter\Enums\Strictness;
use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Exceptions\InvalidArgumentException;
use Ark4ne\OpenApiWriter\Objects\OpenApi;
use Ark4ne\OpenApiWriter\Serialization\DiagnosticLevel;
use Ark4ne\OpenApiWriter\Serialization\Serializer;
use Ark4ne\OpenApiWriter\Support\JsonPointer;
use JsonException;

/**
 * Writes a document for a version and checks it: official schema (when a JsonSchemaValidator is given), then every
 * rule (Composite). Never throws on an invalid document.
 *
 * The document is written in lenient mode: each feature the version cannot express becomes an error whose rule is
 * `Serializer::class`. The written document is then not the one described, so neither the official schema nor the
 * rules run on it: they would report the consequences of the removal (an empty document, a dangling reference).
 * A document that cannot be encoded as JSON (invalid UTF-8) is an error of `Serializer::class` instead of the official
 * schema check; the rules still run on the written array.
 */
final readonly class Validator
{
    /**
     * @param list<Rule> $rules
     */
    private function __construct(
        private array $rules,
        public ?JsonSchemaValidator $schemaValidator,
    ) {}

    /**
     * A validator with every built-in rule.
     */
    public static function create(?JsonSchemaValidator $schemaValidator = null): self
    {
        return new self([
            new Rules\UniqueOperationIdRule(),
            new Rules\PathParametersRule(),
            new Rules\UniqueParametersRule(),
            new Rules\LocalReferencesRule(),
            new Rules\SecurityRequirementsRule(),
            new Rules\DeclaredTagsRule(),
            new Rules\TagParentsRule(),
            new Rules\EquivalentPathsRule(),
            new Rules\NonEmptyDocumentRule(),
            new Rules\ValueTypeRule(),
        ], $schemaValidator);
    }

    /**
     * Adds custom rules.
     */
    public function withRules(Rule ...$rules): self
    {
        return new self([...$this->rules, ...array_values($rules)], $this->schemaValidator);
    }

    /**
     * Disables rules by class name.
     *
     * @param class-string<Rule> ...$rules
     */
    public function withoutRules(string ...$rules): self
    {
        return new self(
            array_values(array_filter($this->rules, static fn(Rule $rule): bool => !in_array($rule::class, $rules, true))),
            $this->schemaValidator,
        );
    }

    /**
     * @return list<Rule>
     */
    public function rules(): array
    {
        return $this->rules;
    }

    /**
     * @param Version|string|null $version default: the `openapi` field of the document, else 3.1.2
     *
     * @throws InvalidArgumentException on an unknown version
     */
    public function validate(OpenApi $openApi, Version|string|null $version = null): Violations
    {
        $serializer = Serializer::for($version ?? $openApi->openapi, Strictness::Lenient);
        $result = $serializer->serialize($openApi);
        $violations = new Violations();

        foreach ($result->diagnostics()->ofLevel(DiagnosticLevel::Dropped) as $diagnostic) {
            $violations->add(new Violation($diagnostic->pointer, Serializer::class, $diagnostic->message));
        }

        if (count($violations) > 0) {
            return $violations;
        }

        if ($this->schemaValidator !== null) {
            try {
                $this->schemaValidator->validate($result->toJson(), $serializer->version, $violations);
            } catch (JsonException $exception) {
                $violations->add(new Violation(JsonPointer::root(), Serializer::class, 'the document cannot be written as JSON: ' . $exception->getMessage()));
            }
        }

        foreach ($this->rules as $rule) {
            $rule->check($result->toArray(), $serializer->version, $violations);
        }

        return $violations;
    }
}
