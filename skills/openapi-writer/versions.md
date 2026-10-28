# Versions

Describe the API with the most recent model; choose the version when writing. Within a line (3.0.x, 3.1.x) the
output is the same except for the `openapi` field.

| Target | When to choose it |
| --- | --- |
| 3.1.2 (default) | Most tools; full JSON Schema 2020-12 |
| 3.0.4 (or 3.0.0 → 3.0.3) | Older tools: some code generators, older Swagger UI versions |
| 3.2.0 | New features below, when every consumer reads 3.2 |

## Converted automatically

Written differently for 3.0, reported as `Converted` diagnostics (never an error):

| Model (3.1+ output) | 3.0 output |
| --- | --- |
| `Schema::string()->nullable()`: `type: [string, "null"]` | `type: string, nullable: true` |
| `Schema::ref('User')->nullable()`: `anyOf: [{$ref}, {type: null}]` (3.1 too) | `anyOf: [{$ref}, {enum: [null], nullable: true}]` |
| `Schema::null()`: `type: "null"` | `enum: [null], nullable: true` |
| `exclusiveMinimum(0)` / `exclusiveMaximum(1)`: numbers | `minimum: 0, exclusiveMinimum: true` (same for maximum) |
| `const('admin')` | `enum: [admin]` |
| `examples('a', 'b')` on a schema | `example: a` (the first one) |
| `type('string', 'integer')` | `anyOf: [{type: string}, {type: integer}]` |
| `Schema::ref('User')->description('…')`: `$ref` with siblings | `allOf: [{$ref}]` with the siblings |
| `contentMediaType('image/png')` | `format: binary` |
| `contentEncoding('base64')` | `format: byte` |
| Boolean schemas `items(true)`, `items(false)` | `{}`, `{not: {}}` |
| Response without `description` | `description: ""` (all versions) |
| Document without `paths` | `paths: {}` (required in 3.0) |

## Not available in older versions

Written for these versions only. Below them, strict mode throws `UnsupportedFeatureException`; lenient mode drops
them and reports `Dropped` diagnostics.

**From 3.1.0**: `OpenApi::webhooks()`, `OpenApi::jsonSchemaDialect()`, `Components::pathItems()`, `Info::summary()`,
`License::identifier()`, `Reference::summary()` / `description()`, `SecurityScheme::mutualTLS()`, scopes on a
non-OAuth2 security requirement, `contentEncoding` other than `base64` (e.g. `base64url`), `contentMediaType` with an
encoding other than base64, a `const` outside the `enum` of its schema.

**From 3.2.0**: `OpenApi::self()`, `Components::mediaTypes()` and `MediaType::ref()`, `Server::name()`,
`Operation::query()` / `PathItem::query()`, `PathItem::additionalOperations()`, `Parameter::querystring()`,
`style: cookie`, `Tag::summary()` / `parent()` / `kind()`, `MediaType::description()` / `itemSchema()` /
`prefixEncoding()` / `itemEncoding()`, the same three encodings on `Encoding`, `Example::dataValue()` /
`serializedValue()`, `Discriminator::defaultMapping()`, `Xml::nodeType()`, `OAuthFlows::deviceAuthorization()`,
`SecurityScheme::oauth2MetadataUrl()` / `deprecated()`, `Response::summary()`.

Extensions starting with `x-oai-` or `x-oas-` are reserved from 3.1.0 and rejected there.

## Redoc extensions

`VendorExtensions::Redoc` (argument of `Serializer::for()`, `toJson()`, `toYaml()`, `toArray()` and
`Validator::validate()`) writes two features as the extensions Redoc reads, instead of rejecting them. Off by
default: the output then follows the specification.

| Model | Before | Written as |
| --- | --- | --- |
| `OpenApi::webhooks()` | 3.1.0 | `x-webhooks` at the root, same shape as `webhooks` (map of name to path item) |
| `Tag::parent()` | 3.2.0 | `parent` removed from the tags; `x-tagGroups` at the root: `[{name, tags: [...]}]` |

- Each conversion is a `Converted` diagnostic (`/webhooks`, `/tags/1/parent`), in both modes. The content of the
  webhooks is written for the version as usual, and its diagnostics keep their `/webhooks/…` pointer.
- Groups follow the order of the tags. Redoc groups on one level: a tag goes into the group of its topmost ancestor,
  named after that ancestor (`cats` → `pets` → `animals`: group `animals`). A tag without parent forms its own group,
  holding itself, because Redoc hides the tags of no group; a parent missing from `tags` still names its group. A
  tag whose parents loop forms its own group. Without any parent, no `x-tagGroups` is written.
- Redoc only shows the tags listed in a group: declare in `OpenApi::tags()` every tag used by an operation.
- `Tag::summary()` and `kind()` have no Redoc form: they stay unsupported before 3.2.
- An `x-webhooks` or `x-tagGroups` set by the document with `x()` wins: the matching feature is then not converted,
  and stays unsupported (exception in strict mode, `Dropped` in lenient mode).
- A `Tag` written alone (not in an `OpenApi`) has no root to hold `x-tagGroups`: its parent stays unsupported.

## Targeting several versions

Build once, write several times. To keep one document valid everywhere, use only features of the oldest target, or
write the older versions in lenient mode and check the diagnostics:

```php
use Ark4ne\OpenApiWriter\Enums\Strictness;
use Ark4ne\OpenApiWriter\Enums\VendorExtensions;
use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Objects\Info;
use Ark4ne\OpenApiWriter\Objects\OpenApi;
use Ark4ne\OpenApiWriter\Objects\Operation;
use Ark4ne\OpenApiWriter\Objects\PathItem;
use Ark4ne\OpenApiWriter\Objects\Response;
use Ark4ne\OpenApiWriter\Objects\Tag;
use Ark4ne\OpenApiWriter\Serialization\DiagnosticLevel;
use Ark4ne\OpenApiWriter\Serialization\Serializer;
use Ark4ne\OpenApiWriter\Validation\Validator;

$multiVersion = OpenApi::create()
    ->openapi(Version::V3_2_0)                                        // the newest target: valid as declared
    ->info(Info::create()->title('Shop')->version('1.0.0')->summary('Orders and products'))
    ->tags(Tag::create()->name('shop'), Tag::create()->name('orders')->parent('shop'))
    ->paths(PathItem::create('/orders')->operations(Operation::get()->operationId('orders.index')->tags('orders')->responses(Response::ok())));

$v32 = $multiVersion->toJson();                                       // everything, as declared
$result = Serializer::for(Version::V3_0_4, Strictness::Lenient)->serialize($multiVersion);
$v30 = $result->toJson();                                             // without Info.summary nor Tag.parent
$lost = array_map('strval', $result->diagnostics()->ofLevel(DiagnosticLevel::Dropped));
// ['[dropped] /info/summary: summary needs 3.1.0', '[dropped] /tags/1/parent: parent needs 3.2.0']

// For a Redoc reader, keep the tag hierarchy as x-tagGroups instead of dropping it:
$redoc = Serializer::for(Version::V3_0_4, Strictness::Lenient, VendorExtensions::Redoc)->serialize($multiVersion);
$tagGroups = $redoc->toArray()['x-tagGroups'] ?? null;            // [['name' => 'shop', 'tags' => ['shop', 'orders']]]
$redocLost = array_map('strval', $redoc->diagnostics()->ofLevel(DiagnosticLevel::Dropped));
// ['[dropped] /info/summary: summary needs 3.1.0']

// Validate with the same option. Without Info.summary, the 3.0 document loses nothing:
$redocReady = $multiVersion->info(Info::create()->title('Shop')->version('1.0.0'));
$redocViolations = array_map('strval', Validator::create()->validate($redocReady, Version::V3_0_4, VendorExtensions::Redoc)->all());
// []
```
