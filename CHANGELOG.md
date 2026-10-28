# Changelog

All notable changes to this project are documented in this file.
The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and the project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.0.0] - Unreleased

First release.

### Added

- **Object model**: the 29 objects of the OpenAPI specification (`OpenApi`, `Info`, `PathItem`, `Operation`,
  `Parameter`, `RequestBody`, `MediaType`, `Response`, `Schema`, `Components`, `SecurityScheme`…), final and
  immutable, with one method per field named exactly like the specification.
- Semantic shortcuts: `Schema::string()`, `Operation::get()`, `Parameter::path()`, `Response::ok()`,
  `MediaType::json()`, `SecurityScheme::http()`…, and `::ref()` shortcuts building the component path.
- Two methods per list or map field: the field name replaces, `add` + the field name appends. Map entries are keyed
  by their `objectId`; a missing or duplicate key throws.
- Extensions on every extensible object (`x()`), "not set" distinct from `null` for free values (`default`,
  `example`, `const`), immediate exceptions for values forbidden in every version (malformed status code, exclusive
  fields set together).
- **Serialization** to PHP arrays, JSON and YAML (`toArray()`, `toJson()`, `toYaml()` with `symfony/yaml`) for
  OpenAPI 3.0.0 → 3.0.4, 3.1.0 → 3.1.2 and 3.2.0; 3.1.2 by default.
- Conversions between versions: `nullable`, exclusive bounds, `const`, schema `examples`, several types, `null`
  type, boolean schemas, `$ref` siblings, `contentEncoding` / `contentMediaType`, empty `paths`, response
  `description`, non-OAuth scopes, reserved extension prefixes.
- Strict mode (default): one `UnsupportedFeatureException` lists every feature the target version cannot express.
  Lenient mode (`Serializer::for($version, Strictness::Lenient)`) drops them and reports each removal and conversion
  as a diagnostic with its JSON Pointer.
- Redoc vendor extensions, off by default (`VendorExtensions::Redoc`, argument of `Serializer::for()`, `toJson()`,
  `toYaml()`, `toArray()` and `Validator::validate()`): webhooks written as `x-webhooks` before 3.1, tag parents as
  `x-tagGroups` before 3.2, reported as conversions. An `x-webhooks` or `x-tagGroups` set by the document wins.
- **Validation**: `Validator` writes the document, checks it against the official schema of its version
  (`OpisJsonSchemaValidator`, with `opis/json-schema`) and runs ten semantic rules: unique `operationId`, path
  parameters, unique parameters, local references, security requirements, declared tags, tag parents, equivalent
  paths, non-empty document and value types. Rules can be removed or added (`Rule` interface).
- The official schemas of OpenAPI 3.0, 3.1 and 3.2 and the JSON Schema 2020-12 meta-schemas are embedded.
- No runtime dependency besides `ext-json`; PHP 8.2 to 8.5.
- Agent skill `skills/openapi-writer/` (rules, API reference, recipes, version matrix) for AI coding agents that use
  the library, with its examples run by the test suite.

[Unreleased]: https://github.com/ark4ne/openapi-writer/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/ark4ne/openapi-writer/releases/tag/v1.0.0
