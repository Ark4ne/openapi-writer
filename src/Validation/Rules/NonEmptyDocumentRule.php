<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Validation\Rules;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Support\JsonPointer;
use Ark4ne\OpenApiWriter\Validation\Rule;
use Ark4ne\OpenApiWriter\Validation\Severity;
use Ark4ne\OpenApiWriter\Validation\Violation;
use Ark4ne\OpenApiWriter\Validation\Violations;

/**
 * The document declares at least one operation, in `paths` or `webhooks` (`additionalOperations` included). The
 * official schema accepts a document without any (empty `paths`, path items without methods, components only), but it
 * describes no API: warning at the root. Path items of `components` are only reusable, so they do not count; a path
 * item of another document (external `$ref`) is not read and counts.
 */
final class NonEmptyDocumentRule implements Rule
{
    public function check(array $document, Version $version, Violations $violations): void
    {
        foreach (['paths', 'webhooks'] as $field) {
            foreach (Document::entries($document[$field] ?? null) as $key => $pathItem) {
                if (Document::isExtension($key) || !is_array($pathItem)) {
                    continue;
                }

                $reference = $pathItem['$ref'] ?? null;

                // A path item of another document is not read: it may declare operations.
                if (is_string($reference) && Document::local($reference) === null) {
                    return;
                }

                $target = $reference === null ? null : Document::resolve($document, $pathItem);

                foreach ([$pathItem, $target] as $candidate) {
                    if (is_array($candidate) && Document::operationsOf($candidate, JsonPointer::root())->valid()) {
                        return;
                    }
                }
            }
        }

        $violations->add(new Violation(JsonPointer::root(), self::class, 'the document declares no operation, in paths or webhooks', Severity::Warning));
    }
}
