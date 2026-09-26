# Querying

Calendar resolution begins with:

```php
$query = $calendar->query();
````

Queries do not modify the underlying calendar definition.

## Single Date

```php
$day = $calendar
    ->query()
    ->on('2026-10-05');
```

Returns a `Day`.

## Week

```php
$week = $calendar
    ->query()
    ->weekOf('2026-10-07');
```

`weekOf()` returns the ISO week containing the date:

```text
Monday → Sunday
```

## Range

```php
$range = $calendar
    ->query()
    ->between(
        '2026-10-01',
        '2026-10-31'
    );
```

Ranges are inclusive.

## Filtering by Status

`open` `closed` `full` `day_off` `holiday`

```php
$result = $calendar
    ->query()
    ->status('open')
    ->between($from, $to);
```

Multiple statuses may be provided:

```php
$result = $calendar
    ->query()
    ->status('open', 'full')
    ->between($from, $to);
```

### Day Statuses

Each resolved day has one of the following statuses:

| Status | Meaning |
| --- | --- |
| `open` | The day has at least one available slot. |
| `closed` | No availability schedule applies to the day. |
| `full` | Availability exists, but all generated slots are busy. |
| `day_off` | The day was explicitly marked as a day off. |
| `holiday` | The day was explicitly marked as a holiday. |

The main distinction between `closed` and `full` is that a `closed` day has
no applicable availability, while a `full` day has availability but no
remaining available slots.

## Available Days

```php
$result = $calendar
    ->query()
    ->available()
    ->between($from, $to);
```

`available()` keeps only days containing at least one available slot.

## Available Slots

```php
$result = $calendar
    ->query()
    ->availableSlots()
    ->between($from, $to);
```

This removes busy slots from returned `Day` objects.

It does not recalculate the original status of the day.

## Selecting Fields

```php
$result = $calendar
    ->query()
    ->select('date', 'status')
    ->between($from, $to);
```

Available fields are:

```text
date
weekday
available
status
slots
```

`select()` controls serialization only.

It does not remove information from the `Day` object.

```php
$day = $calendar
    ->query()
    ->select('status')
    ->on('2026-10-05');

$day->toArray();
// ['status' => 'open']

$day->date();
$day->slots();
$day->available();
```

All domain methods remain available.

## Combining Filters

Query operations can be combined:

```php
$result = $calendar
    ->query()
    ->status('open')
    ->availableSlots()
    ->select('date', 'slots')
    ->between(
        '2026-10-05',
        '2026-10-12'
    );
```

This means:

1. keep open days;
2. remove busy slots;
3. serialize only the date and slots;
4. resolve the requested range.
