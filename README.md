# OpenAPI Writer

Framework-agnostic, fluent and immutable PHP builder for **OpenAPI 3.0, 3.1 and 3.2** documents.

> **Status: work in progress.** The public API is not available yet (milestone M0: repository bootstrap).

## Goals

- One semantic object per object of the specification, named exactly like the specification.
- One model for every version: the target version (3.0.x, 3.1.x or 3.2.0) is chosen when writing.
- Strict by default: a feature the target version cannot express raises an exception;
  a lenient mode drops it and reports a diagnostic.
- Validation against the official JSON schemas plus semantic rules.
- No runtime dependency besides `ext-json`. PHP 8.2+.

## Requirements

- PHP `^8.2`
- `symfony/yaml` (optional) to write YAML

## License

MIT
