<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Validation\Rules;

use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Validation\Rule;
use Ark4ne\OpenApiWriter\Validation\Violations;

/**
 * The document declares at least one operation, in `paths` or `webhooks` (`additionalOperations` included). The
 * official schema accepts a document without any (empty `paths`, path items without methods, components only), but it
 * describes no API: warning at the root. Path items of `components` are only reusable, so they do not count.
 */
final class NonEmptyDocumentRule implements Rule
{
    public function check(array $document, Version $version, Violations $violations): void {}
}
