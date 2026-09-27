# Busy Time

Busy periods represent time that would otherwise be available but cannot
currently be used.

They are normally populated from appointments, reservations or external
calendar events.

## Busy Datetime

```php
$calendar->busy([
    ['2026-10-05 10:00'],
]);
````

When only a start datetime is supplied, Calendary uses the configured
duration.

With:

```php
$calendar->duration(60);
```

the busy period is:

```text
10:00 - 11:00
```

## Explicit Busy Period

Provide both boundaries when the occupied period has an explicit duration:

```php
$calendar->busy([
    [
        '2026-10-05 14:20',
        '2026-10-05 15:40',
    ],
]);
```

Any generated slot overlapping that period becomes busy.

## Busy Date

A date without a time blocks the entire date:

```php
$calendar->busy([
    ['2026-10-06'],
]);
```

## Multiple Busy Periods

```php
$calendar->busy([
    ['2026-10-05 10:00'],
    ['2026-10-05 14:20', '2026-10-05 15:40'],
    ['2026-10-06'],
]);
```

## Break Time

`breakTime()` defines additional unavailable time after a booking whose end is inferred by Calendary.

```php id="8x8rpb"
$calendar
    ->duration(60)
    ->breakTime(15)
    ->busy([
        ['2026-10-05 09:00'],
    ]);
```

The booking itself still lasts 60 minutes:

```text id="p5v1rp"
09:00 ─────── booking ─────── 10:00
```

but the calendar remains unavailable for another 15 minutes:

```text id="g6hmhn"
09:00 ─────── booking ─────── 10:00 ─ break ─ 10:15
```

The effective busy period is therefore `[09:00, 10:15)`.

Break time does not change the duration of generated slots.

### Explicit Busy Periods

Break time is only applied when Calendary needs to infer the end of a busy period.

When both the start and end are explicitly provided, the period is respected exactly as given:

```php id="eflgx7"
$calendar
    ->breakTime(15)
    ->busy([
        ['2026-10-05 09:00', '2026-10-05 10:30'],
    ]);
```

The busy period remains `[09:00, 10:30)`. It is not extended to `10:45`.

Whole-day busy entries are also not extended.

| Busy definition | Resolution                     |
| --------------- | ------------------------------ |
| `[date]`        | Blocks the whole day           |
| `[datetime]`    | Uses `duration + breakTime`    |
| `[start, end]`  | Uses the exact explicit period |


## Period Overlap

Calendary uses half-open intervals:

```text
[start, end)
```

For example:

```text
busy: 10:00 - 11:00
slot: 11:00 - 12:00
```

These periods do not overlap.

But:

```text
busy: 10:30 - 11:30
slot: 11:00 - 12:00
```

does overlap.

## Multi-Day Busy Periods

Explicit busy periods may cross date boundaries:

```php
$calendar->busy([
    [
        '2026-10-05 18:00',
        '2026-10-08 09:00',
    ],
]);
```

Calendary checks period overlap for every resolved date affected by the
busy period.

## Busy vs Days Off

Busy time and closed dates represent different concepts.

Use `busy()` when availability exists but time has become occupied.

Use `daysOff()` or `holidays()` when the schedule itself should not operate
on that date.
