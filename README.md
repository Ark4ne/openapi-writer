# OpenAPI Writer

[![CI](https://github.com/ark4ne/openapi-writer/actions/workflows/ci.yml/badge.svg?branch=main)](https://github.com/ark4ne/openapi-writer/actions/workflows/ci.yml)
[![Coverage](https://codecov.io/gh/ark4ne/openapi-writer/graph/badge.svg)](https://codecov.io/gh/ark4ne/openapi-writer)
[![Latest version](https://img.shields.io/packagist/v/ark4ne/openapi-writer)](https://packagist.org/packages/ark4ne/openapi-writer)
[![PHP](https://img.shields.io/packagist/dependency-v/ark4ne/openapi-writer/php)](https://packagist.org/packages/ark4ne/openapi-writer)
[![License](https://img.shields.io/packagist/l/ark4ne/openapi-writer)](LICENSE)

Framework-agnostic, fluent and immutable PHP builder for **OpenAPI 3.0, 3.1 and 3.2** documents.

- One PHP object per object of the specification, one method per field, named exactly like the specification.
- One model for every version: the target version (3.0.0 → 3.0.4, 3.1.0 → 3.1.2, 3.2.0) is chosen when writing.
- Strict by default: a feature the target version cannot express throws; a lenient mode drops it and reports it.
- Validation against the official JSON schemas and semantic rules (unique `operationId`, path parameters…).
- No runtime dependency besides `ext-json`. PHP 8.2+.

## Installation

```bash
composer require ark4ne/openapi-writer
```

Optional packages:

| Package | Needed for |
| --- | --- |
| `symfony/yaml` | `toYaml()` |
| `opis/json-schema` | `OpisJsonSchemaValidator`, the validation against the official schemas |

## Quick start

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

$user = Schema::object('User')
    ->properties(
        Schema::integer('id')->format('int64')->readOnly(),
        Schema::string('email')->format('email'),
        Schema::string('nickname')->nullable(),
    )
    ->required('id', 'email');

$openApi = OpenApi::create()
    ->info(Info::create()->title('Users API')->version('1.0.0'))
    ->paths(
        PathItem::create('/users/{id}')->operations(
            Operation::get()
                ->operationId('users.show')
                ->parameters(Parameter::path('id')->required()->schema(Schema::integer()))
                ->responses(
                    Response::ok()->content(MediaType::json()->schema(Schema::ref('User'))),
                    Response::notFound(),
                ),
        ),
    )
    ->components(Components::create()->schemas($user));

$json = $openApi->toJson(Version::V3_1_2, JSON_PRETTY_PRINT);
```

Every object is immutable: each method returns a modified copy, so objects can be shared and derived freely.
The first argument of `create()` and of the shortcuts (`Schema::string('email')`, `PathItem::create('/users')`) is
the key of the object in its parent: property name, component name, path, status code.

## Building a document

### Document

```php
use Ark4ne\OpenApiWriter\Objects\Contact;
use Ark4ne\OpenApiWriter\Objects\ExternalDocumentation;
use Ark4ne\OpenApiWriter\Objects\Info;
use Ark4ne\OpenApiWriter\Objects\License;
use Ark4ne\OpenApiWriter\Objects\OpenApi;
use Ark4ne\OpenApiWriter\Objects\Server;
use Ark4ne\OpenApiWriter\Objects\ServerVariable;
use Ark4ne\OpenApiWriter\Objects\Tag;

$document = OpenApi::create()
    ->info(
        Info::create()
            ->title('Pet Store')
            ->version('2.1.0')
            ->description('Pets and their owners.')
            ->contact(Contact::create()->name('API team')->email('api@example.com'))
            ->license(License::create()->name('MIT')->url('https://opensource.org/license/mit')),
    )
    ->servers(
        Server::create()
            ->url('https://{region}.example.com/v2')
            ->variables(ServerVariable::create('region')->enum('eu', 'us')->default('eu')),
    )
    ->tags(Tag::create()->name('pets')->description('Everything about pets'))
    ->externalDocs(ExternalDocumentation::create()->url('https://docs.example.com'))
    ->x('api-id', 'pet-store'); // extensions: the `x-` prefix is added when missing
```

### Paths and operations

```php
use Ark4ne\OpenApiWriter\Objects\Operation;
use Ark4ne\OpenApiWriter\Objects\Parameter;
use Ark4ne\OpenApiWriter\Objects\PathItem;
use Ark4ne\OpenApiWriter\Objects\Response;
use Ark4ne\OpenApiWriter\Objects\Schema;

$pets = PathItem::create('/pets')
    ->summary('Pets')
    ->operations(
        Operation::get()->operationId('pets.index')->tags('pets')->responses(Response::ok()),
        Operation::post()->operationId('pets.store')->tags('pets')->responses(Response::created()),
    );

// Lists and maps have two methods: the field name replaces, `add` + the field name appends.
$document = $document->paths($pets)->addPaths(
    PathItem::create('/pets/{id}')->parameters(Parameter::path('id')->required()->schema(Schema::integer()))->operations(
        Operation::delete()->operationId('pets.destroy')->deprecated()->responses(Response::noContent()),
    ),
);
```

### Parameters

```php
use Ark4ne\OpenApiWriter\Enums\ParameterStyle;
use Ark4ne\OpenApiWriter\Objects\Parameter;
use Ark4ne\OpenApiWriter\Objects\Schema;

$id = Parameter::path('id')->required()->schema(Schema::integer()->format('int64'));
$page = Parameter::query('page')->description('Page number')->schema(Schema::integer()->minimum(1)->default(1));
$tags = Parameter::query('tags')->style(ParameterStyle::Form)->explode(false)->schema(Schema::array()->items(Schema::string()));
$tenant = Parameter::header('X-Tenant')->required()->schema(Schema::string());
$session = Parameter::cookie('session')->schema(Schema::string());
$shared = Parameter::ref('Page'); // `#/components/parameters/Page`
```

### Request bodies

```php
use Ark4ne\OpenApiWriter\Objects\Encoding;
use Ark4ne\OpenApiWriter\Objects\MediaType;
use Ark4ne\OpenApiWriter\Objects\RequestBody;
use Ark4ne\OpenApiWriter\Objects\Schema;

$body = RequestBody::create()
    ->required()
    ->content(
        MediaType::json()->schema(Schema::ref('NewPet')),
        MediaType::multipart()
            ->schema(Schema::object()->properties(Schema::string('name'), Schema::string('photo')->contentMediaType('image/png')))
            ->encoding(Encoding::create('photo')->contentType('image/png')),
    );
```

### Responses

```php
use Ark4ne\OpenApiWriter\Objects\Header;
use Ark4ne\OpenApiWriter\Objects\Link;
use Ark4ne\OpenApiWriter\Objects\MediaType;
use Ark4ne\OpenApiWriter\Objects\Response;
use Ark4ne\OpenApiWriter\Objects\Schema;

// Shortcuts set the status code and its reason phrase as description.
$created = Response::created()
    ->headers(Header::create('Location')->schema(Schema::string()->format('uri')))
    ->content(MediaType::json()->schema(Schema::ref('Pet')))
    ->links(Link::create('GetPet')->operationId('pets.show')->parameters(['id' => '$response.body#/id']));

$teapot = Response::status(418)->description('I am a teapot');
$fallback = Response::create('default')->description('Unexpected error'); // goes to `default` in Operation::responses()
$shared = Response::ref('NotFound'); // `#/components/responses/NotFound`
```

### Schemas

```php
use Ark4ne\OpenApiWriter\Objects\Discriminator;
use Ark4ne\OpenApiWriter\Objects\Schema;

$pet = Schema::object('Pet')
    ->properties(
        Schema::integer('id')->format('int64')->readOnly(),
        Schema::string('name')->minLength(1)->maxLength(100),
        Schema::string('status')->enum('available', 'sold'),
        Schema::string('birthday')->format('date')->nullable(),       // `type: [string, null]`, `nullable: true` in 3.0
        Schema::array('tags')->items(Schema::string())->uniqueItems(),
        Schema::object('metadata')->additionalProperties(Schema::string()),
    )
    ->required('id', 'name');

$animal = Schema::create('Animal')
    ->oneOf(Schema::ref('Cat'), Schema::ref('Dog'))
    ->discriminator(Discriminator::create()->propertyName('kind'));

$free = Schema::string('note')->example('Any value')->default(null); // free values write a real `null`
```

### Components and references

```php
use Ark4ne\OpenApiWriter\Objects\Components;
use Ark4ne\OpenApiWriter\Objects\Parameter;
use Ark4ne\OpenApiWriter\Objects\Response;
use Ark4ne\OpenApiWriter\Objects\Schema;

$components = Components::create()
    ->schemas(
        $pet,
        $animal,
        Schema::object('Cat')->properties(Schema::string('kind'), Schema::boolean('indoor')),
        Schema::object('Dog')->properties(Schema::string('kind'), Schema::boolean('trained')),
        Schema::object('Error')->properties(Schema::string('message')),
    )
    ->parameters(Parameter::query('page', 'Page')->schema(Schema::integer()->minimum(1)))
    ->responses(Response::notFound()->objectId('NotFound'));

// `::ref()` builds the path of the component: Schema::ref('Pet') is `#/components/schemas/Pet`.
// A value containing `#` or `/` is written as is.
$document = $document->components($components);
```

### Security

```php
use Ark4ne\OpenApiWriter\Objects\OAuthFlow;
use Ark4ne\OpenApiWriter\Objects\OAuthFlows;
use Ark4ne\OpenApiWriter\Objects\SecurityRequirement;
use Ark4ne\OpenApiWriter\Objects\SecurityScheme;

$document = $document
    ->components(
        $components->securitySchemes(
            SecurityScheme::http('bearer')->scheme('bearer')->bearerFormat('JWT'),
            SecurityScheme::apiKey('key')->name('X-API-Key')->in('header'),
            SecurityScheme::oauth2('oauth')->flows(
                OAuthFlows::create()->authorizationCode(
                    OAuthFlow::create()
                        ->authorizationUrl('https://example.com/oauth/authorize')
                        ->tokenUrl('https://example.com/oauth/token')
                        ->scopes(['pets:read' => 'Read pets', 'pets:write' => 'Write pets']),
                ),
            ),
        ),
    )
    // Each requirement is one alternative; the schemes of one requirement all apply.
    ->security(
        SecurityRequirement::create()->schemes(['bearer' => []]),
        SecurityRequirement::create()->schemes(['oauth' => ['pets:read']]),
    );
```

## Writing a document

### Choosing the version

The same document is written for any supported version. The default is 3.1.2, or the version given to
`OpenApi::openapi()`.

```php
use Ark4ne\OpenApiWriter\Enums\Version;

$v30 = $document->toJson(Version::V3_0_4);               // nullable: true, exclusiveMinimum: true…
$v31 = $document->toJson('3.1.2', JSON_PRETTY_PRINT);    // a version string works too
$v32 = $document->toArray(Version::V3_2_0);              // PHP array; empty objects are \stdClass
$yaml = $document->toYaml(Version::V3_1_2);              // needs symfony/yaml
```

Each difference between versions is converted when it can be (`nullable`, `exclusiveMinimum`, `examples` of a
schema, `const`…). A feature the target version cannot express (webhooks in 3.0, `Tag.parent` before 3.2…)
throws one `UnsupportedFeatureException` listing all of them.

### Lenient mode

```php
use Ark4ne\OpenApiWriter\Enums\Strictness;
use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Exceptions\UnsupportedFeatureException;
use Ark4ne\OpenApiWriter\Objects\Operation;
use Ark4ne\OpenApiWriter\Objects\PathItem;
use Ark4ne\OpenApiWriter\Objects\Response;
use Ark4ne\OpenApiWriter\Serialization\Serializer;

$withWebhook = $document->webhooks(
    PathItem::create('petAdopted')->operations(Operation::post()->responses(Response::ok())),
);

try {
    $withWebhook->toJson(Version::V3_0_4);
} catch (UnsupportedFeatureException $exception) {
    $reasons = array_map('strval', $exception->diagnostics()->all());
    // ['[unsupported] /webhooks: webhooks needs 3.1.0']
}

// Lenient mode drops what the version cannot express. The diagnostics list each removal and each conversion:
// ['[dropped] /webhooks: webhooks needs 3.1.0',
//  '[converted] /components/schemas/Pet/properties/birthday: nullable written as nullable: true']
$result = Serializer::for(Version::V3_0_4, Strictness::Lenient)->serialize($withWebhook);
$json30 = $result->toJson();
$diagnostics = array_map('strval', $result->diagnostics()->all());
```

## Validation

```php
use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Validation\OpisJsonSchemaValidator;
use Ark4ne\OpenApiWriter\Validation\Rules\NonEmptyDocumentRule;
use Ark4ne\OpenApiWriter\Validation\Validator;

$validator = Validator::create(new OpisJsonSchemaValidator()); // without argument: semantic rules only
$violations = $validator->validate($document, Version::V3_1_2);

if (!$violations->isValid()) {        // warnings alone keep a document valid
    foreach ($violations->errors() as $violation) {
        $message = (string) $violation; // "[error] /paths/~1pets/get/operationId: … (UniqueOperationIdRule)"
    }
}

// Rules can be removed, or added (any class implementing Rule).
$relaxed = $validator->withoutRules(NonEmptyDocumentRule::class);
```

The validator writes the document in lenient mode and reports what the version cannot express, then checks the
official schema of the version and these rules:

| Rule | Checks |
| --- | --- |
| `UniqueOperationIdRule` | Every `operationId` is unique |
| `PathParametersRule` | Each `{name}` of a path has a required `in: path` parameter |
| `UniqueParametersRule` | No two parameters of an operation share a name and a location |
| `LocalReferencesRule` | Every local `$ref`, `operationRef` and discriminator mapping resolves |
| `SecurityRequirementsRule` | Security requirements name declared schemes, with OAuth2 scopes that exist |
| `DeclaredTagsRule` | Tags of operations are declared at the root (warning) |
| `TagParentsRule` | `Tag.parent` names a declared tag, without cycle (3.2) |
| `EquivalentPathsRule` | No two paths differ only by the names of their variables |
| `NonEmptyDocumentRule` | The document declares at least one operation (warning) |
| `ValueTypeRule` | `default` and `example` of a schema match its type (warning) |

## Supported versions

| Version | Notes |
| --- | --- |
| 3.0.0 → 3.0.4 | Converted from the 3.1 model (`nullable`, boolean `exclusiveMinimum`…), unsupported features rejected |
| 3.1.0 → 3.1.2 | Default target (3.1.2) |
| 3.2.0 | `query` method, `additionalOperations`, `Tag.parent`/`kind`, `querystring` parameters, streaming media types… |

Within a line, the output does not depend on the patch version, except for the `openapi` field.

## For AI coding agents

The package ships an agent skill in [`skills/openapi-writer/`](skills/openapi-writer/SKILL.md): the rules that avoid
the usual mistakes, the full API reference, tested recipes and the version matrix. Its examples run in the test suite,
so it matches the installed version.

With Claude Code, link it into the project that uses the library:

```bash
mkdir -p .claude/skills
ln -s ../../vendor/ark4ne/openapi-writer/skills/openapi-writer .claude/skills/openapi-writer
```

With another agent, point its instructions file (`AGENTS.md`…) to
`vendor/ark4ne/openapi-writer/skills/openapi-writer/SKILL.md`.

## Contributing

See [CONTRIBUTING.md](CONTRIBUTING.md). The design is documented in [docs/architecture.md](docs/architecture.md).

## License

MIT, see [LICENSE](LICENSE).
