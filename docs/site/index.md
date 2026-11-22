---
title: OpenAPI Writer
description: Build OpenAPI documents in PHP once, and write them as valid JSON or YAML for OpenAPI 3.0, 3.1 or 3.2.
---

::example{input="PHP" output="$book->toJson() — OpenAPI 3.1.2"}
#input
```php
$book = Schema::object('Book')
    ->properties(
        Schema::string('title'),
        Schema::string('subtitle')->nullable(),
    )
    ->required('title');

$book->toJson();
```
#output
```json
{
  "type": "object",
  "required": ["title"],
  "properties": {
    "title": { "type": "string" },
    "subtitle": { "type": ["string", "null"] }
  }
}
```
::

## Why OpenAPI Writer

Hand-written OpenAPI arrays drift from the specification and break at each version. Describe your API once with
PHP objects; the library writes what each version expects, and checks it.

::card-group
:::card{title="One model, every version" icon="i-lucide-layers" to="/openapi-writer/versions-and-output/choosing-a-version"}
3.0.0 to 3.0.4, 3.1.0 to 3.1.2 and 3.2.0 from the same objects: `$book->toJson('3.0.4')` writes the subtitle as
`"nullable": true`.
:::
:::card{title="Differences converted" icon="i-lucide-repeat" to="/openapi-writer/versions-and-output/conversions"}
`nullable`, `exclusiveMinimum`, `examples` and the rest are rewritten for the target version, and every change is
reported.
:::
:::card{title="Validated" icon="i-lucide-badge-check" to="/openapi-writer/validation/validating-a-document"}
Against the official schema of the version, plus ten semantic rules, before your users find the errors.
:::
:::card{title="JSON, YAML or array" icon="i-lucide-file-json" to="/openapi-writer/versions-and-output/writing-a-document"}
Stable key order for small diffs, from the whole document down to a single schema.
:::
::

## Compatibility

Pure PHP, no framework: a script, a command, a Symfony or Laravel application.

::compatibility
::
