---
name: openapi-writer
description: Build, write and validate OpenAPI 3.0, 3.1 and 3.2 documents in PHP with the ark4ne/openapi-writer library (fluent immutable objects, output for a chosen version as JSON, YAML or array, semantic validation). Use when code imports Ark4ne\OpenApiWriter, when a PHP project must generate an OpenAPI or Swagger specification, or when integrating this library into a project.
---

# ark4ne/openapi-writer

A framework-agnostic PHP 8.2+ library that **builds** OpenAPI documents with one immutable PHP object per object of
the specification, and **writes** them for any version: 3.0.0 → 3.0.4, 3.1.0 → 3.1.2, 3.2.0. It has no runtime
dependency besides `ext-json`.

It does not read or parse OpenAPI files, scan PHP code, resolve external `$ref`, or validate HTTP traffic: the
calling code describes the API, the library writes it.

Files of this skill:

- [reference.md](reference.md): every object, every method, the enums, the exceptions. Read it before calling a
  method you have not seen in an example.
- [recipes.md](recipes.md): complete, tested patterns (CRUD resource, pagination, polymorphism, uploads, auth,
  webhooks, 3.0 output, tests).
- [versions.md](versions.md): what each version can write, and what is converted or rejected.

The source is the final authority: `vendor/ark4ne/openapi-writer/src/Objects/` (one class per object, one setter per
field, each with a docblock).

## Install

```bash
composer require ark4ne/openapi-writer
composer require --dev opis/json-schema   # validation against the official schemas (recommended in tests)
composer require symfony/yaml             # only to write YAML
```

## Rules that prevent most mistakes

1. **Objects are immutable. Every method returns a new object: always use the return value.**
   `$document->addPaths($path);` alone does nothing; write `$document = $document->addPaths($path);`.
2. **Method names are the specification field names**, exactly: `operationId()`, `requestBody()`,
   `exclusiveMinimum()`, `additionalProperties()`. No synonyms, no abbreviations. If a field exists in the
   specification, the method exists.
3. **The first argument of `create()` and of the shortcuts is the `objectId`: the key of the object in its parent**
   (property name, component name, path, status code, media type, header name). Entries of a map need one, and two
   entries of the same map cannot share it (`MissingObjectIdException`, `DuplicateObjectIdException`).
   - `Schema::string('email')` is the property `email`; `Schema::object('User')` in `Components::schemas()` is the
     component `User`.
   - `PathItem::create('/users/{id}')`, `Header::create('X-Rate-Limit')`, `Example::create('admin')`.
   - Shortcuts set it for you: `Response::ok()` is `200`, `MediaType::json()` is `application/json`,
     `Operation::get()` is `get`.
   - `objectId('NotFound')` changes it: `Response::notFound()->objectId('NotFound')` for a component.
   - `Tag::create('pets')` sets only the objectId; **a tag needs `->name('pets')`**.
4. **Lists and maps have two methods**: the field name **replaces** (`paths()`, `properties()`, `tags()`), `add` +
   the field name **appends** (`addPaths()`, `addProperties()`, `addTags()`). Both are variadic and take objects,
   never associative arrays. Exceptions, which take an `array<string, T>`: `Link::parameters()`,
   `Discriminator::mapping()`, `OAuthFlow::scopes()`, `Schema::dependentRequired()`, `SecurityRequirement::schemes()`.
5. **References**: `Schema::ref('User')` writes `{"$ref": "#/components/schemas/User"}` and stays a `Schema` (you can
   chain `->description()`, `->nullable()`). `Response::ref()`, `Parameter::ref()`, `RequestBody::ref()`,
   `Header::ref()`, `Example::ref()`, `Link::ref()`, `Callback::ref()`, `SecurityScheme::ref()`, `MediaType::ref()`
   return a `Reference` to their component section. A value containing `#` or `/` is written as is (external files).
   `Reference::for($component)` builds the reference from a component object.
6. **`null` resets, except for free values.** `->description(null)` removes the description. `default(null)`,
   `example(null)`, `const(null)` write a real `null`. `->without('default')` removes any field.
7. **Strict by default**: writing a feature the target version cannot express (webhooks in 3.0, `Tag.parent` before
   3.2…) throws one `UnsupportedFeatureException` listing all of them. Differences that can be expressed are
   converted silently (`nullable`, `exclusiveMinimum`, `const`, several types…). Use the lenient mode to drop and
   report instead (see below).
8. **Write the model of the latest version; choose the version when writing.** Describe a nullable string with
   `->nullable()`, not with a `null` type workaround; the library writes `nullable: true` for 3.0 and
   `type: [string, "null"]` for 3.1+.
9. **Impossible values throw immediately** (`InvalidArgumentException`): a malformed status code, `example` together
   with `examples`, `License::url()` with `identifier()`, `Link::operationRef()` with `operationId()`. Everything else
   (unique `operationId`, path parameters, references…) is checked by the `Validator`.
10. **Output key order is the specification order**, not the call order. Do not try to control it.

## Integrating it in a project

1. **Pick the target version.** 3.1.2 (default) unless a consumer needs another one: 3.0.x for older tools (some
   code generators, older Swagger UI), 3.2.0 for its new features. Set it once with `OpenApi::openapi()` or pass it to
   `toJson()`.
2. **Write one builder** (class, service or script) that returns the `OpenApi` object. Declare reusable schemas,
   responses, parameters and security schemes in `Components`, and refer to them by name with `::ref()`.
3. **Write the file** with `toJson($version, JSON_PRETTY_PRINT)` or `toYaml($version)` from a command, a build step or
   an endpoint.
4. **Test it**: run the `Validator` with `OpisJsonSchemaValidator` in the project test suite and assert there is no
   error (recipe "Validate in a test"). It checks the official schema of the version plus semantic rules.

```php
use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Objects\Components;
use Ark4ne\OpenApiWriter\Objects\Info;
use Ark4ne\OpenApiWriter\Objects\MediaType;
use Ark4ne\OpenApiWriter\Objects\OpenApi;
use Ark4ne\OpenApiWriter\Objects\Operation;
use Ark4ne\OpenApiWriter\Objects\Parameter;
use Ark4ne\OpenApiWriter\Objects\PathItem;
use Ark4ne\OpenApiWriter\Objects\Response;
use Ark4ne\OpenApiWriter\Objects\Schema;
use Ark4ne\OpenApiWriter\Objects\Tag;
use Ark4ne\OpenApiWriter\Validation\OpisJsonSchemaValidator;
use Ark4ne\OpenApiWriter\Validation\Validator;

$openApi = OpenApi::create()
    ->openapi(Version::V3_1_2)
    ->info(Info::create()->title('Users API')->version('1.0.0'))
    ->tags(Tag::create()->name('users'))
    ->paths(
        PathItem::create('/users/{id}')
            ->parameters(Parameter::path('id')->schema(Schema::integer()->format('int64'))) // path() is required
            ->operations(
                Operation::get()
                    ->operationId('users.show')
                    ->tags('users')
                    ->responses(
                        Response::ok()->content(MediaType::json()->schema(Schema::ref('User'))),
                        Response::notFound(),
                    ),
            ),
    )
    ->components(
        Components::create()->schemas(
            Schema::object('User')
                ->properties(
                    Schema::integer('id')->format('int64')->readOnly(),
                    Schema::string('email')->format('email'),
                    Schema::string('nickname')->nullable(),
                )
                ->required('id', 'email'),
        ),
    );

$json = $openApi->toJson(flags: JSON_PRETTY_PRINT);          // the declared version, 3.1.2
$legacy = $openApi->toJson(Version::V3_0_4);                  // same model, written for 3.0

$violations = Validator::create(new OpisJsonSchemaValidator())->validate($openApi);
$errors = array_map('strval', $violations->errors());       // [] when the document is valid
```

## Writing

| Call | Result |
| --- | --- |
| `$object->toJson(?Version $version = null, int $flags = 0)` | JSON string; `JSON_PRETTY_PRINT` is a common flag |
| `$object->toYaml(?Version $version = null)` | YAML string, needs `symfony/yaml` |
| `$object->toArray(?Version $version = null)` | PHP array; empty objects are `\stdClass` so they stay `{}` |
| `Serializer::for($version, Strictness::Lenient)->serialize($object)` | `SerializationResult`: `toJson()`, `toYaml()`, `toArray()`, `diagnostics()` |
| `toJson($version, extensions: VendorExtensions::Redoc)` | Webhooks and tag parents as Redoc's `x-webhooks` / `x-tagGroups` where the version lacks them ([versions.md](versions.md)) |

The version is a `Version` case or its string (`'3.0.4'`). Without one, the version given to `OpenApi::openapi()` is
used, else 3.1.2. Any object can be written, not only `OpenApi` (useful to inspect a schema).

## Errors

All exceptions implement `Ark4ne\OpenApiWriter\Exceptions\OpenApiWriterException`.

| Exception | When | Fix |
| --- | --- | --- |
| `MissingObjectIdException` | A map entry has no key: `Components::create()->schemas(Schema::string())` | Give the objectId: `Schema::string('Name')` |
| `DuplicateObjectIdException` | Two entries of a map share a key, or `add*` adds an existing key | Rename, or use the replacing setter |
| `InvalidArgumentException` | A value the specification forbids: status code `999`, unknown enum value, exclusive fields together | Fix the value |
| `UnsupportedFeatureException` | Strict writing of a feature the target version cannot express; `->diagnostics()` lists them | Remove the feature, target a newer version, or use the lenient mode |
| `MissingDependencyException` | `toYaml()` without `symfony/yaml`, `OpisJsonSchemaValidator` without `opis/json-schema` | Install the package |

## Checklist before finishing

- Every operation has `responses(...)` (required in 3.0) and a unique `operationId`.
- Every `{name}` in a path has a `Parameter::path('name')`, on the path item or on each operation.
- Every `Schema::ref('X')` has a `Schema::...('X')` in `Components::schemas()` (same for the other `::ref()`).
- Tags used by operations are declared with `OpenApi::tags(Tag::create()->name(...))`.
- `required()` is never called without names (it writes `required: []`, invalid in 3.0).
- The `Validator` with `OpisJsonSchemaValidator` reports no error for the target version.
