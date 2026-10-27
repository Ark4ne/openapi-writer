# Contributing

## Setup

```bash
composer install
```

The design is documented (in French) in [`docs/architecture.md`](docs/architecture.md): read it before writing code.
In short, the model **describes** and the serialization **decides**: one field of the specification is one annotated
property, one difference between versions is one class.

## Checks

Every check below runs in the CI on each push and pull request.

| Command | What it does |
| --- | --- |
| `composer test` | Runs the whole PHPUnit suite |
| `composer test:coverage` | Runs the tests with coverage (pcov or Xdebug) and fails under 95% of lines |
| `composer stan` | PHPStan at max level on `src/`, `tests/` and `bin/`, with the `phpat` architecture rules |
| `composer cs` / `composer cs:fix` | Checks / fixes the code style (PER-CS) |
| `composer mutation` | Mutation testing, minimum 85% MSI (needs `infection` installed globally) |
| `composer progress` | Rebuilds the progress table of `docs/README.md` from the milestone checklists |
| `composer ci` | Every blocking check except coverage and mutation |
| `composer fixtures:update` | Rewrites the expected JSON files from the current output: review the diff |
| `composer benchmark` | Builds and writes a document of 500 operations, and prints the median timings |

## Workflow

Tests come first: never add behaviour without a test that fails before it.

- Expected outputs are JSON files in `tests/fixtures/<line>/` (`3.0`, `3.1`, `3.2`), never strings in the tests.
  Every serialized document of a test is also validated against the official schema of its version.
- Test names are sentences: `test_nullable_string_becomes_type_array_in_3_1`.
- Incomplete, skipped or risky tests, warnings, notices and deprecations fail the build.
- The PHP examples of the README run in `tests/Unit/ReadmeTest.php`: keep them working.

## Adding a field

Example: a field `Tag.kind`, known from 3.2.0.

### 1. The object

Declare the property at its place in the specification order (the output follows it), with the first version that
knows it, then add the setter named exactly like the field, and its `@property-read`:

```php
/**
 * @property-read string|null $kind
 */
final class Tag extends ExtensibleSpecObject
{
    // …
    #[Field(since: Version::V3_2_0)] protected string|Undefined $kind = Undefined::Value;

    public function kind(?string $kind): static
    {
        return $this->with('kind', $kind);
    }
}
```

- A list or map field has two methods: the field name replaces, `add` + the field name appends
  (`tags()` / `addTags()`), both variadic; map entries are keyed by their `objectId`.
- A field that can be an object or a Reference takes the contract interface (`SchemaContract`…).
- A field holding a free value (`default`, `example`) is typed `mixed` and writes `null` as is.
- A value the specification forbids in every version throws an `InvalidArgumentException` in the setter; a field
  that excludes another one declares it with `exclusiveOf()`.

### 2. The versions

- A field that does not exist in an older version needs nothing more than `since` (or `until`): the serializer drops
  it, reports it, and throws in strict mode.
- A field that exists in every version but is written differently needs a rewriter: create
  `src/Serialization/Rewriters/<Feature>Rewriter.php`, which reads the intent in the model and writes it for
  `$context->version`, reporting a `Converted` or `Unsupported` diagnostic, then add it at its place in
  `RewriterChain::default()`.

### 3. The tests

- `tests/Unit/Objects/<Object>Test.php`: add the field to `fields()` (`'kind' => [['nav'], 'nav']`, the arguments of
  the setter and the value read back). The shared `SpecObjectTestCase` then checks the setter, the immutability, the
  reset on `null` and, for lists and maps, both methods.
- `tests/Compatibility/`: add a row for each version difference, in `UnsupportedFeaturesTest` (a field that some
  versions cannot write, with its first version and the reported pointers) or in `ConversionsTest` (a field
  converted between versions, with its fixture `tests/fixtures/<line>/compatibility/<row>.json` and the reported
  `Converted` diagnostics). A new rewriter also gets its place checked in `RewriterChainTest`.

### 4. The fixtures

Run `composer fixtures:update`: it writes the missing or changed files of `tests/fixtures/<line>/` from the current
output, for the latest version of each line, and only when that output is valid against the official schema. Read the
diff before committing it: the fixtures are the expected output.

### 5. The documentation

Mention the field in the `CHANGELOG.md`, and in `docs/architecture.md` if it introduces a new rule.

## Adding a validation rule

Create `src/Validation/Rules/<Name>Rule.php`, implementing `Rule`: it reads the **serialized** document (an array,
with `\stdClass` for empty objects) and adds a `Violation` for each problem. Register it in `Validator::create()`, and
add a valid and an invalid document per case in `tests/Validation/Rules/<Name>RuleTest.php`, with their fixtures
in `tests/fixtures/<line>/validation/`.

## Roadmap

The roadmap lives in [`docs/`](docs/README.md) (written in French): one document per milestone (M0 → M7) with its
checklist. When a deliverable is done, tick it in its milestone document, run `composer progress`, and commit both
files: the CI fails when the progress table is out of date.

## Test suites

| Suite | Folder | Purpose |
| --- | --- | --- |
| Unit | `tests/Unit` | One test class per object: fields, shortcuts, immutability, extensions |
| Compatibility | `tests/Compatibility` | Every version difference × every target version |
| Conformance | `tests/Conformance` | Official OpenAPI Initiative examples rebuilt with the API |
| Validation | `tests/Validation` | One valid and one invalid document per validation rule |

Architecture tests (`tests/Architecture`, `phpat`) run with PHPStan.

## Releasing

1. Move the `[Unreleased]` entries of `CHANGELOG.md` under the new version and its date.
2. Check that the CI is green on `main`.
3. Tag `vX.Y.Z` on `main` and push the tag: Packagist updates from the GitHub hook.
