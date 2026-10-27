# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

`ark4ne/openapi-writer` is a **framework-agnostic** PHP library that builds and writes OpenAPI documents
(3.0.0 → 3.0.4, 3.1.0 → 3.1.2, 3.2.0) through a semantic, fluent, immutable object API in the spirit of
`goldspecdigital/oooas`, which it replaces. Its first consumer is `ark4ne/laravel-openapi-generator` V1, but the
library must never depend on Laravel or any other framework.

- Specification (source of truth): https://claude.ai/artifact/PKmRGrPDwdnietSK4Aik3X
- **Architecture, patterns and conventions: `docs/architecture.md` — read it before writing any code.**
- Roadmap and progress: `docs/README.md`, one document per milestone in `docs/M*.md`

Out of scope: reading/parsing OpenAPI files, scanning PHP code, resolving external `$ref`, HTTP validation, UIs.

## Commands

```bash
composer test            # whole PHPUnit suite
composer test:coverage   # coverage (pcov/Xdebug), fails under 95% of lines
composer stan            # PHPStan max level on src/, tests/, bin/
composer cs              # code style check (PER-CS); composer cs:fix to fix
composer progress        # rebuild the progress table of docs/README.md
composer ci              # every blocking check except coverage and mutation

vendor/bin/phpunit --filter test_name            # single test
vendor/bin/phpunit --testsuite Compatibility     # single suite
```

Runtime requirements are only `php ^8.2` and `ext-json`; never add a mandatory runtime dependency
(`symfony/yaml` and the JSON Schema validator stay in `suggest`).

## Workflow: tests first

Milestones run in order: M0 bootstrap → M1 API contract (signatures, no logic) → M2 full test suite (red) →
M3 objects + 3.1 serialization → M4 3.0 adapter → M5 3.2 adapter → M6 validation → M7 release 1.0.0.

- Never implement behaviour that has no test yet. During M1, method bodies return neutral values
  (`clone $this`, `[]`, `''`, `null`) so that M2 tests fail on assertions, not on fatal errors.
- When a deliverable is done, tick it (`- [x]`) in its `docs/M*.md`, run `composer progress`, and commit both.
  The CI fails when `docs/README.md` is out of sync with the checklists.

## Architecture (details in `docs/architecture.md`)

The model **describes**, serialization **decides**. One field = one annotated property; one version difference =
one class.

- Layers, each depending only on those to its left: `Support` ← `Enums` ← `Metadata` ← `Objects`/`Contracts` ←
  `Serialization` ← `Validation` (`Exceptions` usable everywhere). Only `SpecObject`'s `toArray/toJson/toYaml`
  facades may call `Serialization`. Enforced by `phpat` architecture tests.
- Objects (`src/Objects/`) are `final`, immutable value objects extending `SpecObject` / `ExtensibleSpecObject`:
  `final protected` constructor, `create(?string $objectId)` + semantic shortcuts (`Schema::string()`,
  `Operation::get()`…), one setter per spec field with **exactly** the spec name, each doing
  `return $this->with('field', $value);`. Properties are declared in spec order (= output key order) as
  `#[Field] protected T|Undefined $x = Undefined::Value;` and read through `__get` (`@property-read`).
- `Undefined` (sentinel enum) ≠ `null`: typed setters reset on `null`; free-value fields (`default`, `example`,
  `const`) write `null`; `without('field')` removes. `Undefined` never appears in a public signature.
- Version availability is declared, never coded: `#[Field(since: Version::V3_1_0)]`, `VersionedEnum::since()`.
- "X or Reference" unions are interfaces in `src/Contracts/` (`SchemaContract`…). Every list/map field has
  two variadic methods returning clones: the spec-named setter **replaces** (`servers(...)`), and `add` + the exact
  field name **appends** (`addServers(...)`, no singularization). Maps are keyed by `objectId`; a missing or
  already present `objectId` throws, in both methods (never a silent overwrite).
- Serialization: `Normalizer` (visitor, no version rule inside) → `RewriterChain` (one `Rewriter` per row of the
  spec's version tables, handling every version) → `Encoder` (JSON, optional YAML). `Diagnostics` collects
  everything; strict mode (default) throws one `UnsupportedFeatureException` with all of them, lenient mode drops
  and reports. Default target version: 3.1.2.
- Validation: one `Rule` class per semantic rule, run on the **serialized** document; `Violations` collects all.
- Exceptions implement `OpenApiWriterException`, extend the matching SPL exception, and are built with named
  constructors (`InvalidArgumentException::forField(...)`).
- No traits in `src/`, no singletons or mutable static state (only `MetadataFactory`'s pure cache),
  `declare(strict_types=1)`, PHPStan max, `mixed` only for free values. Optional dependencies sit behind a port
  (`Encoder`, `JsonSchemaValidator`).

## Tests

- Every object test extends the abstract `SpecObjectTestCase` (immutability, `objectId`, extensions, each field
  via a `fields()` provider). Reference documents come from the Object Mother `tests/Support/Documents.php`.
  Shared assertions live in a base `TestCase`, not in traits.
- Suites: `tests/Unit` (one class per object), `tests/Compatibility` (every version difference × every version),
  `tests/Conformance` (official OpenAPI Initiative examples), `tests/Validation` (valid + invalid case per rule).
- Expected outputs are JSON files in `tests/fixtures/<version>/`, never inline strings.
- Every serialized document in a test is also validated against the official schema of its version.
- Test names are sentences: `test_nullable_string_becomes_type_array_in_3_1`.
- Incomplete, skipped or risky tests, warnings, notices and deprecations fail the build.

## Languages

Code, identifiers, comments, README, CONTRIBUTING and CHANGELOG are in English. `docs/` is written in French.
