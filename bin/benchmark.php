<?php

declare(strict_types=1);

/*
 * Measures the cost of immutability: builds a document of N operations (five per resource, so N is rounded up to a
 * multiple of 5), once with every path given in one call and once path by path with addPaths() (one clone of the
 * whole map per call), then writes it. After a warmup run, both builds alternate and the median of each is printed.
 *
 * Usage: php bin/benchmark.php [operations = 500] [runs = 15]
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
$runs = (int) ($argv[2] ?? 15);

if ($operations < 1 || $runs < 1) {
    fwrite(STDERR, 'Usage: php bin/benchmark.php [operations = 500] [runs = 15]' . PHP_EOL);
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

$atOnce = static function () use ($resources, $resource, $schema, $info): OpenApi {
    $paths = [];
    $schemas = [];

    for ($index = 0; $index < $resources; $index++) {
        $paths[] = $resource($index);
        $schemas[] = $schema($index);
    }

    return OpenApi::create()->info($info)->paths(...array_merge(...$paths))->components(Components::create()->schemas(...$schemas));
};

$oneByOne = static function () use ($resources, $resource, $schema, $info): OpenApi {
    $document = OpenApi::create()->info($info)->paths();
    $components = Components::create();

    for ($index = 0; $index < $resources; $index++) {
        $document = $document->addPaths(...$resource($index));
        $components = $components->addSchemas($schema($index));
    }

    return $document->components($components);
};

// Warmup: autoloading, the metadata cache and the first writes are paid before any measure.
$json = $atOnce()->toJson(Version::V3_0_4);

if ($json !== $oneByOne()->toJson(Version::V3_0_4)) {
    fwrite(STDERR, 'Both documents should be identical.' . PHP_EOL);
    exit(1);
}

$timings = ['atOnce' => [], 'oneByOne' => [], 'write' => []];

for ($run = 0; $run < $runs; $run++) {
    $start = hrtime(true);
    $atOnce();
    $timings['atOnce'][] = (hrtime(true) - $start) / 1e6;

    $start = hrtime(true);
    $document = $oneByOne();
    $timings['oneByOne'][] = (hrtime(true) - $start) / 1e6;

    $start = hrtime(true);
    $document->toJson(Version::V3_0_4);
    $timings['write'][] = (hrtime(true) - $start) / 1e6;
}

printf("PHP %s, %d operations, %d paths, %d schemas, %.0f KiB of JSON, median of %d runs\n", PHP_VERSION, $resources * 5, $resources * 2, $resources, strlen($json) / 1024, $runs);
printf("%-34s %8.2f ms\n", 'Build, paths given at once', median($timings['atOnce']));
printf("%-34s %8.2f ms\n", 'Build, addPaths() path by path', median($timings['oneByOne']));
printf("%-34s %8.2f ms\n", 'Write as 3.0.4 JSON', median($timings['write']));
printf("%-34s %8.1f MiB\n", 'Peak memory', memory_get_peak_usage(true) / 1048576);

/**
 * @param list<float> $timings at least one
 */
function median(array $timings): float
{
    sort($timings);
    $middle = intdiv(count($timings), 2);

    return count($timings) % 2 === 1 ? $timings[$middle] ?? 0.0 : (($timings[$middle - 1] ?? 0.0) + ($timings[$middle] ?? 0.0)) / 2;
}
