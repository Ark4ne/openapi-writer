# Contributing

## Setup

```bash
composer install
```

## Checks

Every check below runs in the CI on each push and pull request.

| Command | What it does |
| --- | --- |
| `composer test` | Runs the whole PHPUnit suite |
| `composer test:coverage` | Runs the tests with coverage (pcov or Xdebug) and fails under 95% of lines |
| `composer stan` | PHPStan at max level on `src/`, `tests/` and `bin/` |
| `composer cs` / `composer cs:fix` | Checks / fixes the code style (PER-CS) |
| `composer mutation` | Mutation testing, minimum 85% MSI (needs `infection` installed globally) |
| `composer progress` | Rebuilds the progress table of `docs/README.md` from the milestone checklists |
| `composer ci` | Every blocking check except coverage and mutation |

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

Expected outputs live as JSON files in `tests/fixtures/<version>/`, never as strings in the tests.
Incomplete or skipped tests fail the build.
