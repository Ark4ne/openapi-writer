<?php

declare(strict_types=1);

/*
 * Measures the cost of immutability: builds a document of N operations, once with every path given in one call and
 * once path by path with addPaths() (one clone of the whole map per call), then writes it.
 *
 * Usage: php bin/benchmark.php [operations = 500]
 */

use Ark4ne\OpenApiWriter\Enums\Version;
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

require dirname(__DIR__) . '/vendor/autoload.php';

$operations = (int) ($argv[1] ?? 500);

if ($operations < 1) {
    fwrite(STDERR, 'Usage: php bin/benchmark.php [operations = 500]' . PHP_EOL);
    exit(1);
}

/**
 * Five operations per resource, as a CRUD API: list, create, show, update, delete.
 *
 * @return list<PathItem>
 */
$resource = static function (int $index): array {
    $name = 'resource' . $index;
    $body = RequestBody::create()->required()->content(MediaType::json()->schema(Schema::ref($name)));
    $one = Response::ok()->content(MediaType::json()->schema(Schema::ref($name)));
    $id = Parameter::path('id')->schema(Schema::integer()->format('int64'));

    return [
        PathItem::create("/{$name}")->operations(
            Operation::get()->operationId("{$name}.index")->tags($name)
                ->parameters(Parameter::query('page')->schema(Schema::integer()->minimum(1)))
                ->responses(Response::ok()->content(MediaType::json()->schema(Schema::array()->items(Schema::ref($name))))),
            Operation::post()->operationId("{$name}.store")->tags($name)->requestBody($body)
                ->responses(Response::created()->content(MediaType::json()->schema(Schema::ref($name))), Response::unprocessableEntity()),
        ),
        PathItem::create("/{$name}/{id}")->parameters($id)->operations(
            Operation::get()->operationId("{$name}.show")->tags($name)->responses($one, Response::notFound()),
            Operation::put()->operationId("{$name}.update")->tags($name)->requestBody($body)->responses($one, Response::notFound()),
            Operation::delete()->operationId("{$name}.destroy")->tags($name)->responses(Response::noContent(), Response::notFound()),
        ),
    ];
};

$schema = static fn(int $index): Schema => Schema::object('resource' . $index)
    ->properties(
        Schema::integer('id')->format('int64')->readOnly(),
        Schema::string('name')->maxLength(255),
        Schema::string('email')->format('email')->nullable(),
        Schema::string('created_at')->format('date-time')->readOnly(),
    )
    ->required('id', 'name');

$resources = intdiv($operations + 4, 5);
$info = Info::create()->title('Benchmark')->version('1.0.0');

$start = hrtime(true);
$paths = [];
$schemas = [];

for ($index = 0; $index < $resources; $index++) {
    $paths = [...$paths, ...$resource($index)];
    $schemas[] = $schema($index);
}

$atOnce = OpenApi::create()->info($info)->paths(...$paths)->components(Components::create()->schemas(...$schemas));
$atOnceMs = (hrtime(true) - $start) / 1e6;

$start = hrtime(true);
$oneByOne = OpenApi::create()->info($info)->paths();
$components = Components::create();

for ($index = 0; $index < $resources; $index++) {
    $oneByOne = $oneByOne->addPaths(...$resource($index));
    $components = $components->addSchemas($schema($index));
}

$oneByOne = $oneByOne->components($components);
$oneByOneMs = (hrtime(true) - $start) / 1e6;

$start = hrtime(true);
$json = $oneByOne->toJson(Version::V3_0_4);
$writeMs = (hrtime(true) - $start) / 1e6;

if ($json !== $atOnce->toJson(Version::V3_0_4)) {
    fwrite(STDERR, 'Both documents should be identical.' . PHP_EOL);
    exit(1);
}

printf("PHP %s, %d operations, %d paths, %d schemas, %.0f KiB of JSON\n", PHP_VERSION, $resources * 5, $resources * 2, $resources, strlen($json) / 1024);
printf("%-34s %8.1f ms\n", 'Build, paths given at once', $atOnceMs);
printf("%-34s %8.1f ms\n", 'Build, addPaths() path by path', $oneByOneMs);
printf("%-34s %8.1f ms\n", 'Write as 3.0.4 JSON', $writeMs);
printf("%-34s %8.1f MiB\n", 'Peak memory', memory_get_peak_usage(true) / 1048576);
