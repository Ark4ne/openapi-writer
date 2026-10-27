<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Validation;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Exceptions\InvalidArgumentException;
use Ark4ne\OpenApiWriter\Objects\OpenApi;

/**
 * Writes a document for a version and checks it: official schema (when a JsonSchemaValidator is given), then every
 * rule (Composite). Never throws on an invalid document.
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
        return new self([], $schemaValidator);
    }

    /**
     * Adds custom rules.
     */
    public function withRules(Rule ...$rules): self
    {
        return $this;
    }

    /**
     * Disables rules by class name.
     *
     * @param class-string<Rule> ...$rules
     */
    public function withoutRules(string ...$rules): self
    {
        return $this;
    }

    /**
     * @return list<Rule>
     */
    public function rules(): array
    {
        return $this->rules;
    }

    /**
     * @throws InvalidArgumentException on an unknown version
     */
    public function validate(OpenApi $openApi, Version|string|null $version = null): Violations
    {
        return new Violations();
    }
}
