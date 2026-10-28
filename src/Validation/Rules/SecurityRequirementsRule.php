<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Validation\Rules;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Support\JsonPointer;
use Ark4ne\OpenApiWriter\Validation\Rule;
use Ark4ne\OpenApiWriter\Validation\Violation;
use Ark4ne\OpenApiWriter\Validation\Violations;

/**
 * Each security requirement names a declared security scheme, and asks only for OAuth2 scopes that its flows declare.
 */
final class SecurityRequirementsRule implements Rule
{
    public function check(array $document, Version $version, Violations $violations): void
    {
        foreach (Document::operations($document, $version) as [$pointer, $operation]) {
            self::checkRequirements($document, $version, $operation['security'] ?? null, $pointer->append('security'), $violations);
        }

        self::checkRequirements($document, $version, $document['security'] ?? null, JsonPointer::root()->append('security'), $violations);
    }

    /**
     * @param array<string, mixed> $document
     */
    private static function checkRequirements(array $document, Version $version, mixed $requirements, JsonPointer $pointer, Violations $violations): void
    {
        foreach (Document::entries($requirements) as $index => $requirement) {
            foreach (Document::entries($requirement) as $name => $scopes) {
                $name = (string) $name;
                $schemePointer = $pointer->append($index, $name);
                $scheme = self::scheme($document, $version, $name);

                if ($scheme === false) {
                    $violations->add(new Violation($schemePointer, self::class, sprintf('security scheme "%s" is not declared in components.securitySchemes', $name)));

                    continue;
                }

                if (!is_array($scheme) || ($scheme['type'] ?? null) !== 'oauth2') {
                    continue;
                }

                $declared = self::scopes($scheme);

                foreach (Document::entries($scopes) as $position => $scope) {
                    if (is_string($scope) && !in_array($scope, $declared, true)) {
                        $violations->add(new Violation(
                            $schemePointer->append($position),
                            self::class,
                            sprintf('scope "%s" is not declared by the flows of "%s"', $scope, $name),
                        ));
                    }
                }
            }
        }
    }

    /**
     * The security scheme a requirement names, references followed: false when it does not exist, null when it
     * cannot be read. From 3.2, a name that is not a component and holds a `/` or a `#` is a URI: a local one must
     * resolve, an external one is not checked. Any other name (`oauth:main`) is a component name.
     *
     * @param array<string, mixed> $document
     */
    private static function scheme(array $document, Version $version, string $name): mixed
    {
        $schemes = Document::entries(Document::entries($document['components'] ?? null)['securitySchemes'] ?? null);

        if (array_key_exists($name, $schemes)) {
            return Document::resolve($document, $schemes[$name]);
        }

        if (!$version->isAtLeast(Version::V3_2_0) || !str_contains($name, '/') && !str_contains($name, '#')) {
            return false;
        }

        $reference = Document::local($name);

        if ($reference === null) {
            return null;
        }

        [$found, $scheme] = Document::find($document, $reference);

        return $found ? Document::resolve($document, $scheme) : false;
    }

    /**
     * The scopes declared by every flow of an OAuth2 scheme.
     *
     * @param array<mixed> $scheme
     *
     * @return list<string>
     */
    private static function scopes(array $scheme): array
    {
        $scopes = [];

        foreach (Document::entries($scheme['flows'] ?? null) as $flow) {
            foreach (array_keys(Document::entries(Document::entries($flow)['scopes'] ?? null)) as $scope) {
                $scopes[] = (string) $scope;
            }
        }

        return $scopes;
    }
}
