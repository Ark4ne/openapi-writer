<?php

declare(strict_types=1);

namespace Ark4ne\OpenApiWriter\Tests\Support;

use Ark4ne\OpenApiWriter\Objects\Callback;
use Ark4ne\OpenApiWriter\Objects\Components;
use Ark4ne\OpenApiWriter\Objects\Contact;
use Ark4ne\OpenApiWriter\Objects\Example;
use Ark4ne\OpenApiWriter\Objects\ExternalDocumentation;
use Ark4ne\OpenApiWriter\Objects\Header;
use Ark4ne\OpenApiWriter\Objects\Info;
use Ark4ne\OpenApiWriter\Objects\License;
use Ark4ne\OpenApiWriter\Objects\Link;
use Ark4ne\OpenApiWriter\Objects\MediaType;
use Ark4ne\OpenApiWriter\Objects\OAuthFlow;
use Ark4ne\OpenApiWriter\Objects\OAuthFlows;
use Ark4ne\OpenApiWriter\Objects\OpenApi;
use Ark4ne\OpenApiWriter\Objects\Operation;
use Ark4ne\OpenApiWriter\Objects\Parameter;
use Ark4ne\OpenApiWriter\Objects\PathItem;
use Ark4ne\OpenApiWriter\Objects\RequestBody;
use Ark4ne\OpenApiWriter\Objects\Response;
use Ark4ne\OpenApiWriter\Objects\Schema;
use Ark4ne\OpenApiWriter\Objects\SecurityRequirement;
use Ark4ne\OpenApiWriter\Objects\SecurityScheme;
use Ark4ne\OpenApiWriter\Objects\Server;
use Ark4ne\OpenApiWriter\Objects\ServerVariable;
use Ark4ne\OpenApiWriter\Objects\Tag;

/**
 * Object Mother of the reference documents, shared by the Conformance and Validation suites.
 *
 * The official examples of the OpenAPI Initiative are rebuilt with the API; their expected output is the official
 * file with the keys in the output order of the library (`tests/fixtures/<line>/conformance/<example>.json`).
 */
final class Documents
{
    /**
     * The smallest document of the compatibility matrix: a title, a version and no path.
     */
    public static function compatibility(): OpenApi
    {
        return OpenApi::create()->info(Info::create()->title('Compatibility')->version('1.0.0'))->paths();
    }

    /**
     * `petstore` (OpenAPI 3.0.0), from https://github.com/OAI/learn.openapis.org/tree/main/examples.
     */
    public static function petstore(): OpenApi
    {
        return OpenApi::create()
            ->info(
                Info::create()
                    ->version('1.0.0')
                    ->title('Swagger Petstore')
                    ->license(License::create()->name('MIT')),
            )
            ->servers(Server::create()->url('http://petstore.swagger.io/v1'))
            ->paths(
                PathItem::create('/pets')
                    ->operations(
                        Operation::get()
                            ->summary('List all pets')
                            ->operationId('listPets')
                            ->tags('pets')
                            ->parameters(
                                Parameter::query('limit')
                                    ->description('How many items to return at one time (max 100)')
                                    ->required(false)
                                    ->schema(Schema::integer()->maximum(100)->format('int32')),
                            )
                            ->responses(
                                Response::status(200)
                                    ->description('A paged array of pets')
                                    ->headers(
                                        Header::create('x-next')
                                            ->description('A link to the next page of responses')
                                            ->schema(Schema::string()),
                                    )
                                    ->content(MediaType::json()->schema(Schema::ref('Pets'))),
                                Response::create('default')
                                    ->description('unexpected error')
                                    ->content(MediaType::json()->schema(Schema::ref('Error'))),
                            ),
                        Operation::post()
                            ->summary('Create a pet')
                            ->operationId('createPets')
                            ->tags('pets')
                            ->requestBody(
                                RequestBody::create()->content(MediaType::json()->schema(Schema::ref('Pet')))->required(true),
                            )
                            ->responses(
                                Response::status(201)->description('Null response'),
                                Response::create('default')
                                    ->description('unexpected error')
                                    ->content(MediaType::json()->schema(Schema::ref('Error'))),
                            ),
                    ),
                PathItem::create('/pets/{petId}')
                    ->operations(
                        Operation::get()
                            ->summary('Info for a specific pet')
                            ->operationId('showPetById')
                            ->tags('pets')
                            ->parameters(
                                Parameter::path('petId')->description('The id of the pet to retrieve')->schema(Schema::string()),
                            )
                            ->responses(
                                Response::status(200)
                                    ->description('Expected response to a valid request')
                                    ->content(MediaType::json()->schema(Schema::ref('Pet'))),
                                Response::create('default')
                                    ->description('unexpected error')
                                    ->content(MediaType::json()->schema(Schema::ref('Error'))),
                            ),
                    ),
            )
            ->components(
                Components::create()
                    ->schemas(
                        Schema::object('Pet')
                            ->required('id', 'name')
                            ->properties(
                                Schema::integer('id')->format('int64'),
                                Schema::string('name'),
                                Schema::string('tag'),
                            ),
                        Schema::array('Pets')->maxItems(100)->items(Schema::ref('Pet')),
                        Schema::object('Error')
                            ->required('code', 'message')
                            ->properties(Schema::integer('code')->format('int32'), Schema::string('message')),
                    ),
            );
    }

    /**
     * `petstore-expanded` (OpenAPI 3.0.0), from https://github.com/OAI/learn.openapis.org/tree/main/examples.
     */
    public static function petstoreExpanded(): OpenApi
    {
        return OpenApi::create()
            ->info(
                Info::create()
                    ->version('1.0.0')
                    ->title('Swagger Petstore')
                    ->description(
                        'A sample API that uses a petstore as an example to demonstrate features in the OpenAPI 3.0 specification',
                    )
                    ->termsOfService('http://swagger.io/terms/')
                    ->contact(
                        Contact::create()->name('Swagger API Team')->email('apiteam@swagger.io')->url('http://swagger.io'),
                    )
                    ->license(
                        License::create()->name('Apache 2.0')->url('https://www.apache.org/licenses/LICENSE-2.0.html'),
                    ),
            )
            ->servers(Server::create()->url('https://petstore.swagger.io/v2'))
            ->paths(
                PathItem::create('/pets')
                    ->operations(
                        Operation::get()
                            ->description(
                                "Returns all pets from the system that the user has access to\nNam sed condimentum est. Maecenas tempor sagittis sapien, nec rhoncus sem sagittis sit amet. Aenean at gravida augue, ac iaculis sem. Curabitur odio lorem, ornare eget elementum nec, cursus id lectus. Duis mi turpis, pulvinar ac eros ac, tincidunt varius justo. In hac habitasse platea dictumst. Integer at adipiscing ante, a sagittis ligula. Aenean pharetra tempor ante molestie imperdiet. Vivamus id aliquam diam. Cras quis velit non tortor eleifend sagittis. Praesent at enim pharetra urna volutpat venenatis eget eget mauris. In eleifend fermentum facilisis. Praesent enim enim, gravida ac sodales sed, placerat id erat. Suspendisse lacus dolor, consectetur non augue vel, vehicula interdum libero. Morbi euismod sagittis libero sed lacinia.\n\nSed tempus felis lobortis leo pulvinar rutrum. Nam mattis velit nisl, eu condimentum ligula luctus nec. Phasellus semper velit eget aliquet faucibus. In a mattis elit. Phasellus vel urna viverra, condimentum lorem id, rhoncus nibh. Ut pellentesque posuere elementum. Sed a varius odio. Morbi rhoncus ligula libero, vel eleifend nunc tristique vitae. Fusce et sem dui. Aenean nec scelerisque tortor. Fusce malesuada accumsan magna vel tempus. Quisque mollis felis eu dolor tristique, sit amet auctor felis gravida. Sed libero lorem, molestie sed nisl in, accumsan tempor nisi. Fusce sollicitudin massa ut lacinia mattis. Sed vel eleifend lorem. Pellentesque vitae felis pretium, pulvinar elit eu, euismod sapien.\n",
                            )
                            ->operationId('findPets')
                            ->parameters(
                                Parameter::query('tags')
                                    ->description('tags to filter by')
                                    ->required(false)
                                    ->style('form')
                                    ->schema(Schema::array()->items(Schema::string())),
                                Parameter::query('limit')
                                    ->description('maximum number of results to return')
                                    ->required(false)
                                    ->schema(Schema::integer()->format('int32')),
                            )
                            ->responses(
                                Response::status(200)
                                    ->description('pet response')
                                    ->content(MediaType::json()->schema(Schema::array()->items(Schema::ref('Pet')))),
                                Response::create('default')
                                    ->description('unexpected error')
                                    ->content(MediaType::json()->schema(Schema::ref('Error'))),
                            ),
                        Operation::post()
                            ->description('Creates a new pet in the store. Duplicates are allowed')
                            ->operationId('addPet')
                            ->requestBody(
                                RequestBody::create()
                                    ->description('Pet to add to the store')
                                    ->required(true)
                                    ->content(MediaType::json()->schema(Schema::ref('NewPet'))),
                            )
                            ->responses(
                                Response::status(200)
                                    ->description('pet response')
                                    ->content(MediaType::json()->schema(Schema::ref('Pet'))),
                                Response::create('default')
                                    ->description('unexpected error')
                                    ->content(MediaType::json()->schema(Schema::ref('Error'))),
                            ),
                    ),
                PathItem::create('/pets/{id}')
                    ->operations(
                        Operation::get()
                            ->description(
                                'Returns a user based on a single ID, if the user does not have access to the pet',
                            )
                            ->operationId('find pet by id')
                            ->parameters(
                                Parameter::path('id')
                                    ->description('ID of pet to fetch')
                                    ->schema(Schema::integer()->format('int64')),
                            )
                            ->responses(
                                Response::status(200)
                                    ->description('pet response')
                                    ->content(MediaType::json()->schema(Schema::ref('Pet'))),
                                Response::create('default')
                                    ->description('unexpected error')
                                    ->content(MediaType::json()->schema(Schema::ref('Error'))),
                            ),
                        Operation::delete()
                            ->description('deletes a single pet based on the ID supplied')
                            ->operationId('deletePet')
                            ->parameters(
                                Parameter::path('id')
                                    ->description('ID of pet to delete')
                                    ->schema(Schema::integer()->format('int64')),
                            )
                            ->responses(
                                Response::status(204)->description('pet deleted'),
                                Response::create('default')
                                    ->description('unexpected error')
                                    ->content(MediaType::json()->schema(Schema::ref('Error'))),
                            ),
                    ),
            )
            ->components(
                Components::create()
                    ->schemas(
                        Schema::create('Pet')
                            ->allOf(
                                Schema::ref('NewPet'),
                                Schema::object()->required('id')->properties(Schema::integer('id')->format('int64')),
                            ),
                        Schema::object('NewPet')
                            ->required('name')
                            ->properties(Schema::string('name'), Schema::string('tag')),
                        Schema::object('Error')
                            ->required('code', 'message')
                            ->properties(Schema::integer('code')->format('int32'), Schema::string('message')),
                    ),
            );
    }

    /**
     * `callback-example` (OpenAPI 3.0.0), from https://github.com/OAI/learn.openapis.org/tree/main/examples.
     */
    public static function callbackExample(): OpenApi
    {
        return OpenApi::create()
            ->info(Info::create()->title('Callback Example')->version('1.0.0'))
            ->paths(
                PathItem::create('/streams')
                    ->operations(
                        Operation::post()
                            ->description('subscribes a client to receive out-of-band data')
                            ->parameters(
                                Parameter::query('callbackUrl')
                                    ->required(true)
                                    ->description(
                                        "the location where data will be sent.  Must be network accessible\nby the source server\n",
                                    )
                                    ->schema(Schema::string()->format('uri')->example('https://tonys-server.com')),
                            )
                            ->responses(
                                Response::status(201)
                                    ->description('subscription successfully created')
                                    ->content(
                                        MediaType::json()
                                            ->schema(
                                                Schema::object()
                                                    ->description('subscription information')
                                                    ->required('subscriptionId')
                                                    ->properties(
                                                        Schema::string('subscriptionId')
                                                            ->description('this unique identifier allows management of the subscription')
                                                            ->example('2531329f-fb09-4ef7-887e-84e648214436'),
                                                    ),
                                            ),
                                    ),
                            )
                            ->callbacks(
                                Callback::create('onData')
                                    ->pathItems(
                                        PathItem::create('{$request.query.callbackUrl}/data')
                                            ->operations(
                                                Operation::post()
                                                    ->requestBody(
                                                        RequestBody::create()
                                                            ->description('subscription payload')
                                                            ->content(
                                                                MediaType::json()
                                                                    ->schema(
                                                                        Schema::object()
                                                                            ->properties(Schema::string('timestamp')->format('date-time'), Schema::string('userData')),
                                                                    ),
                                                            ),
                                                    )
                                                    ->responses(
                                                        Response::status(202)
                                                            ->description(
                                                                "Your server implementation should return this HTTP status code\nif the data was received successfully\n",
                                                            ),
                                                        Response::status(204)
                                                            ->description(
                                                                "Your server should return this HTTP status code if no longer interested\nin further updates\n",
                                                            ),
                                                    ),
                                            ),
                                    ),
                            ),
                    ),
            );
    }

    /**
     * `link-example` (OpenAPI 3.0.0), from https://github.com/OAI/learn.openapis.org/tree/main/examples.
     */
    public static function linkExample(): OpenApi
    {
        return OpenApi::create()
            ->info(Info::create()->title('Link Example')->version('1.0.0'))
            ->paths(
                PathItem::create('/2.0/users/{username}')
                    ->operations(
                        Operation::get()
                            ->operationId('getUserByName')
                            ->parameters(Parameter::path('username')->schema(Schema::string()))
                            ->responses(
                                Response::status(200)
                                    ->description('The User')
                                    ->content(MediaType::json()->schema(Schema::ref('user')))
                                    ->links(Link::ref('UserRepositories', 'userRepositories')),
                            ),
                    ),
                PathItem::create('/2.0/repositories/{username}')
                    ->operations(
                        Operation::get()
                            ->operationId('getRepositoriesByOwner')
                            ->parameters(Parameter::path('username')->schema(Schema::string()))
                            ->responses(
                                Response::status(200)
                                    ->description('repositories owned by the supplied user')
                                    ->content(MediaType::json()->schema(Schema::array()->items(Schema::ref('repository'))))
                                    ->links(Link::ref('UserRepository', 'userRepository')),
                            ),
                    ),
                PathItem::create('/2.0/repositories/{username}/{slug}')
                    ->operations(
                        Operation::get()
                            ->operationId('getRepository')
                            ->parameters(
                                Parameter::path('username')->schema(Schema::string()),
                                Parameter::path('slug')->schema(Schema::string()),
                            )
                            ->responses(
                                Response::status(200)
                                    ->description('The repository')
                                    ->content(MediaType::json()->schema(Schema::ref('repository')))
                                    ->links(Link::ref('RepositoryPullRequests', 'repositoryPullRequests')),
                            ),
                    ),
                PathItem::create('/2.0/repositories/{username}/{slug}/pullrequests')
                    ->operations(
                        Operation::get()
                            ->operationId('getPullRequestsByRepository')
                            ->parameters(
                                Parameter::path('username')->schema(Schema::string()),
                                Parameter::path('slug')->schema(Schema::string()),
                                Parameter::query('state')->schema(Schema::string()->enum('open', 'merged', 'declined')),
                            )
                            ->responses(
                                Response::status(200)
                                    ->description('an array of pull request objects')
                                    ->content(MediaType::json()->schema(Schema::array()->items(Schema::ref('pullrequest')))),
                            ),
                    ),
                PathItem::create('/2.0/repositories/{username}/{slug}/pullrequests/{pid}')
                    ->operations(
                        Operation::get()
                            ->operationId('getPullRequestsById')
                            ->parameters(
                                Parameter::path('username')->schema(Schema::string()),
                                Parameter::path('slug')->schema(Schema::string()),
                                Parameter::path('pid')->schema(Schema::string()),
                            )
                            ->responses(
                                Response::status(200)
                                    ->description('a pull request object')
                                    ->content(MediaType::json()->schema(Schema::ref('pullrequest')))
                                    ->links(Link::ref('PullRequestMerge', 'pullRequestMerge')),
                            ),
                    ),
                PathItem::create('/2.0/repositories/{username}/{slug}/pullrequests/{pid}/merge')
                    ->operations(
                        Operation::post()
                            ->operationId('mergePullRequest')
                            ->parameters(
                                Parameter::path('username')->schema(Schema::string()),
                                Parameter::path('slug')->schema(Schema::string()),
                                Parameter::path('pid')->schema(Schema::string()),
                            )
                            ->responses(Response::status(204)->description('the PR was successfully merged')),
                    ),
            )
            ->components(
                Components::create()
                    ->links(
                        Link::create('UserRepositories')
                            ->operationId('getRepositoriesByOwner')
                            ->parameters(['username' => '$response.body#/username']),
                        Link::create('UserRepository')
                            ->operationId('getRepository')
                            ->parameters(
                                ['username' => '$response.body#/owner/username', 'slug' => '$response.body#/slug'],
                            ),
                        Link::create('RepositoryPullRequests')
                            ->operationId('getPullRequestsByRepository')
                            ->parameters(
                                ['username' => '$response.body#/owner/username', 'slug' => '$response.body#/slug'],
                            ),
                        Link::create('PullRequestMerge')
                            ->operationId('mergePullRequest')
                            ->parameters(
                                [
                                    'username' => '$response.body#/author/username',
                                    'slug' => '$response.body#/repository/slug',
                                    'pid' => '$response.body#/id',
                                ],
                            ),
                    )
                    ->schemas(
                        Schema::object('user')->properties(Schema::string('username'), Schema::string('uuid')),
                        Schema::object('repository')->properties(Schema::string('slug'), Schema::ref('user', 'owner')),
                        Schema::object('pullrequest')
                            ->properties(
                                Schema::integer('id'),
                                Schema::string('title'),
                                Schema::ref('repository', 'repository'),
                                Schema::ref('user', 'author'),
                            ),
                    ),
            );
    }

    /**
     * `api-with-examples` (OpenAPI 3.0.0), from https://github.com/OAI/learn.openapis.org/tree/main/examples.
     */
    public static function apiWithExamples(): OpenApi
    {
        return OpenApi::create()
            ->info(Info::create()->title('Simple API overview')->version('2.0.0'))
            ->paths(
                PathItem::create('/')
                    ->operations(
                        Operation::get()
                            ->operationId('listVersionsv2')
                            ->summary('List API versions')
                            ->responses(
                                Response::status(200)
                                    ->description('200 response')
                                    ->content(
                                        MediaType::json()
                                            ->examples(
                                                Example::create('foo')
                                                    ->value(
                                                        [
                                                            'versions' => [
                                                                [
                                                                    'status' => 'CURRENT',
                                                                    'updated' => '2011-01-21T11:33:21Z',
                                                                    'id' => 'v2.0',
                                                                    'links' => [['href' => 'http://127.0.0.1:8774/v2/', 'rel' => 'self']],
                                                                ],
                                                                [
                                                                    'status' => 'EXPERIMENTAL',
                                                                    'updated' => '2013-07-23T11:33:21Z',
                                                                    'id' => 'v3.0',
                                                                    'links' => [['href' => 'http://127.0.0.1:8774/v3/', 'rel' => 'self']],
                                                                ],
                                                            ],
                                                        ],
                                                    ),
                                            ),
                                    ),
                                Response::status(300)
                                    ->description('300 response')
                                    ->content(
                                        MediaType::json()
                                            ->examples(
                                                Example::create('foo')
                                                    ->value(
                                                        [
                                                            'versions' => [
                                                                [
                                                                    'status' => 'CURRENT',
                                                                    'updated' => '2011-01-21T11:33:21Z',
                                                                    'id' => 'v2.0',
                                                                    'links' => [['href' => 'http://127.0.0.1:8774/v2/', 'rel' => 'self']],
                                                                ],
                                                                [
                                                                    'status' => 'EXPERIMENTAL',
                                                                    'updated' => '2013-07-23T11:33:21Z',
                                                                    'id' => 'v3.0',
                                                                    'links' => [['href' => 'http://127.0.0.1:8774/v3/', 'rel' => 'self']],
                                                                ],
                                                            ],
                                                        ],
                                                    ),
                                            ),
                                    ),
                            ),
                    ),
                PathItem::create('/v2')
                    ->operations(
                        Operation::get()
                            ->operationId('getVersionDetailsv2')
                            ->summary('Show API version details')
                            ->responses(
                                Response::status(200)
                                    ->description('200 response')
                                    ->content(
                                        MediaType::json()
                                            ->examples(
                                                Example::create('foo')
                                                    ->value(
                                                        [
                                                            'version' => [
                                                                'status' => 'CURRENT',
                                                                'updated' => '2011-01-21T11:33:21Z',
                                                                'media-types' => [
                                                                    [
                                                                        'base' => 'application/xml',
                                                                        'type' => 'application/vnd.openstack.compute+xml;version=2',
                                                                    ],
                                                                    [
                                                                        'base' => 'application/json',
                                                                        'type' => 'application/vnd.openstack.compute+json;version=2',
                                                                    ],
                                                                ],
                                                                'id' => 'v2.0',
                                                                'links' => [
                                                                    ['href' => 'http://127.0.0.1:8774/v2/', 'rel' => 'self'],
                                                                    [
                                                                        'href' => 'http://docs.openstack.org/api/openstack-compute/2/os-compute-devguide-2.pdf',
                                                                        'type' => 'application/pdf',
                                                                        'rel' => 'describedby',
                                                                    ],
                                                                    [
                                                                        'href' => 'http://docs.openstack.org/api/openstack-compute/2/wadl/os-compute-2.wadl',
                                                                        'type' => 'application/vnd.sun.wadl+xml',
                                                                        'rel' => 'describedby',
                                                                    ],
                                                                    [
                                                                        'href' => 'http://docs.openstack.org/api/openstack-compute/2/wadl/os-compute-2.wadl',
                                                                        'type' => 'application/vnd.sun.wadl+xml',
                                                                        'rel' => 'describedby',
                                                                    ],
                                                                ],
                                                            ],
                                                        ],
                                                    ),
                                            ),
                                    ),
                                Response::status(203)
                                    ->description('203 response')
                                    ->content(
                                        MediaType::json()
                                            ->examples(
                                                Example::create('foo')
                                                    ->value(
                                                        [
                                                            'version' => [
                                                                'status' => 'CURRENT',
                                                                'updated' => '2011-01-21T11:33:21Z',
                                                                'media-types' => [
                                                                    [
                                                                        'base' => 'application/xml',
                                                                        'type' => 'application/vnd.openstack.compute+xml;version=2',
                                                                    ],
                                                                    [
                                                                        'base' => 'application/json',
                                                                        'type' => 'application/vnd.openstack.compute+json;version=2',
                                                                    ],
                                                                ],
                                                                'id' => 'v2.0',
                                                                'links' => [
                                                                    ['href' => 'http://23.253.228.211:8774/v2/', 'rel' => 'self'],
                                                                    [
                                                                        'href' => 'http://docs.openstack.org/api/openstack-compute/2/os-compute-devguide-2.pdf',
                                                                        'type' => 'application/pdf',
                                                                        'rel' => 'describedby',
                                                                    ],
                                                                    [
                                                                        'href' => 'http://docs.openstack.org/api/openstack-compute/2/wadl/os-compute-2.wadl',
                                                                        'type' => 'application/vnd.sun.wadl+xml',
                                                                        'rel' => 'describedby',
                                                                    ],
                                                                ],
                                                            ],
                                                        ],
                                                    ),
                                            ),
                                    ),
                            ),
                    ),
            );
    }

    /**
     * `uspto` (OpenAPI 3.0.1), from https://github.com/OAI/learn.openapis.org/tree/main/examples.
     */
    public static function uspto(): OpenApi
    {
        return OpenApi::create()
            ->servers(
                Server::create()
                    ->url('{scheme}://developer.uspto.gov/ds-api')
                    ->variables(
                        ServerVariable::create('scheme')
                            ->description('The Data Set API is accessible via https and http')
                            ->enum('https', 'http')
                            ->default('https'),
                    ),
            )
            ->info(
                Info::create()
                    ->description(
                        'The Data Set API (DSAPI) allows the public users to discover and search USPTO exported data sets. This is a generic API that allows USPTO users to make any CSV based data files searchable through API. With the help of GET call, it returns the list of data fields that are searchable. With the help of POST call, data can be fetched based on the filters on the field names. Please note that POST call is used to search the actual data. The reason for the POST call is that it allows users to specify any complex search criteria without worry about the GET size limitations as well as encoding of the input parameters.',
                    )
                    ->version('1.0.0')
                    ->title('USPTO Data Set API')
                    ->contact(
                        Contact::create()
                            ->name('Open Data Portal')
                            ->url('https://developer.uspto.gov')
                            ->email('developer@uspto.gov'),
                    ),
            )
            ->tags(
                Tag::create()->name('metadata')->description('Find out about the data sets'),
                Tag::create()->name('search')->description('Search a data set'),
            )
            ->paths(
                PathItem::create('/')
                    ->operations(
                        Operation::get()
                            ->tags('metadata')
                            ->operationId('list-data-sets')
                            ->summary('List available data sets')
                            ->responses(
                                Response::status(200)
                                    ->description('Returns a list of data sets')
                                    ->content(
                                        MediaType::json()
                                            ->schema(Schema::ref('dataSetList'))
                                            ->example(
                                                [
                                                    'total' => 2,
                                                    'apis' => [
                                                        [
                                                            'apiKey' => 'oa_citations',
                                                            'apiVersionNumber' => 'v1',
                                                            'apiUrl' => 'https://developer.uspto.gov/ds-api/oa_citations/v1/fields',
                                                            'apiDocumentationUrl' => 'https://developer.uspto.gov/ds-api-docs/index.html?url=https://developer.uspto.gov/ds-api/swagger/docs/oa_citations.json',
                                                        ],
                                                        [
                                                            'apiKey' => 'cancer_moonshot',
                                                            'apiVersionNumber' => 'v1',
                                                            'apiUrl' => 'https://developer.uspto.gov/ds-api/cancer_moonshot/v1/fields',
                                                            'apiDocumentationUrl' => 'https://developer.uspto.gov/ds-api-docs/index.html?url=https://developer.uspto.gov/ds-api/swagger/docs/cancer_moonshot.json',
                                                        ],
                                                    ],
                                                ],
                                            ),
                                    ),
                            ),
                    ),
                PathItem::create('/{dataset}/{version}/fields')
                    ->operations(
                        Operation::get()
                            ->tags('metadata')
                            ->summary(
                                'Provides the general information about the API and the list of fields that can be used to query the dataset.',
                            )
                            ->description(
                                'This GET API returns the list of all the searchable field names that are in the oa_citations. Please see the \'fields\' attribute which returns an array of field names. Each field or a combination of fields can be searched using the syntax options shown below.',
                            )
                            ->operationId('list-searchable-fields')
                            ->parameters(
                                Parameter::path('dataset')
                                    ->description('Name of the dataset.')
                                    ->example('oa_citations')
                                    ->schema(Schema::string()),
                                Parameter::path('version')
                                    ->description('Version of the dataset.')
                                    ->example('v1')
                                    ->schema(Schema::string()),
                            )
                            ->responses(
                                Response::status(200)
                                    ->description(
                                        'The dataset API for the given version is found and it is accessible to consume.',
                                    )
                                    ->content(MediaType::json()->schema(Schema::string())),
                                Response::status(404)
                                    ->description(
                                        'The combination of dataset name and version is not found in the system or it is not published yet to be consumed by public.',
                                    )
                                    ->content(MediaType::json()->schema(Schema::string())),
                            ),
                    ),
                PathItem::create('/{dataset}/{version}/records')
                    ->operations(
                        Operation::post()
                            ->tags('search')
                            ->summary('Provides search capability for the data set with the given search criteria.')
                            ->description(
                                'This API is based on Solr/Lucene Search. The data is indexed using SOLR. This GET API returns the list of all the searchable field names that are in the Solr Index. Please see the \'fields\' attribute which returns an array of field names. Each field or a combination of fields can be searched using the Solr/Lucene Syntax. Please refer https://lucene.apache.org/core/3_6_2/queryparsersyntax.html#Overview for the query syntax. List of field names that are searchable can be determined using above GET api.',
                            )
                            ->operationId('perform-search')
                            ->parameters(
                                Parameter::path('version')
                                    ->description('Version of the dataset.')
                                    ->schema(Schema::string()->default('v1')),
                                Parameter::path('dataset')
                                    ->description('Name of the dataset. In this case, the default value is oa_citations')
                                    ->schema(Schema::string()->default('oa_citations')),
                            )
                            ->responses(
                                Response::status(200)
                                    ->description('successful operation')
                                    ->content(
                                        MediaType::json()
                                            ->schema(
                                                Schema::array()->items(Schema::object()->additionalProperties(Schema::object())),
                                            ),
                                    ),
                                Response::status(404)->description('No matching record found for the given criteria.'),
                            )
                            ->requestBody(
                                RequestBody::create()
                                    ->content(
                                        MediaType::formUrlEncoded()
                                            ->schema(
                                                Schema::object()
                                                    ->properties(
                                                        Schema::string('criteria')
                                                            ->description(
                                                                'Uses Lucene Query Syntax in the format of propertyName:value, propertyName:[num1 TO num2] and date range format: propertyName:[yyyyMMdd TO yyyyMMdd]. In the response please see the \'docs\' element which has the list of record objects. Each record structure would consist of all the fields and their corresponding values.',
                                                            )
                                                            ->default('*:*'),
                                                        Schema::integer('start')->description('Starting record number. Default value is 0.')->default(0),
                                                        Schema::integer('rows')
                                                            ->description(
                                                                'Specify number of rows to be returned. If you run the search with default values, in the response you will see \'numFound\' attribute which will tell the number of records available in the dataset.',
                                                            )
                                                            ->default(100),
                                                    )
                                                    ->required('criteria'),
                                            ),
                                    ),
                            ),
                    ),
            )
            ->components(
                Components::create()
                    ->schemas(
                        Schema::object('dataSetList')
                            ->properties(
                                Schema::integer('total'),
                                Schema::array('apis')
                                    ->items(
                                        Schema::object()
                                            ->properties(
                                                Schema::string('apiKey')->description('To be used as a dataset parameter value'),
                                                Schema::string('apiVersionNumber')->description('To be used as a version parameter value'),
                                                Schema::string('apiUrl')
                                                    ->format('uri-reference')
                                                    ->description('The URL describing the dataset\'s fields'),
                                                Schema::string('apiDocumentationUrl')
                                                    ->format('uri-reference')
                                                    ->description('A URL to the API console for each API'),
                                            ),
                                    ),
                            ),
                    ),
            );
    }

    /**
     * `tictactoe` (OpenAPI 3.1.0), from https://github.com/OAI/learn.openapis.org/tree/main/examples.
     */
    public static function tictactoe(): OpenApi
    {
        return OpenApi::create()
            ->info(
                Info::create()
                    ->title('Tic Tac Toe')
                    ->description(
                        "This API allows writing down marks on a Tic Tac Toe board\nand requesting the state of the board or of individual squares.\n",
                    )
                    ->version('1.0.0'),
            )
            ->tags(Tag::create()->name('Gameplay'))
            ->paths(
                PathItem::create('/board')
                    ->operations(
                        Operation::get()
                            ->summary('Get the whole board')
                            ->description('Retrieves the current state of the board and the winner.')
                            ->tags('Gameplay')
                            ->operationId('get-board')
                            ->responses(Response::ok()->content(MediaType::json()->schema(Schema::ref('status'))))
                            ->security(
                                SecurityRequirement::create()->schemes(['defaultApiKey' => []]),
                                SecurityRequirement::create()->schemes(['app2AppOauth' => ['board:read']]),
                            ),
                    ),
                PathItem::create('/board/{row}/{column}')
                    ->parameters(Parameter::ref('rowParam'), Parameter::ref('columnParam'))
                    ->operations(
                        Operation::get()
                            ->summary('Get a single board square')
                            ->description('Retrieves the requested square.')
                            ->tags('Gameplay')
                            ->operationId('get-square')
                            ->responses(
                                Response::ok()->content(MediaType::json()->schema(Schema::ref('mark'))),
                                Response::status(400)
                                    ->description('The provided parameters are incorrect')
                                    ->content(
                                        MediaType::of('text/html')->schema(Schema::ref('errorMessage'))->example('Illegal coordinates'),
                                    ),
                            )
                            ->security(
                                SecurityRequirement::create()->schemes(['bearerHttpAuthentication' => []]),
                                SecurityRequirement::create()->schemes(['user2AppOauth' => ['board:read']]),
                            ),
                        Operation::put()
                            ->summary('Set a single board square')
                            ->description(
                                'Places a mark on the board and retrieves the whole board and the winner (if any).',
                            )
                            ->tags('Gameplay')
                            ->operationId('put-square')
                            ->requestBody(
                                RequestBody::create()->required(true)->content(MediaType::json()->schema(Schema::ref('mark'))),
                            )
                            ->responses(
                                Response::ok()->content(MediaType::json()->schema(Schema::ref('status'))),
                                Response::status(400)
                                    ->description('The provided parameters are incorrect')
                                    ->content(
                                        MediaType::of('text/html')
                                            ->schema(Schema::ref('errorMessage'))
                                            ->examples(
                                                Example::create('illegalCoordinates')->value('Illegal coordinates.'),
                                                Example::create('notEmpty')->value('Square is not empty.'),
                                                Example::create('invalidMark')->value('Invalid Mark (X or O).'),
                                            ),
                                    ),
                            )
                            ->security(
                                SecurityRequirement::create()->schemes(['bearerHttpAuthentication' => []]),
                                SecurityRequirement::create()->schemes(['user2AppOauth' => ['board:write']]),
                            ),
                    ),
            )
            ->components(
                Components::create()
                    ->parameters(
                        Parameter::path('row', 'rowParam')
                            ->description('Board row (vertical coordinate)')
                            ->schema(Schema::ref('coordinate')),
                        Parameter::path('column', 'columnParam')
                            ->description('Board column (horizontal coordinate)')
                            ->schema(Schema::ref('coordinate')),
                    )
                    ->schemas(
                        Schema::string('errorMessage')->maxLength(256)->description('A text message describing an error'),
                        Schema::integer('coordinate')->minimum(1)->maximum(3)->example(1),
                        Schema::string('mark')
                            ->enum('.', 'X', 'O')
                            ->description('Possible values for a board square. `.` means empty square.')
                            ->example('.'),
                        Schema::array('board')
                            ->maxItems(3)
                            ->minItems(3)
                            ->items(Schema::array()->maxItems(3)->minItems(3)->items(Schema::ref('mark'))),
                        Schema::string('winner')
                            ->enum('.', 'X', 'O')
                            ->description('Winner of the game. `.` means nobody has won yet.')
                            ->example('.'),
                        Schema::object('status')
                            ->properties(Schema::ref('winner', 'winner'), Schema::ref('board', 'board')),
                    )
                    ->securitySchemes(
                        SecurityScheme::apiKey('defaultApiKey')
                            ->description('API key provided in console')
                            ->name('api-key')
                            ->in('header'),
                        SecurityScheme::http('basicHttpAuthentication')
                            ->description('Basic HTTP Authentication')
                            ->scheme('Basic'),
                        SecurityScheme::http('bearerHttpAuthentication')
                            ->description('Bearer token using a JWT')
                            ->scheme('Bearer')
                            ->bearerFormat('JWT'),
                        SecurityScheme::oauth2('app2AppOauth')
                            ->flows(
                                OAuthFlows::create()
                                    ->clientCredentials(
                                        OAuthFlow::create()
                                            ->tokenUrl('https://learn.openapis.org/oauth/2.0/token')
                                            ->scopes(['board:read' => 'Read the board']),
                                    ),
                            ),
                        SecurityScheme::oauth2('user2AppOauth')
                            ->flows(
                                OAuthFlows::create()
                                    ->authorizationCode(
                                        OAuthFlow::create()
                                            ->authorizationUrl('https://learn.openapis.org/oauth/2.0/auth')
                                            ->tokenUrl('https://learn.openapis.org/oauth/2.0/token')
                                            ->scopes(['board:read' => 'Read the board', 'board:write' => 'Write to the board']),
                                    ),
                            ),
                    ),
            );
    }

    /**
     * `webhook-example` (OpenAPI 3.1.0), from https://github.com/OAI/learn.openapis.org/tree/main/examples.
     */
    public static function webhookExample(): OpenApi
    {
        return OpenApi::create()
            ->info(Info::create()->title('Webhook Example')->version('1.0.0'))
            ->webhooks(
                PathItem::create('newPet')
                    ->operations(
                        Operation::post()
                            ->requestBody(
                                RequestBody::create()
                                    ->description('Information about a new pet in the system')
                                    ->content(MediaType::json()->schema(Schema::ref('Pet'))),
                            )
                            ->responses(
                                Response::status(200)
                                    ->description('Return a 200 status to indicate that the data was received successfully'),
                            ),
                    ),
            )
            ->components(
                Components::create()
                    ->schemas(
                        Schema::object('Pet')
                            ->required('id', 'name')
                            ->properties(
                                Schema::integer('id')->format('int64'),
                                Schema::string('name'),
                                Schema::string('tag'),
                            ),
                    ),
            );
    }

    /**
     * `non-oauth-scopes` (OpenAPI 3.1.0), from https://github.com/OAI/learn.openapis.org/tree/main/examples.
     */
    public static function nonOauthScopes(): OpenApi
    {
        return OpenApi::create()
            ->info(Info::create()->title('Non-oAuth Scopes example')->version('1.0.0'))
            ->paths(
                PathItem::create('/users')
                    ->operations(
                        Operation::get()
                            ->security(
                                SecurityRequirement::create()->schemes(['bearerAuth' => ['read:users', 'public']]),
                            ),
                    ),
            )
            ->components(
                Components::create()
                    ->securitySchemes(
                        SecurityScheme::http('bearerAuth')
                            ->scheme('bearer')
                            ->bearerFormat('jwt')
                            ->description('note: non-oauth scopes are not defined at the securityScheme level'),
                    ),
            );
    }

    /**
     * `3.2-query-example` (OpenAPI 3.2.0), from https://github.com/OAI/learn.openapis.org/tree/main/examples.
     */
    public static function queryExample(): OpenApi
    {
        return OpenApi::create()
            ->info(Info::create()->title('Flight API')->version('1.0.0'))
            ->paths(
                PathItem::create('/flights/search')
                    ->operations(
                        Operation::query()
                            ->summary('Search flights with complex criteria')
                            ->description(
                                'Uses the HTTP QUERY method to perform a safe, idempotent flight search with filters that are too complex for a URL query string.',
                            )
                            ->operationId('searchFlights')
                            ->requestBody(
                                RequestBody::create()
                                    ->required(true)
                                    ->content(
                                        MediaType::json()
                                            ->schema(
                                                Schema::object()
                                                    ->properties(
                                                        Schema::string('origin')->example('ATL'),
                                                        Schema::string('destination')->example('LHR'),
                                                        Schema::string('departureDate')->format('date')->example('2026-06-15'),
                                                        Schema::string('returnDate')->format('date')->example('2026-06-25'),
                                                        Schema::integer('passengers')->minimum(1)->example(2),
                                                        Schema::string('cabinClass')
                                                            ->enum('economy', 'premium-economy', 'business', 'first')
                                                            ->example('business'),
                                                        Schema::boolean('nonStopOnly')->example(true),
                                                    )
                                                    ->required('origin', 'destination', 'departureDate'),
                                            ),
                                    ),
                            )
                            ->responses(
                                Response::status(200)
                                    ->description('Matching flights')
                                    ->content(
                                        MediaType::json()
                                            ->schema(
                                                Schema::object()
                                                    ->properties(
                                                        Schema::array('flights')
                                                            ->items(
                                                                Schema::object()
                                                                    ->properties(
                                                                        Schema::string('flightNumber')->example('DL30'),
                                                                        Schema::string('origin')->example('ATL'),
                                                                        Schema::string('destination')->example('LHR'),
                                                                        Schema::string('departureTime')->format('date-time')->example('2026-06-15T22:30:00Z'),
                                                                        Schema::string('arrivalTime')->format('date-time')->example('2026-06-16T10:15:00Z'),
                                                                        Schema::number('price')->format('float')->example(1249.99),
                                                                    ),
                                                            ),
                                                    ),
                                            ),
                                    ),
                            ),
                    ),
            );
    }

    /**
     * `3.2-tags-example` (OpenAPI 3.2.0), from https://github.com/OAI/learn.openapis.org/tree/main/examples.
     */
    public static function tagsExample(): OpenApi
    {
        return OpenApi::create()
            ->info(Info::create()->title('Flight API')->version('1.0.0'))
            ->tags(
                Tag::create()
                    ->name('flights')
                    ->summary('Flights')
                    ->description('Core flight operations')
                    ->kind('nav'),
                Tag::create()
                    ->name('international')
                    ->summary('International')
                    ->description('Flights that cross country borders')
                    ->parent('flights')
                    ->kind('nav'),
                Tag::create()
                    ->name('domestic')
                    ->summary('Domestic')
                    ->description('Flights within a single country')
                    ->parent('flights')
                    ->kind('nav'),
                Tag::create()
                    ->name('delays')
                    ->summary('Delays')
                    ->description('Information about flight delays')
                    ->kind('badge')
                    ->externalDocs(
                        ExternalDocumentation::create()
                            ->description('Delay compensation policies')
                            ->url('https://docs.example.com/delay-policies'),
                    ),
            )
            ->paths(
                PathItem::create('/flights')
                    ->operations(Operation::get()->tags('flights')->summary('List all flights')),
                PathItem::create('/flights/international')
                    ->operations(Operation::get()->tags('international')->summary('List international flights')),
                PathItem::create('/flights/domestic')
                    ->operations(Operation::get()->tags('domestic')->summary('List domestic flights')),
                PathItem::create('/flights/delayed')
                    ->operations(Operation::get()->tags('delays')->summary('Get delayed flights')),
            );
    }
}
