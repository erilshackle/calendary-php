# Contributing to Calendary

Thank you for considering contributing to Calendary.

Calendary aims to remain small, predictable, framework-agnostic, and focused on scheduling and availability.

## Before Contributing

For bug fixes, documentation improvements, and small changes, feel free to open a pull request directly.

For larger features or changes to the public API, please open a GitHub Discussion first so the design can be discussed before implementation.

## Development Setup

Clone the repository and install the dependencies:

```bash
composer install
```

Run the test suite:

```bash
composer test
```

Validate the Composer configuration:

```bash
composer validate
```

## Pull Requests

Keep pull requests focused on a single change.

Before submitting a pull request:

* make sure all tests pass;
* add tests for new behavior or bug fixes;
* update the documentation when public behavior changes;
* avoid introducing framework or database dependencies;
* preserve backward compatibility within the current major version.

## Code Style

Follow the existing code style and project structure.

Prefer:

* strict types;
* explicit types;
* small and focused classes;
* clear method names;
* minimal abstractions;
* framework-independent implementations.

Avoid adding dependencies unless they provide clear value that cannot reasonably be implemented within the library.

## Tests

Changes that affect behavior should include tests.

Tests should verify observable behavior rather than internal implementation details whenever possible.

```bash
composer test
```

## Documentation

When changing the public API or scheduling behavior, update the relevant documentation under `docs/` and, when appropriate:

* `README.md`
* `CHANGELOG.md`
* `llms.txt`

Documentation can be validated locally with:

```bash
mkdocs build --strict
```

## Bug Reports

When reporting a bug, include:

* Calendary version;
* PHP version;
* minimal schedule configuration;
* code needed to reproduce the issue;
* expected behavior;
* actual behavior.

A minimal reproducible example is strongly preferred.

## Feature Requests

Calendary intentionally has a small scope.

Features should represent generally useful scheduling or availability concepts rather than application-specific business logic.

Integrations with databases, ORMs, frameworks, HTTP layers, authentication systems, or application models should normally remain outside the core library.

## Backward Compatibility

Calendary follows Semantic Versioning.

Within a major version, changes should avoid breaking the documented public API whenever possible.

Breaking changes should be reserved for a new major release.
