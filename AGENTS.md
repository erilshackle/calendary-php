# AGENTS.md

## Project

Calendary is a lightweight, framework-agnostic PHP library for resolving
calendar availability, busy periods and bookable time slots.

Namespace:

```text
Eril\Calendary
````

Requirements:

* PHP 8.2+
* PHPUnit 11.5+

## Architecture

Keep the core independent of frameworks, databases, ORMs and external
calendar providers.

Main responsibilities:

```text
Calendary
    definition and configuration

CalendarQuery
    query construction and filtering

Resolver
    availability and conflict resolution

Period
    immutable time period and overlap semantics

Day / Slot / Range
    result objects
```

Do not introduce persistence or HTTP concerns into the core.

Applications are responsible for mapping external data into the structures
accepted by Calendary.

## Availability Rules

Resolution precedence is:

```text
day off / holiday
        ↓
date-specific availability
        ↓
weekly availability
        ↓
generate slots
        ↓
apply busy periods
```

Date-specific availability completely overrides weekly availability.

An empty date-specific definition explicitly closes the date.

Weekly schedules use ISO weekdays:

```text
1 = Monday
...
7 = Sunday
```

## Time Rules

`duration()` defines slot length.

`interval()` defines the distance between candidate slot starts and defaults
to the configured duration.

A generated slot must fit completely inside its availability period.

Periods use half-open semantics:

```text
[start, end)
```

Therefore a period ending at 11:00 does not overlap one beginning at 11:00.

Ranges returned by `between()` are inclusive.

## Statuses

Day statuses:

```text
open
closed
full
day_off
holiday
```

Slot statuses:

```text
available
busy
```

Do not change status semantics without updating tests and documentation.

## Public API

Keep the public API small and fluent.

Primary entry point:

```php
Calendary::load([...]);
```

Configuration:

```php
->timezone(...)
->duration(...)
->interval(...)
->busy(...)
->daysOff(...)
->holidays(...)
```

Queries:

```php
->query()->on(...)
->query()->weekOf(...)
->query()->between(...)
```

Query modifiers:

```php
->select(...)
->status(...)
->available()
->availableSlots()
```

Avoid adding framework-specific constructors such as `fromDatabase()`,
`fromModel()` or ORM-specific integrations to the core.

## Results

`Day`, `Slot` and `Range` implement:

```text
Eril\Calendary\Contracts\Result
```

Results support:

```php
$result->toArray();
$result->toJson();
```

and PHP `JsonSerializable`.

Field selection affects Day serialization only. It must not remove domain
information from the Day object.

## Code Style

* Use `declare(strict_types=1);`.
* Prefer `final` classes unless extension is intentionally supported.
* Prefer `DateTimeImmutable` for internal date/time values.
* Keep methods small and explicit.
* Avoid unnecessary abstractions.
* Preserve framework independence.
* Add PHPDoc where it improves API discovery or documents array shapes and
  literal values.

## Testing

Install dependencies:

```bash
composer install
```

Run tests:

```bash
composer test
```

Validate Composer metadata:

```bash
composer validate
```

Add or update tests whenever calendar resolution behavior changes.

Important behavior to keep covered includes:

* weekly availability;
* date-specific overrides;
* explicitly closed dates;
* duration and interval;
* busy periods;
* multi-day busy periods;
* days off and holidays;
* inclusive ranges;
* day statuses;
* query filters;
* available-slot filtering;
* field selection;
* period boundary semantics.

## Documentation

User-facing behavior changes must also be reflected in the relevant files
under `docs/` and, when appropriate, `README.md`.

Keep `llms.txt` concise and focused on helping consumers understand and use
the library.
