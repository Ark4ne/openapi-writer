# Recipes

Each recipe is complete and runs as is (the library's test suite runs them, and validates every document they build
against the official schema and the rules). Recipes build on the `$components` and `$info` of the first one.

## CRUD resource with shared components

Declare each reusable piece once in `Components`, refer to it by name everywhere else.

```php
use Ark4ne\OpenApiWriter\Objects\Components;
use Ark4ne\OpenApiWriter\Objects\Info;
use Ark4ne\OpenApiWriter\Objects\MediaType;
use Ark4ne\OpenApiWriter\Objects\OpenApi;
use Ark4ne\OpenApiWriter\Objects\Operation;
use Ark4ne\OpenApiWriter\Objects\Parameter;
use Ark4ne\OpenApiWriter\Objects\PathItem;
use Ark4ne\OpenApiWriter\Objects\RequestBody;
use Ark4ne\OpenApiWriter\Objects\Response;
use Ark4ne\OpenApiWriter\Objects\Schema;
use Ark4ne\OpenApiWriter\Objects\Server;
use Ark4ne\OpenApiWriter\Objects\Tag;

$info = Info::create()->title('Book API')->version('1.0.0');

$components = Components::create()
    ->schemas(
        Schema::object('Book')
            ->properties(
                Schema::integer('id')->format('int64')->readOnly(),
                Schema::string('title')->minLength(1)->maxLength(255),
                Schema::string('isbn')->pattern('^[0-9-]{10,17}$'),
                Schema::string('published_at')->format('date')->nullable(),
            )
            ->required('id', 'title'),
        Schema::object('Error')
            ->properties(Schema::string('message'), Schema::object('errors')->additionalProperties(Schema::array()->items(Schema::string())))
            ->required('message'),
    )
    ->parameters(Parameter::path('book', 'BookId')->description('Book id')->schema(Schema::integer()->format('int64')))
    ->requestBodies(RequestBody::create('Book')->required()->content(MediaType::json()->schema(Schema::ref('Book'))))
    ->responses(
        Response::notFound()->objectId('NotFound')->content(MediaType::json()->schema(Schema::ref('Error'))),
        Response::unprocessableEntity()->objectId('ValidationError')->content(MediaType::json()->schema(Schema::ref('Error'))),
    );

$book = Response::ok()->content(MediaType::json()->schema(Schema::ref('Book')));

$crudApi = OpenApi::create()
    ->info($info)
    ->servers(Server::create()->url('https://api.example.com/v1'))
    ->tags(Tag::create()->name('books'))
    ->paths(
        PathItem::create('/books')->operations(
            Operation::get()->operationId('books.index')->tags('books')
                ->responses(Response::ok()->content(MediaType::json()->schema(Schema::array()->items(Schema::ref('Book'))))),
            Operation::post()->operationId('books.store')->tags('books')
                ->requestBody(RequestBody::ref('Book'))
                ->responses(
                    Response::created()->content(MediaType::json()->schema(Schema::ref('Book'))),
                    Response::ref('ValidationError', '422'), // the objectId is the status code
                ),
        ),
        PathItem::create('/books/{book}')
            ->parameters(Parameter::ref('BookId'))
            ->operations(
                Operation::get()->operationId('books.show')->tags('books')->responses($book, Response::ref('NotFound', '404')),
                Operation::put()->operationId('books.update')->tags('books')
                    ->requestBody(RequestBody::ref('Book'))
                    ->responses($book, Response::ref('NotFound', '404'), Response::ref('ValidationError', '422')),
                Operation::delete()->operationId('books.destroy')->tags('books')
                    ->responses(Response::noContent(), Response::ref('NotFound', '404')),
            ),
    )
    ->components($components);
```

## Building a document step by step

Objects are immutable: reassign on every step. Loops over routes or models usually look like this.

```php
use Ark4ne\OpenApiWriter\Objects\Info;
use Ark4ne\OpenApiWriter\Objects\OpenApi;
use Ark4ne\OpenApiWriter\Objects\Operation;
use Ark4ne\OpenApiWriter\Objects\PathItem;
use Ark4ne\OpenApiWriter\Objects\Response;

$routes = [
    ['GET', '/health', 'health.show'],
    ['GET', '/status', 'status.show'],
    ['POST', '/status', 'status.update'],
];

// Group the operations by path first: a path can be added only once.
$operationsByPath = [];

foreach ($routes as [$method, $uri, $name]) {
    $operationsByPath[$uri][] = Operation::create(strtolower($method))->operationId($name)->responses(Response::noContent());
}

$loopApi = OpenApi::create()->info(Info::create()->title('Status API')->version('1.0.0'))->paths();

foreach ($operationsByPath as $uri => $operations) {
    $loopApi = $loopApi->addPaths(PathItem::create($uri)->operations(...$operations)); // reassign!
}
```

## Pagination envelope

A generic envelope composed with `allOf`, the item type given per endpoint.

```php
use Ark4ne\OpenApiWriter\Objects\MediaType;
use Ark4ne\OpenApiWriter\Objects\OpenApi;
use Ark4ne\OpenApiWriter\Objects\Operation;
use Ark4ne\OpenApiWriter\Objects\Parameter;
use Ark4ne\OpenApiWriter\Objects\PathItem;
use Ark4ne\OpenApiWriter\Objects\Response;
use Ark4ne\OpenApiWriter\Objects\Schema;

$paginated = static fn(string $item): Schema => Schema::create()->allOf(
    Schema::ref('Page'),
    Schema::object()->properties(Schema::array('data')->items(Schema::ref($item)))->required('data'),
);

$paginationApi = OpenApi::create()
    ->info($info)
    ->paths(PathItem::create('/books')->operations(
        Operation::get()->operationId('books.index')
            ->parameters(
                Parameter::query('page')->schema(Schema::integer()->minimum(1)->default(1)),
                Parameter::query('per_page')->schema(Schema::integer()->minimum(1)->maximum(100)->default(15)),
            )
            ->responses(Response::ok()->content(MediaType::json()->schema($paginated('Book')))),
    ))
    ->components($components->addSchemas(
        Schema::object('Page')
            ->properties(Schema::integer('current_page'), Schema::integer('last_page'), Schema::integer('total'))
            ->required('current_page', 'last_page', 'total'),
    ));
```

## Enums, nullable and polymorphism

```php
use Ark4ne\OpenApiWriter\Objects\Discriminator;
use Ark4ne\OpenApiWriter\Objects\Schema;

$status = Schema::string('status')->enum('draft', 'published', 'archived')->default('draft');

// Nullable: describe it once, every version gets its own form.
$deletedAt = Schema::string('deleted_at')->format('date-time')->nullable();
$owner = Schema::ref('User', 'owner')->nullable(); // a nullable reference

// oneOf with a discriminator: each variant declares the discriminator property.
$payment = Schema::create('Payment')
    ->oneOf(Schema::ref('CardPayment'), Schema::ref('TransferPayment'))
    ->discriminator(Discriminator::create()->propertyName('method')->mapping([
        'card' => '#/components/schemas/CardPayment',
        'transfer' => '#/components/schemas/TransferPayment',
    ]));

$cardPayment = Schema::object('CardPayment')
    ->properties(Schema::string('method')->const('card'), Schema::string('last4')->pattern('^[0-9]{4}$'))
    ->required('method', 'last4');
$transferPayment = Schema::object('TransferPayment')
    ->properties(Schema::string('method')->const('transfer'), Schema::string('iban'))
    ->required('method', 'iban');

$schemasApi = OpenApi::create()->info($info)->paths(
    PathItem::create('/payments')->operations(
        Operation::post()->operationId('payments.store')
            ->requestBody(RequestBody::create()->required()->content(MediaType::json()->schema(Schema::ref('Payment'))))
            ->responses(Response::created()->content(MediaType::json()->schema(
                Schema::object()->properties($status, $deletedAt, $owner, Schema::ref('Payment', 'payment')),
            ))),
    ),
)->components(Components::create()->schemas(
    $payment,
    $cardPayment,
    $transferPayment,
    Schema::object('User')->properties(Schema::string('name')),
));
```

## File upload and download

```php
use Ark4ne\OpenApiWriter\Objects\Encoding;
use Ark4ne\OpenApiWriter\Objects\Header;
use Ark4ne\OpenApiWriter\Objects\MediaType;
use Ark4ne\OpenApiWriter\Objects\Operation;
use Ark4ne\OpenApiWriter\Objects\PathItem;
use Ark4ne\OpenApiWriter\Objects\RequestBody;
use Ark4ne\OpenApiWriter\Objects\Response;
use Ark4ne\OpenApiWriter\Objects\Schema;

$filesApi = OpenApi::create()->info($info)->paths(
    PathItem::create('/files')->operations(
        Operation::post()->operationId('files.store')
            ->requestBody(RequestBody::create()->required()->content(
                MediaType::multipart()
                    ->schema(Schema::object()
                        ->properties(
                            Schema::string('name'),
                            Schema::string('file')->contentMediaType('application/octet-stream'), // 3.0: format: binary
                        )
                        ->required('file'))
                    ->encoding(Encoding::create('file')->contentType('image/png, image/jpeg')),
            ))
            ->responses(Response::created()),
    ),
    PathItem::create('/files/{file}')
        ->parameters(Parameter::path('file')->schema(Schema::string()->format('uuid')))
        ->operations(
            Operation::get()->operationId('files.download')->responses(
                Response::ok()
                    ->headers(Header::create('Content-Disposition')->schema(Schema::string()))
                    ->content(MediaType::of('application/octet-stream')->schema(Schema::string()->contentMediaType('application/octet-stream'))),
            ),
        ),
);
```

## Authentication

Declare the schemes in `Components::securitySchemes()`, require them globally with `OpenApi::security()`, and override
per operation: `security()` with no argument makes an operation public, an empty `SecurityRequirement` makes
authentication optional.

```php
use Ark4ne\OpenApiWriter\Objects\Components;
use Ark4ne\OpenApiWriter\Objects\OAuthFlow;
use Ark4ne\OpenApiWriter\Objects\OAuthFlows;
use Ark4ne\OpenApiWriter\Objects\Operation;
use Ark4ne\OpenApiWriter\Objects\PathItem;
use Ark4ne\OpenApiWriter\Objects\Response;
use Ark4ne\OpenApiWriter\Objects\SecurityRequirement;
use Ark4ne\OpenApiWriter\Objects\SecurityScheme;

$authApi = OpenApi::create()
    ->info($info)
    ->components(Components::create()->securitySchemes(
        SecurityScheme::http('bearer')->scheme('bearer')->bearerFormat('JWT'),
        SecurityScheme::apiKey('apiKey')->name('X-API-Key')->in('header'),
        SecurityScheme::oauth2('oauth')->flows(OAuthFlows::create()->authorizationCode(
            OAuthFlow::create()
                ->authorizationUrl('https://auth.example.com/authorize')
                ->tokenUrl('https://auth.example.com/token')
                ->scopes(['books:read' => 'Read books', 'books:write' => 'Write books']),
        )),
    ))
    // Bearer token OR API key, on every operation by default.
    ->security(
        SecurityRequirement::create()->schemes(['bearer' => []]),
        SecurityRequirement::create()->schemes(['apiKey' => []]),
    )
    ->paths(
        PathItem::create('/login')->operations(
            Operation::post()->operationId('auth.login')->security()->responses(Response::ok()), // public
        ),
        PathItem::create('/catalog')->operations(
            Operation::get()->operationId('catalog.index') // optional authentication
                ->security(SecurityRequirement::create()->schemes(['bearer' => []]), SecurityRequirement::create())
                ->responses(Response::ok()),
        ),
        PathItem::create('/admin/books')->operations(
            Operation::delete()->operationId('admin.books.purge') // OAuth2 with a scope
                ->security(SecurityRequirement::create()->schemes(['oauth' => ['books:write']]))
                ->responses(Response::noContent()),
        ),
    );
```

## Headers, examples and links

```php
use Ark4ne\OpenApiWriter\Objects\Example;
use Ark4ne\OpenApiWriter\Objects\Header;
use Ark4ne\OpenApiWriter\Objects\Link;
use Ark4ne\OpenApiWriter\Objects\MediaType;
use Ark4ne\OpenApiWriter\Objects\Operation;
use Ark4ne\OpenApiWriter\Objects\PathItem;
use Ark4ne\OpenApiWriter\Objects\Response;
use Ark4ne\OpenApiWriter\Objects\Schema;

$detailsApi = OpenApi::create()->info($info)->paths(
    PathItem::create('/books')->operations(
        Operation::post()->operationId('books.create')
            ->requestBody(RequestBody::create()->content(
                MediaType::json()
                    ->schema(Schema::ref('Book'))
                    ->examples(
                        Example::create('minimal')->summary('Title only')->value(['title' => 'Dune']),
                        Example::create('full')->value(['title' => 'Dune', 'isbn' => '978-0441013593']),
                    ),
            ))
            ->responses(
                Response::created()
                    ->headers(
                        Header::create('Location')->required()->schema(Schema::string()->format('uri')),
                        Header::create('X-RateLimit-Remaining')->schema(Schema::integer())->example(59),
                    )
                    ->content(MediaType::json()->schema(Schema::ref('Book')))
                    ->links(Link::create('GetBook')->operationId('books.show')->parameters(['book' => '$response.body#/id'])),
            ),
    ),
    PathItem::create('/books/{book}')->parameters(Parameter::ref('BookId'))->operations(
        Operation::get()->operationId('books.show')->responses(Response::ok()),
    ),
)->components($components);
```

## Webhooks and callbacks

```php
use Ark4ne\OpenApiWriter\Objects\Callback;
use Ark4ne\OpenApiWriter\Objects\MediaType;
use Ark4ne\OpenApiWriter\Objects\Operation;
use Ark4ne\OpenApiWriter\Objects\PathItem;
use Ark4ne\OpenApiWriter\Objects\RequestBody;
use Ark4ne\OpenApiWriter\Objects\Response;
use Ark4ne\OpenApiWriter\Objects\Schema;

$event = RequestBody::create()->required()->content(MediaType::json()->schema(Schema::ref('Book')));

$eventsApi = OpenApi::create()
    ->info($info)
    // Webhooks (3.1+): requests the API sends, outside any call. The objectId is the webhook name.
    ->webhooks(PathItem::create('bookPublished')->operations(
        Operation::post()->operationId('webhooks.bookPublished')->requestBody($event)->responses(Response::noContent()),
    ))
    // Callbacks: requests sent in reaction to a call, to a URL given in that call.
    ->paths(PathItem::create('/subscriptions')->operations(
        Operation::post()->operationId('subscriptions.store')
            ->requestBody(RequestBody::create()->required()->content(MediaType::json()->schema(
                Schema::object()->properties(Schema::string('callbackUrl')->format('uri'))->required('callbackUrl'),
            )))
            ->callbacks(Callback::create('bookPublished')->pathItems(
                PathItem::create('{$request.body#/callbackUrl}')->operations(
                    Operation::post()->requestBody($event)->responses(Response::noContent()),
                ),
            ))
            ->responses(Response::created()),
    ))
    ->components($components);
```

## OpenAPI 3.2 features

```php
use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Objects\MediaType;
use Ark4ne\OpenApiWriter\Objects\Operation;
use Ark4ne\OpenApiWriter\Objects\Parameter;
use Ark4ne\OpenApiWriter\Objects\PathItem;
use Ark4ne\OpenApiWriter\Objects\Response;
use Ark4ne\OpenApiWriter\Objects\Schema;
use Ark4ne\OpenApiWriter\Objects\Tag;

$modernApi = OpenApi::create()
    ->openapi(Version::V3_2_0)
    ->info($info)
    ->tags(
        Tag::create()->name('library')->summary('Library')->kind('nav'),
        Tag::create()->name('books')->parent('library'),
    )
    ->paths(PathItem::create('/books/search')->operations(
        // QUERY: a safe request with a body.
        Operation::query()->operationId('books.search')->tags('books')
            ->requestBody(RequestBody::create()->content(MediaType::json()->schema(Schema::object()->properties(Schema::string('title')))))
            ->responses(Response::ok()->content(
                // A stream of JSON lines, one Book per line.
                MediaType::of('application/jsonl')->itemSchema(Schema::ref('Book')),
            )),
        // Any other method goes to additionalOperations.
        Operation::create('COPY')->operationId('books.copy')->tags('books')->responses(Response::created()),
    ))
    ->components($components);
```

## Writing files

```php
use Ark4ne\OpenApiWriter\Enums\Version;

$directory = sys_get_temp_dir();

file_put_contents($directory . '/openapi.json', $crudApi->toJson(Version::V3_1_2, JSON_PRETTY_PRINT) . "\n");
file_put_contents($directory . '/openapi.yaml', $crudApi->toYaml(Version::V3_1_2)); // needs symfony/yaml
file_put_contents($directory . '/openapi-3.0.json', $crudApi->toJson(Version::V3_0_4, JSON_PRETTY_PRINT) . "\n");
```

## Validate in a test

Run it in the project's test suite: it catches what the type system cannot (duplicate `operationId`, missing path
parameter, broken `$ref`…), for the version you publish.

```php
use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Validation\OpisJsonSchemaValidator;
use Ark4ne\OpenApiWriter\Validation\Validator;

$validator = Validator::create(new OpisJsonSchemaValidator());

foreach ([Version::V3_0_4, Version::V3_1_2] as $version) {
    $violations = $validator->validate($crudApi, $version);
    $messages = array_map('strval', $violations->all()); // errors and warnings, one line each
    // In PHPUnit: self::assertSame([], $messages);
}
```

## Custom validation rule

```php
use Ark4ne\OpenApiWriter\Enums\Version;
use Ark4ne\OpenApiWriter\Support\JsonPointer;
use Ark4ne\OpenApiWriter\Validation\Rule;
use Ark4ne\OpenApiWriter\Validation\Severity;
use Ark4ne\OpenApiWriter\Validation\Validator;
use Ark4ne\OpenApiWriter\Validation\Violation;
use Ark4ne\OpenApiWriter\Validation\Violations;

// Every operation has a summary. $document is the written document, as arrays.
$summaryRule = new class implements Rule {
    public function check(array $document, Version $version, Violations $violations): void
    {
        foreach (is_array($document['paths'] ?? null) ? $document['paths'] : [] as $path => $pathItem) {
            foreach (['get', 'put', 'post', 'delete', 'patch'] as $method) {
                if (is_array($pathItem[$method] ?? null) && !isset($pathItem[$method]['summary'])) {
                    $violations->add(new Violation(
                        JsonPointer::root()->append('paths', (string) $path, $method),
                        'operation-summary',
                        'the operation has no summary',
                        Severity::Warning,
                    ));
                }
            }
        }
    }
};

$summaryWarnings = array_map('strval', Validator::create()->withRules($summaryRule)->validate($crudApi)->warnings());
// ['[warning] /paths/~1books/get: the operation has no summary (operation-summary)', …]
```
