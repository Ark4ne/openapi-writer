# Architecture, concepts and patterns

This document sets the concepts and patterns of the library. Every contribution follows them; a departure is
discussed and recorded here before it is coded.

**Guiding idea:** the model **describes** (declarative), the serialization **decides** (version, conversions,
rejections). One field = one annotated property; one difference between versions = one class. Nothing else.

## 1. Layers and dependency rule

```
Support  ←  Enums  ←  Metadata  ←  Objects / Contracts  ←  Serialization  ←  Validation
   (Exceptions: usable by every layer)
```

| Layer | Folder | Content |
| --- | --- | --- |
| Support | `src/Support/` | `Undefined`, `JsonPointer`: building blocks without dependency |
| Enums | `src/Enums/` | `Version`, `Strictness`, `ParameterLocation`… |
| Metadata | `src/Metadata/` | `#[Field]` attribute, `ClassMetadata`, `MetadataFactory` |
| Objects | `src/Objects/`, `src/Contracts/` | The 29 objects of the specification, the "X or Reference" unions |
| Serialization | `src/Serialization/` | `Serializer`, `Normalizer`, `Rewriters/`, `Diagnostics`, `Encoders/` |
| Validation | `src/Validation/` | `Validator`, `Rules/`, `Violations` |
| Exceptions | `src/Exceptions/` | Single exception hierarchy |

- A layer depends only on the layers to its left. `Objects` knows neither `Serialization` nor `Validation`.
- **Only exception:** the base class `SpecObject` exposes `toArray()` / `toJson()` / `toYaml()` as a facade that
  delegates to `Serializer`. No other object imports `Serialization`.
- `Exceptions` depends only on `Support`, `Enums` and the diagnostic classes of `Serialization` (`Diagnostics`,
  `Diagnostic`, `DiagnosticLevel`), which `UnsupportedFeatureException` carries.
- These rules are checked by architecture tests (`phpat`, a PHPStan extension) in the CI:
  `tests/Architecture/ArchitectureTest.php`, run by `composer stan`.

## 2. The model

### 2.1 `SpecObject`: immutable value object

Every class of `src/Objects/` extends `SpecObject` (or `ExtensibleSpecObject`). Patterns: **immutable Value
Object**, **Fluent Interface**, **Named Constructor**.

```php
final class Info extends ExtensibleSpecObject
{
    #[Field] protected string|Undefined $title = Undefined::Value;
    #[Field(since: Version::V3_1_0)] protected string|Undefined $summary = Undefined::Value;
    #[Field] protected string|Undefined $description = Undefined::Value;
    #[Field] protected string|Undefined $termsOfService = Undefined::Value;
    #[Field] protected Contact|Undefined $contact = Undefined::Value;
    #[Field] protected License|Undefined $license = Undefined::Value;
    #[Field] protected string|Undefined $version = Undefined::Value;

    public function title(?string $title): static
    {
        return $this->with('title', $title);
    }

    public function summary(?string $summary): static
    {
        return $this->with('summary', $summary);
    }

    // … one method per field, same name as in the specification
}
```

Rules:

1. **`final` classes.** Extension goes through composition and `x-*` extensions, never inheritance. Only
   `SpecObject` and `ExtensibleSpecObject` are abstract.
2. **Construction**: `final protected` constructor, creation through `create(?string $objectId = null)` and the
   semantic shortcuts (`Schema::string()`, `Operation::get()`…), which all call `create()`.
3. **Modification**: every setter calls `with()` from the base, which clones and assigns. The current object never
   changes. There is no other way to write a property.
4. **Property order = order of the fields in the text of the specification.** This order gives the order of the
   keys in the output; nothing is ever sorted.
5. **Reading**: `protected` properties exposed read-only through `__get` (like `goldspecdigital/oooas`), declared as
   `@property-read` for static analysis. An unset field reads `null`; `has('field')` tells "unset" from `null`.

### 2.2 `Undefined`: the sentinel

`enum Undefined { case Value; }` in `src/Support/`. It is the default value of **every** field.

- An `Undefined` field does not appear in the output; a `null` field is written `null`.
- Setters of typed fields (`?string`, `?Contact`…): `null` resets the field to `Undefined`.
- Free-value fields (`default`, `example`, `const`, extension values): `null` is a real value. To remove the field,
  `without('default')`.
- `Undefined` never appears in a public signature.

### 2.3 Declarative metadata: `#[Field]`

**Metadata Mapping** pattern. The attribute carries everything the serialization needs to know about a field:

| Argument | Role | Example |
| --- | --- | --- |
| `name` | Output name when it differs from the property | `#[Field(name: '$ref')] protected … $ref` |
| `since` | First version that knows the field | `#[Field(since: Version::V3_1_0)]` |
| `until` | Last version that knows it | `#[Field(until: Version::V3_0_4)]` |
| `map` | Array keyed by `objectId` (written `{}` even when empty) | `#[Field(map: true)]` |
| `emit` | `false` for an intent that only a rewriter translates | `#[Field(emit: false)] protected bool $nullable` |
| `patterned` | Map whose entries are written directly in the object (the "patterned" fields of the specification) | `Responses::$responses`, `Callback::$pathItems`, `SecurityRequirement::$schemes` |
| `keyedByObjectId` | `false` for a map of values (setters take an `array<string, T>`), with no `objectId` to read | `#[Field(map: true, keyedByObjectId: false)] protected array $scopes` |

- `MetadataFactory` reads the attributes through reflection once per class and caches the result (**Flyweight**).
  It is the only cache allowed, because it is pure and deterministic.
- The availability of a field per version is **always** declared through `since` / `until`, never coded in an `if`.

### 2.4 Versioned enums

Enum cases that appeared in a later version declare it (`ParameterLocation::Querystring`, `HttpMethod::Query`,
`SecuritySchemeType::MutualTLS`). These enums implement `VersionedEnum::since(): Version`; the serialization treats
them exactly like a `since` field. The `since()` of a case is a declaration, never a neutral body. The shortcuts rely
on these enums (`Operation::get()` uses `HttpMethod::Get->value`). `VersionedEnum` lives in `src/Enums/`: an enum
cannot depend on `Contracts`, which is to its right.

### 2.5 "X or Reference" unions: contract interfaces

Wherever the specification accepts "X or Reference Object", the type is an interface of `src/Contracts/`
(`SchemaContract`, `ResponseContract`…) implemented by the object and by `Reference`. No `Schema|Reference` in the
signatures.

- The shortcuts `Response::ref()`, `Parameter::ref()`… return a `Reference` and build the path
  `#/components/<section>/<name>` themselves: a `Reference` does not know its section, `Reference::to()` writes its
  argument as is.
- **Exception:** `Schema::ref()` returns a `Schema` carrying `$ref`. In JSON Schema, `$ref` is a keyword like any
  other: `Schema::ref('User')->nullable()` and sibling fields stay possible, and the rewriters convert them for 3.0.
  This field therefore has no instance setter (it would share the name of the static shortcut).
- Fields whose name starts with `$` have a setter without the `$` (`$id` → `id()`, `$defs` → `defs()`) and
  `#[Field(name: '$id')]`.
- Boolean subschemas (JSON Schema 2020-12): `items`, `contains`, `not`, `propertyNames`, `if` / `then` / `else`,
  `unevaluated*` and `additionalProperties` accept `SchemaContract|bool`. `BooleanSchemaRewriter` converts them for
  3.0 (`{}` / `{not: {}}`), except `additionalProperties`, boolean in every version.

### 2.6 `objectId`, collections and maps

- `objectId` is the key of the object in its parent; it is not one of the `#[Field]` fields.
- Every list or map field has **two** methods, both returning a clone:
    - the setter named after the specification, variadic, which **replaces** the whole content:
      `->servers(Server ...$servers)`;
    - `add` + the exact field name, variadic, which **appends**: `->addServers(Server ...$servers)`.
- Name of the `add*` methods: `add` followed by the field name, never singularized. This avoids irregular plurals
  (`requestBodies`) and collisions (`example()` already exists next to `examples()`). Examples: `addProperties()`,
  `addPaths()`, `addTags()`, `addRequired()`, `addAllOf()`.
- Lists (`servers`, `tags`, `required`, `allOf`…): `add*` appends without deduplicating; uniqueness, when required
  (`required`, `enum`), belongs to the validator. **Exception:** `Schema.type`, where a duplicate is forbidden in
  every version: `type()` and `addType()` reject it (`SpecObjectTestCase::listsOfUniqueValues()`).
- Maps (`properties`, `paths`, `responses`, sections of `components`): keyed by `objectId`, in both methods. A
  missing `objectId` → `MissingObjectIdException`. An `objectId` already present → `DuplicateObjectIdException`,
  with `add*` as with the setter: an entry is never silently replaced.
- Both methods go through the base: `with('servers', $items)` and `withAdded('servers', $items)`.
- `Responses`: a response with the `objectId` `default` goes into the `default` field. `responses()` (replacing)
  substitutes it for the previous one and keeps the previous one when the new set has none; `addResponses()`
  (appending) throws `DuplicateObjectIdException` when the field is already set.
- `PathItem::operations()` recognizes a fixed method whatever its case (`GET` → `get`); `additionalOperations()` and
  `addAdditionalOperations()` reject it, whatever its case (`QUERY` included).
- Exclusive fields, rejected at construction in every version: `License.url` / `identifier`,
  `Link.operationRef` / `operationId`, `example` / `examples` (`Parameter`, `Header`, `MediaType`),
  `Example.value` / `externalValue` / `dataValue` / `serializedValue` (`value` excludes the other three,
  `serializedValue` excludes `externalValue`), `encoding` / `prefixEncoding` + `itemEncoding` (`MediaType`,
  `Encoding`), `Xml.nodeType` / `attribute` + `wrapped`. An empty collection does not count as set.
  `exclusiveOf($field, ...$others)`, in the base `SpecObject`, carries the rule.
- `Xml.nodeType` is an enum (`XmlNodeType`), like the other closed values.
- In a free-value field (`example`, `value`, `dataValue`), an empty array is a value: it counts as set for the
  exclusive fields.
- `Schema::type()` without argument removes the type.
- `Operation::responses()` accepts a `Responses` or responses that it wraps: it is the only setter that is not a
  single call to `with()`.
- **Exception: maps of scalar values** (`Link.parameters`, `Discriminator.mapping`, `OAuthFlow.scopes`,
  `Schema.dependentRequired`, `SecurityRequirement`): their entries have no `objectId`, so both methods take an
  `array<string, T>` instead of variadic arguments, and the field declares it (`keyedByObjectId: false`) so that
  `with()` has nothing to guess. A key already present also throws `DuplicateObjectIdException`.
- `SpecObjectTestCase` checks that every list or map field has its two methods.

### 2.7 Extensions

`ExtensibleSpecObject` adds `x(string $name, mixed $value)` and `withoutX(string $name)`. Only the objects the
specification declares extensible extend it: `Reference` and `SecurityRequirement` are not.

## 3. The serialization

```
OpenApi ──► Normalizer (traversal) ──► Rewriters (one version difference each) ──► array ──► Encoder (JSON | YAML)
                 │                              │
                 └────── Context: Version, Strictness, JsonPointer, Diagnostics ──────┘
```

| Pattern | Class | Role |
| --- | --- | --- |
| **Visitor** (tree traversal) | `Normalizer` | Walks the object graph with `ClassMetadata`, builds the array, follows the JSON Pointer |
| **Chain of Responsibility / Pipeline** | `Rewriter` + `RewriterChain` | Each rewriter handles **one** difference between versions |
| **Notification** | `Diagnostics` | Collects every conversion and every rejection, instead of stopping at the first one |
| **Ports & Adapters** | `Encoder` (`JsonEncoder`, `SymfonyYamlEncoder`) | Isolates the optional dependencies |
| **Facade** | `Serializer`, `SpecObject::toJson()` | Single entry point |

### 3.1 `Normalizer`

For each object, the `Normalizer`:

1. reads its `ClassMetadata`;
2. for each set and emitted field: checks `since` / `until` against the target version. When the field is
   unavailable, it reports `Unsupported` and skips it, unless a rewriter declares that it converts it
   (`UnavailableFieldRewriter::convertsTo()`): it then normalizes it with its own diagnostics
   (`Context::detached()`), which point where the rewriter writes it. The other rewriters therefore never see a
   field the version ignores. Otherwise it normalizes the field (recursion into objects, lists, maps);
3. writes an empty object or map as `\stdClass` (so `{}` in JSON, never `[]`), including in `toArray()`; the
   rewriters follow the same convention;
4. passes the resulting node to the `RewriterChain`, then adds the diagnostics of the converted fields; a field
   announced as converted but still present is removed and reported `Unsupported`, without the diagnostics of its
   content;
5. puts the keys of the rewritten node back in the order of the fields (stable sort, rank precomputed by
   `ClassMetadata::rank()`): the entries of a "patterned" field in place of that field, unknown keys next, extensions
   last. A rewriter therefore never has to place the keys it adds (`anyOf` in place of a `$ref`…).

A non-empty map whose keys form a list (`"0"`, `"1"`…, which PHP turns into integers) is written as `\stdClass`, like
an empty map: JSON makes it an object, never an array.

A free value (`default`, `example`, extensions…) is written as is, after a recursive check (arrays and `\stdClass`):
a `JsonSerializable` is replaced by its data, so that JSON and YAML write the same thing; `NAN`, `INF`, a resource or
any other object throw an `InvalidArgumentException` carrying the JSON Pointer of the value.

The `Normalizer` contains **no** version-specific rule.

### 3.2 `Rewriter`: one version difference = one class

```php
interface Rewriter
{
    public function supports(SpecObject $object): bool;

    /**
     * @param array<string, mixed> $node   the normalized node of $object
     * @return array<string, mixed>        the node rewritten for $context->version
     */
    public function rewrite(SpecObject $object, array $node, Context $context): array;
}
```

- A rewriter covers **one row** of the version tables of the specification, for **every** version:
  `NullableRewriter`, `ExclusiveBoundsRewriter`, `ConstRewriter`, `SchemaExamplesRewriter`, `MultiTypeRewriter`,
  `RefSiblingsRewriter`, `ContentEncodingRewriter`, `ResponseDescriptionRewriter`, `EmptyPathsRewriter`…
- It reads the intent in the model (`$schema->nullable`) and writes it according to `$context->version`.
- It reports what it does: `Converted` for a conversion, `Unsupported` for what cannot be expressed.
- The order of the chain is explicit, in `RewriterChain::default()`, and tested on the combinations (`nullable` +
  `$ref` + several types).
- Four rewriters do not match a conversion row: `OpenApiVersionRewriter` writes the target version in `openapi`;
  `NonOAuthScopesRewriter` (scopes outside OAuth2, rejected in 3.0) works on the `OpenApi` node, which holds the
  whole document; `ReservedExtensionRewriter` rejects the `x-oai-` / `x-oas-` prefixes from 3.1;
  `MediaTypeReferenceRewriter` rejects a `Reference` in a `content` before 3.2.
- Two rewriters write vendor extensions, only with the `VendorExtensions::Redoc` option of the `Context`:
  `WebhooksExtensionRewriter` (`webhooks` → `x-webhooks` before 3.1) and `TagGroupsExtensionRewriter` (`Tag.parent`
  → `x-tagGroups` before 3.2, on the `Tag` then `OpenApi` nodes; it reads the whole document in
  `Context::$document`, the object serialized at the root). They implement `UnavailableFieldRewriter` and run before
  `NonOAuthScopesRewriter`, which also handles the operations of `x-webhooks`. Before 3.1, the validation rules read
  `x-webhooks` as `webhooks` (`Document`, `Scope`).
- There is therefore no `V30Adapter` class: "supporting 3.0" is the set of `since` / `until` and rewriters.

### 3.3 Diagnostics and strict mode

`Diagnostic` = level (`Converted`, `Dropped`, `Unsupported`), `JsonPointer`, message. At the end of the traversal:

- **Strict**: if there is at least one `Unsupported`, a single `UnsupportedFeatureException` holding all of them;
- **Lenient**: each `Unsupported` becomes `Dropped`, and the result carries the complete list.

Rules fixed by the test suite:

- `Converted` reports any output that differs from the shape of the model (that of the latest line): mostly in 3.0,
  but also the empty `description` of a response in 3.1, and `nullable` on a `$ref` in every version. The mode
  (strict or lenient) has no effect on a conversion.
- The pointer of a diagnostic is that of the rewritten node; the pointer of an `Unsupported` field is that of the
  field.
- An unavailable enum case (`mutualTLS`, `querystring`, `cookie` style) removes the object that carries it, at the
  pointer of that object.
- Scopes of a non-OAuth scheme in 3.0 (lenient): the list becomes `[]`, pointer `/security/0/ApiKey`; they are
  searched in the `security` of the document, of the operations, of the operations of their callbacks and of
  `components.callbacks`.
- A conversion is reported once, even when several rewriters take part in it (several types + `nullable`,
  `contentMediaType` + `contentEncoding`); two distinct conversions of the same node give two `Converted`.
- In 3.0, the "null only" schema is written `{enum: [null], type: object, nullable: true}`, in field order, including
  in an `anyOf` branch.
- `NullableWithoutTypeRewriter` is the last rewriter of `Schema`: the branch it builds contains keywords already
  converted, and `RefSiblingsRewriter` leaves it a nullable `$ref` without type.
- The reserved extensions `x-oai-` / `x-oas-` are written in 3.0, removed or rejected from 3.1.
- `Validator::validate()` does not throw `UnsupportedFeatureException`: each feature that cannot be expressed becomes
  a violation whose rule is `Serializer::class`; the errors of the official schema have the rule
  `OpisJsonSchemaValidator::class`.
- YAML: `Yaml::DUMP_OBJECT_AS_MAP | DUMP_EMPTY_ARRAY_AS_SEQUENCE | DUMP_MULTI_LINE_LITERAL_BLOCK |
  DUMP_NUMERIC_KEY_AS_STRING`, inline depth 10, indentation 2; status codes are therefore quoted. An empty map is
  written `{}` from symfony/yaml 8.1, `{  }` before.

## 4. The validation

| Pattern | Class | Role |
| --- | --- | --- |
| **Rule / Specification** | `Rule` | One semantic rule per class |
| **Composite** | `Validator` | Applies a list of rules, which can be added and removed |
| **Notification** | `Violations` | Collects every violation |
| **Ports & Adapters** | `JsonSchemaValidator` (`OpisJsonSchemaValidator`) | Official validation (level 1), optional dependency |

The rules work on **the serialized document** (the array actually written, with its JSON Pointers), not on the
model: what is validated is what comes out, for a given version.

```php
interface Rule
{
    /** @param array<string, mixed> $document */
    public function check(array $document, Version $version, Violations $violations): void;
}
```

- `Validator::validate()` writes the document in lenient mode. When a feature had to be removed (a
  `Serializer::class` violation), neither the official schema nor the rules run: the written document is no longer
  the described one, and the consequences of the removal (empty document, orphan reference) would be reported.
- A rule may receive a document the official schema rejects: every access checks the shape of the node.
- The traversal is shared by `Rules\Document` (internal): path items and operations written in place, in document
  order (`paths`, `webhooks`, then `components`), each operation followed by those of its callbacks; a `$ref` path
  item is listed with the fields written next to its reference, without its target. `Document::resolve()` follows
  local `$ref` (parameters, path items, security schemes); an external target is not read. A violation is reported
  where it is fixed: the reference rather than its target.
- `Rules\Scope` knows whether a node is a specification object or a map of names, from the field that holds it and
  the nature of its parent: a map entry is always an object, even when named `content`. It spots free values
  (`example`, `default`, `const`, `enum`, `value`, `dataValue`, extensions, the `examples` list of a schema, the
  `requestBody` of a Link): a `$ref` or a `schema` found there is data. `x-` keys are extensions in Paths, Responses
  and Callback, names in the other maps.
- `OpisJsonSchemaValidator` puts at the root (`""`) the errors opis reports under `/`. A document that cannot be
  encoded as JSON (invalid UTF-8) becomes a `Serializer::class` violation instead of an exception; the rules run
  anyway.
- `OpisJsonSchemaValidator` loads the schemas on the first validation, once per instance (no static state): reuse an
  instance to validate several documents.

## 5. Errors

- Every exception implements the `OpenApiWriterException` interface and extends the matching SPL exception
  (`InvalidArgumentException extends \InvalidArgumentException`…).
- They are created through named constructors: `InvalidArgumentException::forField(Info::class, 'license', '…')`,
  `UnsupportedFeatureException::fromDiagnostics($diagnostics)`.
- The message starts with the target: `Info::license: url and identifier are mutually exclusive`.
- An immediate exception only for a value forbidden **in every version**. What depends on the version belongs to the
  serialization; what depends on the whole document belongs to the validator.
- Forbidden values (`InvalidArgumentException`): `url` + `identifier` of a license, `example` + `examples`
  (Parameter, Header, MediaType), `value` + `externalValue` (Example), `operationRef` + `operationId` (Link),
  malformed status code, negative `min*` / `max*` counter, `multipleOf` ≤ 0, unknown or duplicate type, `x('')` and
  `x('x-')`, `Reference::for()` on an object outside the components.
- Reference messages: `Parameter::in: "body" is not one of query, header, path, cookie, querystring`,
  `Components::schemas: an entry with the objectId "User" already exists`, `Components::schemas: an entry has no
  objectId`, `Reference::for: the Schema has no objectId`, `symfony/yaml is required to write YAML: composer
  require symfony/yaml`, and `"2 features cannot be written for this version:\n- /webhooks: …"`.

## 6. Code conventions

| Topic | Rule |
| --- | --- |
| Classes | `final` by default; abstract only for the base classes of the model |
| Traits | Forbidden in `src/`: use an abstract base or composition |
| Global state | No singleton, no mutable static property; only the cache of `MetadataFactory` is allowed |
| Types | `declare(strict_types=1)`, every signature typed; `mixed` reserved for free values and documented |
| PHPDoc | Generics for collections (`list<Server>`, `array<string, SchemaContract>`), no redundant PHPDoc |
| Enums | Methods accept the enum or its value; conversion through `::from()`, invalid value = `InvalidArgumentException` |
| Naming | Objects = singular name from the specification; unions = `*Contract`; capabilities = adjective (`Extensible`); services = noun (`Rewriter`, `Rule`, `Encoder`); no `Interface` suffix |
| Rewriters / rules | Named after the feature: `NullableRewriter`, `UniqueOperationIdRule` |
| Files | One class per file, PSR-4, folder = layer |

## 7. Test patterns

- **Template Method**: `SpecObjectTestCase` (abstract) tests for each object the immutability, `objectId`, the
  extensions and every field from a `fields()` provider. Every object test class extends it and adds its own cases.
- **Object Mother**: `tests/Support/Documents.php` provides the reference documents (petstore, webhooks…), shared by
  the Conformance and Validation suites.
- **One test per rewriter and per version**, and one test per rule (a valid case, an invalid case).
- Shared assertions in a base class `TestCase` (`assertSerializesTo`, `assertValidOpenApi`), not in traits.
- Arrange / Act / Assert structure, test name as a sentence.
- **Fixtures**: `tests/fixtures/<line>/` (`3.0`, `3.1`, `3.2`), written for the latest patch of the line; for another
  patch, the helper replaces the `openapi` field. PHP's `JSON_PRETTY_PRINT` format plus a newline, compared byte by
  byte. A fragment is wrapped in a minimal document (`Envelope`) before it is validated.
- The expected fixture is validated against the official schema **before** the comparison (`OfficialSchema`, a test
  tool independent from the tested code, configured like `OpisJsonSchemaValidator`).
- `composer fixtures:update` rewrites the fixtures from the actual output, only when it is valid JSON: every change is
  reviewed in the diff.
- Test doubles (rewriters, rules) are named classes of `tests/Support/`: the `phpat` naming rules also apply to
  anonymous classes.

## 8. Recipes

### Adding a field

1. Add the `#[Field(since: …)]` property at its place in the order of the specification.
2. Add the setter with the same name, which calls `with()`.
3. Add the case to `fields()` in the test of the object, and the output fixture of each version concerned.
4. If the field cannot be expressed in an earlier version, nothing else to do: `since` is enough. If it is
   **converted**, write a rewriter (next recipe).

### Adding a conversion between versions

1. Create `src/Serialization/Rewriters/<Feature>Rewriter.php` and insert it at its place in
   `RewriterChain::default()`.
2. Write the test of the rewriter for each version, and the matching row of the `tests/Compatibility/` matrix.
3. Document the conversion in `skills/openapi-writer/versions.md` and in the `CHANGELOG.md`.

## 9. Automatic safeguards

| Safeguard | What it checks |
| --- | --- |
| PHPStan max level + strict rules | Types, dead code, comparisons |
| `phpat` (architecture tests, `composer stan`) | Dependency rule between layers, `final` classes, abstract classes reserved to the base of the model, no trait in `src/`, naming (`*Contract`, `*Rewriter`, `*Rule`, no `Interface` suffix) |
| PHP-CS-Fixer (PER-CS) | Style |
| `SpecObjectTestCase` | Common contract of the 29 objects |
| Infection | Quality of the tests (MSI ≥ 85%) |
