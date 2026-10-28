# API reference

Namespaces: objects in `Ark4ne\OpenApiWriter\Objects`, enums in `Ark4ne\OpenApiWriter\Enums`, validation in
`Ark4ne\OpenApiWriter\Validation`, exceptions in `Ark4ne\OpenApiWriter\Exceptions`, serialization in
`Ark4ne\OpenApiWriter\Serialization`.

Conventions used below:

- Every method returns a modified copy (`static`) unless a return type is given.
- `T...` is variadic. For a list or map, the plain method replaces and the `add` method appends.
- **(3.1+)**, **(3.2)** mark fields that the older versions cannot write (see [versions.md](versions.md)).
- A method taking an enum also takes its string value: `->in('header')` or `->in(ApiKeyLocation::Header)`.
- "objectId" is the key of the object in its parent (rule 3 of SKILL.md).

## Common to every object

| Method | Purpose |
| --- | --- |
| `static create(?string $objectId = null)` | New empty object |
| `objectId(?string $objectId)` | Copy with another key |
| `without(string $field)` | Copy without the field (`'default'`, `'description'`…) |
| `has(string $field): bool` | Whether the field is set |
| `$object->field` | Read a field (`$schema->maxLength`): `null` when unset |
| `toArray($version, $extensions)`, `toJson($version, $flags, $extensions)`, `toYaml($version, $extensions)` | Write the object (SKILL.md, "Writing") |
| `x(string $name, mixed $value)` | Extension `x-<name>` on extensible objects (all but `Reference` and `SecurityRequirement`); the prefix is added when missing |
| `withoutX(string $name)` | Remove an extension |

## Document

### OpenApi

Root object. `OpenApi::create()`.

| Method | Notes |
| --- | --- |
| `openapi(Version\|string\|null $openapi)` | Default version for writing and validating |
| `self(?string $self)` | **(3.2)** `$self` URI of the document |
| `info(?Info $info)` | Required |
| `jsonSchemaDialect(?string $jsonSchemaDialect)` | **(3.1+)** |
| `servers(Server ...)`, `addServers(Server ...)` | |
| `paths(PathItem ...)`, `addPaths(PathItem ...)` | objectId = path (`/users/{id}`) |
| `webhooks(PathItemContract ...)`, `addWebhooks(...)` | **(3.1+)** objectId = webhook name; `x-webhooks` before 3.1 with `VendorExtensions::Redoc` |
| `components(?Components $components)` | |
| `security(SecurityRequirement ...)`, `addSecurity(...)` | Alternatives; see `SecurityRequirement` |
| `tags(Tag ...)`, `addTags(Tag ...)` | List, no objectId needed |
| `externalDocs(?ExternalDocumentation $externalDocs)` | |
| `jsonSerialize()` | `json_encode($openApi)` writes it in its declared version |

### Info

`Info::create()`: `title(?string)` (required), `summary(?string)` **(3.1+)**, `description(?string)`,
`termsOfService(?string)`, `contact(?Contact)`, `license(?License)`, `version(?string)` (required: the API version).

### Contact

`Contact::create()`: `name(?string)`, `url(?string)`, `email(?string)`.

### License

`License::create()`: `name(?string)` (required), `identifier(?string)` **(3.1+)** (SPDX), `url(?string)`.
`identifier` and `url` are exclusive.

### Server

`Server::create()`: `url(?string)` (required, may hold `{variables}`), `description(?string)`, `name(?string)`
**(3.2)**, `variables(ServerVariable ...)`, `addVariables(ServerVariable ...)`.

### ServerVariable

`ServerVariable::create('region')` (objectId = variable name): `enum(string ...)`, `addEnum(string ...)`,
`default(?string)` (required), `description(?string)`.

### Tag

`Tag::create()->name('pets')`: `name(?string)` (required; `create('pets')` does **not** set it), `summary(?string)`
**(3.2)**, `description(?string)`, `externalDocs(?ExternalDocumentation)`, `parent(?string)` **(3.2)** (name of the
parent tag; `x-tagGroups` before 3.2 with `VendorExtensions::Redoc`), `kind(?string)` **(3.2)** (`nav`, `badge`,
`audience`…).

### ExternalDocumentation

`ExternalDocumentation::create()`: `description(?string)`, `url(?string)` (required).

## Paths and operations

### PathItem

`PathItem::create('/users/{id}')` (objectId = path, or webhook / callback expression).

| Method | Notes |
| --- | --- |
| `operations(Operation ...)` | Puts each operation under its method (its objectId): `get`, `post`…; any case |
| `get(?Operation)`, `put`, `post`, `delete`, `options`, `head`, `patch`, `trace` | One method each |
| `query(?Operation)` | **(3.2)** `QUERY` method |
| `additionalOperations(Operation ...)`, `addAdditionalOperations(Operation ...)` | **(3.2)** other methods: `Operation::create('COPY')`; a fixed method is refused |
| `ref(?string $ref)` | `$ref` to a path item defined elsewhere |
| `summary(?string)`, `description(?string)` | |
| `servers(Server ...)`, `addServers(Server ...)` | |
| `parameters(ParameterContract ...)`, `addParameters(...)` | Shared by every operation of the path |

### Operation

Shortcuts set the objectId to the method: `Operation::get()`, `put()`, `post()`, `delete()`, `options()`, `head()`,
`patch()`, `trace()`, `query()` **(3.2)**; `Operation::create('COPY')` for `additionalOperations`.

| Method | Notes |
| --- | --- |
| `tags(string ...)`, `addTags(string ...)` | Tag names; declare them in `OpenApi::tags()` |
| `summary(?string)`, `description(?string)` | |
| `externalDocs(?ExternalDocumentation)` | |
| `operationId(?string)` | Unique in the document |
| `parameters(ParameterContract ...)`, `addParameters(...)` | |
| `requestBody(?RequestBodyContract)` | |
| `responses(Responses\|ResponseContract ...)` | Takes responses directly (keyed by their status) or a `Responses` |
| `addResponses(ResponseContract ...)` | |
| `callbacks(CallbackContract ...)`, `addCallbacks(...)` | |
| `deprecated(?bool $deprecated = true)` | |
| `security(SecurityRequirement ...)`, `addSecurity(...)` | `security()` without argument writes `[]`: no authentication |
| `servers(Server ...)`, `addServers(Server ...)` | |

### Responses

Rarely needed: `Operation::responses()` builds it. `Responses::create()`: `default(?ResponseContract)`,
`responses(ResponseContract ...)`, `addResponses(ResponseContract ...)`. A response whose objectId is `default` goes to
the `default` field.

### Callback

`Callback::create('onPaid')` (objectId = callback name): `pathItems(PathItemContract ...)`,
`addPathItems(PathItemContract ...)` where each path item's objectId is a runtime expression
(`'{$request.body#/callbackUrl}'`). `Callback::ref(string $ref, ?string $objectId = null)`.

## Parameters

### Parameter

Shortcuts: `Parameter::path($name)` (sets `required: true`), `Parameter::query($name)`, `Parameter::header($name)`,
`Parameter::cookie($name)`, `Parameter::querystring($name)` **(3.2)**. Their second argument is the objectId, needed
only in `Components::parameters()`: `Parameter::query('page', 'Page')`. `Parameter::ref('Page')` refers to
`#/components/parameters/Page`.

| Method | Notes |
| --- | --- |
| `name(?string)`, `in(ParameterLocation\|string\|null)` | Set by the shortcuts |
| `description(?string)` | |
| `required(?bool $required = true)` | |
| `deprecated(?bool $deprecated = true)` | |
| `allowEmptyValue(?bool $allowEmptyValue = true)` | |
| `style(ParameterStyle\|string\|null)`, `explode(?bool $explode = true)`, `allowReserved(?bool $allowReserved = true)` | Serialization of arrays and objects |
| `schema(?SchemaContract)` | Either `schema` or `content` |
| `content(MediaTypeContract ...)`, `addContent(...)` | One media type, for complex values |
| `example(mixed)` | Exclusive with `examples` |
| `examples(ExampleContract ...)`, `addExamples(...)` | objectId = example name |

### Header

`Header::create('X-Rate-Limit')` (objectId = header name), `Header::ref(string $ref, ?string $objectId = null)`.
Methods: `description(?string)`, `required(?bool = true)`, `deprecated(?bool = true)`,
`style(ParameterStyle|string|null)`, `explode(?bool = true)`, `schema(?SchemaContract)`, `example(mixed)`,
`examples(ExampleContract ...)`, `addExamples(...)`, `content(MediaTypeContract ...)`, `addContent(...)`.

## Bodies and content

### RequestBody

`RequestBody::create()`, `RequestBody::ref(string $ref, ?string $objectId = null)`: `description(?string)`,
`content(MediaTypeContract ...)` (required), `addContent(...)`, `required(?bool $required = true)`.

### MediaType

objectId = media type. Shortcuts: `MediaType::json()`, `MediaType::xml()`, `MediaType::formUrlEncoded()`,
`MediaType::multipart()` (`multipart/form-data`), `MediaType::of('text/csv')`. `MediaType::ref('UserJson',
'application/json')` **(3.2)** refers to `#/components/mediaTypes/UserJson`; its second argument is the media type.

| Method | Notes |
| --- | --- |
| `schema(?SchemaContract)` | |
| `itemSchema(?SchemaContract)` | **(3.2)** schema of each item of a stream (`application/jsonl`, `text/event-stream`) |
| `description(?string)` | **(3.2)** |
| `example(mixed)`, `examples(ExampleContract ...)`, `addExamples(...)` | Exclusive |
| `encoding(Encoding ...)`, `addEncoding(...)` | objectId = property name (multipart, form) |
| `prefixEncoding(Encoding ...)`, `addPrefixEncoding(...)`, `itemEncoding(?Encoding)` | **(3.2)** exclusive with `encoding` |

### Encoding

`Encoding::create('photo')` (objectId = property name): `contentType(?string)`, `headers(HeaderContract ...)`,
`addHeaders(...)`, `style(ParameterStyle|string|null)`, `explode(?bool = true)`, `allowReserved(?bool = true)`,
`encoding(Encoding ...)` / `addEncoding(...)`, `prefixEncoding(Encoding ...)` / `addPrefixEncoding(...)`,
`itemEncoding(?Encoding)` **(3.2, all three)**.

### Example

`Example::create('admin')` (objectId = example name), `Example::ref(string $ref, ?string $objectId = null)`:
`summary(?string)`, `description(?string)`, `value(mixed)`, `externalValue(?string)`, `dataValue(mixed)` **(3.2)**,
`serializedValue(?string)` **(3.2)**. `value` excludes the three others; `serializedValue` excludes `externalValue`.

## Responses

### Response

The shortcuts set the objectId (status code) and the reason phrase as `description`: `ok()` 200, `created()` 201,
`accepted()` 202, `noContent()` 204, `movedPermanently()` 301, `found()` 302, `notModified()` 304,
`badRequest()` 400, `unauthorized()` 401, `forbidden()` 403, `notFound()` 404, `methodNotAllowed()` 405,
`conflict()` 409, `unprocessableEntity()` 422, `tooManyRequests()` 429, `internalServerError()` 500,
`serviceUnavailable()` 503. Others: `Response::status(418)` or `Response::status('4XX')` (no description: set one).
Default response: `Response::create('default')`. `Response::ref('NotFound')` refers to
`#/components/responses/NotFound`. In `Components::responses()`, rename: `Response::notFound()->objectId('NotFound')`.

| Method | Notes |
| --- | --- |
| `description(?string)` | Required; the library writes `""` when missing |
| `summary(?string)` | **(3.2)** |
| `headers(HeaderContract ...)`, `addHeaders(...)` | |
| `content(MediaTypeContract ...)`, `addContent(...)` | |
| `links(LinkContract ...)`, `addLinks(...)` | |

### Link

`Link::create('GetUser')` (objectId = link name), `Link::ref(string $ref, ?string $objectId = null)`:
`operationRef(?string)` or `operationId(?string)` (exclusive), `parameters(array<string, mixed>)`,
`addParameters(array<string, mixed>)` (values are runtime expressions such as `'$response.body#/id'`),
`requestBody(mixed)`, `description(?string)`, `server(?Server)`.

## Schemas

### Schema

Shortcuts set the type: `Schema::string()`, `integer()`, `number()`, `boolean()`, `array()`, `object()`, `null()`;
`Schema::create()` has no type. Their argument is the objectId (property or component name). `Schema::ref('User')`
writes `$ref: '#/components/schemas/User'` and stays a `Schema`: siblings (`description`, `readOnly`, `nullable`…) are
written, and converted for 3.0.

| Group | Methods |
| --- | --- |
| Type | `type(SchemaType\|string ...)` (no argument removes it), `addType(...)`, `nullable(?bool $nullable = true)`, `format(?string)` |
| Metadata | `title(?string)`, `description(?string)`, `default(mixed)`, `example(mixed)`, `examples(mixed ...)`, `addExamples(mixed ...)`, `deprecated(?bool = true)`, `readOnly(?bool = true)`, `writeOnly(?bool = true)`, `externalDocs(?ExternalDocumentation)`, `xml(?Xml)`, `comment(?string)` |
| Values | `enum(mixed ...)`, `addEnum(mixed ...)`, `const(mixed)` |
| Numbers | `minimum(int\|float\|null)`, `maximum(...)`, `exclusiveMinimum(int\|float\|null)`, `exclusiveMaximum(...)` (the bound itself, 3.1 style), `multipleOf(...)` |
| Strings | `minLength(?int)`, `maxLength(?int)`, `pattern(?string)`, `contentEncoding(?string)`, `contentMediaType(?string)`, `contentSchema(?SchemaContract)` |
| Arrays | `items(SchemaContract\|bool\|null)`, `prefixItems(SchemaContract ...)`, `addPrefixItems(...)`, `contains(SchemaContract\|bool\|null)`, `minContains(?int)`, `maxContains(?int)`, `minItems(?int)`, `maxItems(?int)`, `uniqueItems(?bool = true)`, `unevaluatedItems(SchemaContract\|bool\|null)` |
| Objects | `properties(SchemaContract ...)`, `addProperties(...)` (objectId = property name), `required(string ...)`, `addRequired(string ...)`, `additionalProperties(SchemaContract\|bool\|null)`, `patternProperties(SchemaContract ...)` / `addPatternProperties(...)` (objectId = pattern), `propertyNames(SchemaContract\|bool\|null)`, `unevaluatedProperties(SchemaContract\|bool\|null)`, `minProperties(?int)`, `maxProperties(?int)`, `dependentRequired(array<string, list<string>>)`, `addDependentRequired(array)`, `dependentSchemas(SchemaContract ...)` / `addDependentSchemas(...)` |
| Composition | `allOf(SchemaContract ...)`, `addAllOf(...)`, `anyOf(...)`, `addAnyOf(...)`, `oneOf(...)`, `addOneOf(...)`, `not(SchemaContract\|bool\|null)`, `if(...)`, `then(...)`, `else(...)`, `discriminator(?Discriminator)` |
| JSON Schema core | `schema(?string)` (`$schema`), `id(?string)` (`$id`), `anchor(?string)`, `dynamicAnchor(?string)`, `dynamicRef(?string)`, `defs(SchemaContract ...)` / `addDefs(...)` (`$defs`) |

Notes: a type listed twice throws; `enum` and `required` are not deduplicated. `examples()` takes values (a JSON
Schema list), unlike the `examples()` of `Parameter` and `MediaType` which take `Example` objects.

### Discriminator

`Discriminator::create()`: `propertyName(?string)` (required), `mapping(array<string, string>)`,
`addMapping(array<string, string>)` (value → schema name or reference), `defaultMapping(?string)` **(3.2)**.

### Xml

`Xml::create()`: `name(?string)`, `namespace(?string)`, `prefix(?string)`, `attribute(?bool = true)`,
`wrapped(?bool = true)`, `nodeType(XmlNodeType|string|null)` **(3.2)**, exclusive with `attribute` and `wrapped`.

## Components and references

### Components

`Components::create()`. Every section is a map keyed by the objectId of its entries (the component name):

`schemas(SchemaContract ...)` / `addSchemas`, `responses(ResponseContract ...)` / `addResponses`,
`parameters(ParameterContract ...)` / `addParameters`, `examples(ExampleContract ...)` / `addExamples`,
`requestBodies(RequestBodyContract ...)` / `addRequestBodies`, `headers(HeaderContract ...)` / `addHeaders`,
`securitySchemes(SecuritySchemeContract ...)` / `addSecuritySchemes`, `links(LinkContract ...)` / `addLinks`,
`callbacks(CallbackContract ...)` / `addCallbacks`, `pathItems(PathItemContract ...)` / `addPathItems` **(3.1+)**,
`mediaTypes(MediaTypeContract ...)` / `addMediaTypes` **(3.2)**.

### Reference

A `$ref` object, accepted wherever the specification allows "X or Reference Object".

| Method | Notes |
| --- | --- |
| `static to(string $ref, ?string $objectId = null)` | Any reference: `Reference::to('#/components/responses/NotFound', '404')` |
| `static for(SpecObject $component)` | Reference to a component object, from its class and objectId |
| `ref(?string)` | |
| `summary(?string)`, `description(?string)` | **(3.1+)** override the target's |

The `::ref()` shortcuts of each object are simpler: `Response::ref('NotFound')`.

## Security

### SecurityScheme

Shortcuts set the type; their argument is the objectId (the scheme name in `Components::securitySchemes()`):
`SecurityScheme::apiKey('key')`, `http('bearer')`, `oauth2('oauth')`, `openIdConnect('oidc')`, `mutualTLS('mtls')`
**(3.1+)**. `SecurityScheme::ref(string $ref, ?string $objectId = null)`.

| Method | Used by |
| --- | --- |
| `type(SecuritySchemeType\|string\|null)` | all (set by the shortcuts) |
| `description(?string)` | all |
| `name(?string)`, `in(ApiKeyLocation\|string\|null)` | `apiKey`: header, query or cookie name |
| `scheme(?string)`, `bearerFormat(?string)` | `http`: `bearer`, `basic`…; `JWT` |
| `flows(?OAuthFlows)` | `oauth2` |
| `oauth2MetadataUrl(?string)` | `oauth2` **(3.2)** |
| `openIdConnectUrl(?string)` | `openIdConnect` |
| `deprecated(?bool $deprecated = true)` | **(3.2)** |

### OAuthFlows and OAuthFlow

`OAuthFlows::create()`: `implicit(?OAuthFlow)`, `password(?OAuthFlow)`, `clientCredentials(?OAuthFlow)`,
`authorizationCode(?OAuthFlow)`, `deviceAuthorization(?OAuthFlow)` **(3.2)**.

`OAuthFlow::create()`: `authorizationUrl(?string)`, `tokenUrl(?string)`, `refreshUrl(?string)`,
`deviceAuthorizationUrl(?string)` **(3.2)**, `scopes(array<string, string>)`, `addScopes(array<string, string>)`
(scope → description; `[]` when none).

### SecurityRequirement

`SecurityRequirement::create()->schemes(['bearer' => []])`: `schemes(array<string, list<string>>)`,
`addSchemes(array<string, list<string>>)`; scheme name → required scopes (`[]` when none). The schemes of one
requirement all apply together; several requirements in `security()` are alternatives. An empty
`SecurityRequirement::create()` writes `{}`: authentication is optional.

## Enums (`Ark4ne\OpenApiWriter\Enums`)

| Enum | Cases |
| --- | --- |
| `Version` | `V3_0_0`, `V3_0_1`, `V3_0_2`, `V3_0_3`, `V3_0_4`, `V3_1_0`, `V3_1_1`, `V3_1_2`, `V3_2_0` |
| `Strictness` | `Strict` (default), `Lenient` |
| `VendorExtensions` | `None` (default), `Redoc` (`x-webhooks` before 3.1, `x-tagGroups` before 3.2; [versions.md](versions.md)) |
| `SchemaType` | `String`, `Number`, `Integer`, `Boolean`, `Array`, `Object`, `Null` |
| `ParameterLocation` | `Query`, `Header`, `Path`, `Cookie`, `Querystring` |
| `ParameterStyle` | `Matrix`, `Label`, `Simple`, `Form`, `SpaceDelimited`, `PipeDelimited`, `DeepObject`, `Cookie` |
| `SecuritySchemeType` | `ApiKey`, `Http`, `OAuth2`, `OpenIdConnect`, `MutualTLS` |
| `ApiKeyLocation` | `Query`, `Header`, `Cookie` |
| `HttpMethod` | `Get`, `Put`, `Post`, `Delete`, `Options`, `Head`, `Patch`, `Trace`, `Query` |
| `XmlNodeType` | `Element`, `Attribute`, `Text`, `Cdata`, `None` |

## Serialization (`Ark4ne\OpenApiWriter\Serialization`)

- `Serializer::for(Version|string|null $version = null, Strictness|string $strictness = Strictness::Strict,
  VendorExtensions|string $extensions = VendorExtensions::None)`, then `->serialize(SpecObject $object): SerializationResult`.
  Enums or their values (`'3.0.4'`, `'lenient'`, `'redoc'`).
- `SerializationResult`: `toArray(): array`, `toJson(int $flags = 0): string`, `toYaml(): string`,
  `diagnostics(): Diagnostics`.
- `Diagnostics`: iterable and countable; `all(): list<Diagnostic>`, `ofLevel(DiagnosticLevel $level)`, `isEmpty()`.
- `Diagnostic`: public `level` (`DiagnosticLevel::Converted`, `Dropped`, `Unsupported`), `pointer` (JSON Pointer),
  `message`; `(string) $diagnostic` gives `[dropped] /webhooks: webhooks needs 3.1.0`.
- `UnsupportedFeatureException::diagnostics()` lists the features of a strict failure.

## Validation (`Ark4ne\OpenApiWriter\Validation`)

- `Validator::create(?JsonSchemaValidator $schemaValidator = null)`: the built-in rules, plus the official schema
  when a schema validator is given (`new OpisJsonSchemaValidator()`, needs `opis/json-schema`; reuse the instance).
- `->validate(OpenApi $openApi, Version|string|null $version = null, VendorExtensions|string $extensions =
  VendorExtensions::None): Violations`; the version defaults to the declared one, else 3.1.2. Pass the vendor
  extensions the document is written with. What the version cannot express is reported, not thrown. Before 3.1, the
  rules read `x-webhooks` like `webhooks`.
- `->withRules(Rule ...$rules)` adds rules, `->withoutRules(string ...$ruleClasses)` removes them, `->rules()` lists
  them; both return a new validator.
- `Violations`: iterable and countable; `all()`, `errors()`, `warnings()`, `isValid(): bool` (no error; warnings
  allowed).
- `Violation`: public `pointer`, `rule`, `message`, `severity` (`Severity::Error`, `Severity::Warning`);
  `(string) $violation` gives `[error] /paths/~1pets/get/operationId: operationId "list" is already used (UniqueOperationIdRule)`.
- `Rule` interface, for custom rules: `check(array $document, Version $version, Violations $violations): void`, where
  `$document` is the written document (`toArray()` of the version). Add a `new Violation(JsonPointer $pointer,
  string $rule, string $message, Severity $severity = Severity::Error)` per problem, with
  `Ark4ne\OpenApiWriter\Support\JsonPointer::root()->append('info', 'summary')` as pointer.

Built-in rules (`Validation\Rules`): `UniqueOperationIdRule`, `PathParametersRule`, `UniqueParametersRule`,
`LocalReferencesRule`, `SecurityRequirementsRule`, `DeclaredTagsRule` (warning), `TagParentsRule` (3.2),
`EquivalentPathsRule`, `NonEmptyDocumentRule` (warning), `ValueTypeRule` (warning).
