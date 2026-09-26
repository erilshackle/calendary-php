# Calendary

A lightweight, framework-agnostic PHP library for resolving calendar availability, busy periods and bookable time slots.

Calendary lets you define **when a resource can be available**, add periods when it is unavailable, and query the resulting calendar without coupling your scheduling logic to a database, framework or external calendar provider.

```php
use Eril\Calendary\Calendary;

$calendar = Calendary::load([
    'weekly' => [
        1 => [['09:00', '12:00'], ['14:00', '18:00']],
        2 => [['09:00', '18:00']],
        3 => [['09:00', '18:00']],
        4 => [['09:00', '18:00']],
        5 => [['09:00', '17:00']],
    ],

    'dates' => [
        '2026-10-08' => [['10:00', '14:00']],
    ],
]);

$calendar
    ->timezone('Atlantic/Cape_Verde')
    ->duration(60)
    ->interval(30)
    ->daysOff(['2026-10-07'])
    ->holidays(['2026-10-12'])
    ->busy([
        ['2026-10-05 10:00'],
        ['2026-10-05 14:20', '2026-10-05 15:40'],
    ]);

$result = $calendar
    ->query()
    ->available()
    ->availableSlots()
    ->between('2026-10-05', '2026-10-12');
```

## Features

* Recurring weekly availability
* Date-specific availability overrides
* Configurable slot duration and interval
* Busy dates and time periods
* Days off and holidays
* Timezone-aware date resolution
* Available and busy slot detection
* Single-day, weekly and range queries
* Day status filtering
* Available-slot filtering
* Selectable output fields
* Array and JSON serialization
* Framework and database independent

## Requirements

* PHP 8.2 or later

## Installation

Install Calendary using Composer:

```bash
composer require eril/calendary
```

## Quick Start

Create a calendar by defining its recurring weekly availability:

```php
use Eril\Calendary\Calendary;

$calendar = Calendary::load([
    'weekly' => [
        1 => [['09:00', '12:00'], ['14:00', '18:00']],
        2 => [['09:00', '18:00']],
        3 => [['09:00', '18:00']],
        4 => [['09:00', '18:00']],
        5 => [['09:00', '17:00']],
    ],
]);

$calendar
    ->timezone('Atlantic/Cape_Verde')
    ->duration(60);
```

Weekly availability uses ISO-8601 weekdays:

| Value | Day       |
| ----- | --------- |
| `1`   | Monday    |
| `2`   | Tuesday   |
| `3`   | Wednesday |
| `4`   | Thursday  |
| `5`   | Friday    |
| `6`   | Saturday  |
| `7`   | Sunday    |

You can then resolve a date:

```php
$day = $calendar
    ->query()
    ->on('2026-10-05');
```

And inspect the result:

```php
$day->status();
$day->available();
$day->slots();
$day->availableSlots();

$day->toArray();
$day->toJson();
```

## Availability

### Weekly availability

The `weekly` definition represents recurring availability.

```php
$calendar = Calendary::load([
    'weekly' => [
        1 => [
            ['09:00', '12:00'],
            ['14:00', '18:00'],
        ],

        2 => [
            ['09:00', '18:00'],
        ],
    ],
]);
```

A resource may have multiple availability periods on the same day.

### Date-specific availability

Use `dates` to define availability for a specific date:

```php
$calendar = Calendary::load([
    'weekly' => [
        4 => [['09:00', '18:00']],
    ],

    'dates' => [
        '2026-10-08' => [['10:00', '14:00']],
    ],
]);
```

Date-specific availability completely overrides weekly availability for that date.

In the example above, October 8 is available from `10:00` to `14:00`, regardless of the recurring Thursday schedule.

An empty date definition explicitly closes the date:

```php
'dates' => [
    '2026-10-08' => [],
]
```

## Duration and Interval

### Duration

The duration defines how long each generated slot lasts.

```php
$calendar->duration(60);
```

With availability from `09:00` to `12:00`, this produces:

```text
09:00 - 10:00
10:00 - 11:00
11:00 - 12:00
```

### Interval

The interval defines the distance between candidate slot start times.

```php
$calendar
    ->duration(60)
    ->interval(30);
```

This produces:

```text
09:00 - 10:00
09:30 - 10:30
10:00 - 11:00
10:30 - 11:30
11:00 - 12:00
```

A slot is generated only when its complete duration fits inside the availability period.

When no interval is explicitly configured, the duration is used as the interval.

## Busy Time

Busy periods represent time that would otherwise be available but is already occupied.

```php
$calendar->busy([
    ['2026-10-05 10:00'],
    ['2026-10-05 14:20', '2026-10-05 15:40'],
    ['2026-10-06'],
]);
```

Calendary supports three forms.

A date blocks the complete day:

```php
['2026-10-06']
```

A datetime blocks a period using the configured duration:

```php
['2026-10-05 10:00']
```

With a duration of 60 minutes, this represents `10:00` to `11:00`.

An explicit start and end define the exact busy period:

```php
[
    '2026-10-05 14:20',
    '2026-10-05 15:40',
]
```

Busy periods may span multiple calendar dates.

Calendary uses half-open time periods:

```text
[start, end)
```

Therefore a busy period from `10:00` to `11:00` does not conflict with a slot beginning exactly at `11:00`.

## Days Off and Holidays

Dates may be explicitly blocked as days off:

```php
$calendar->daysOff([
    '2026-10-07',
    '2026-10-09',
]);
```

Or holidays:

```php
$calendar->holidays([
    '2026-12-25',
    '2027-01-01',
]);
```

Both take precedence over weekly and date-specific availability.

They remain separate concepts so consumers can distinguish their reason through the resolved day status.

## Timezones

Set the timezone used for calendar resolution:

```php
$calendar->timezone('Atlantic/Cape_Verde');
```

A `DateTimeZone` instance is also accepted:

```php
$calendar->timezone(
    new DateTimeZone('Atlantic/Cape_Verde')
);
```

When no timezone is explicitly configured, Calendary uses PHP's current default timezone.

## Querying

Queries are created using:

```php
$query = $calendar->query();
```

### Single date

```php
$day = $calendar
    ->query()
    ->on('2026-10-05');
```

Returns a `Day`.

### Week

```php
$week = $calendar
    ->query()
    ->weekOf('2026-10-07');
```

Returns the ISO week containing the date, from Monday through Sunday.

### Date range

```php
$range = $calendar
    ->query()
    ->between(
        '2026-10-01',
        '2026-10-31'
    );
```

Ranges are inclusive.

Both October 1 and October 31 belong to the requested range.

## Filtering Days

Filter results by day status:

```php
$result = $calendar
    ->query()
    ->status('open')
    ->between($from, $to);
```

Multiple statuses may be accepted:

```php
$result = $calendar
    ->query()
    ->status('open', 'full')
    ->between($from, $to);
```

Available day statuses are:

```text
open
closed
full
day_off
holiday
```

You may also return only days containing at least one available slot:

```php
$result = $calendar
    ->query()
    ->available()
    ->between($from, $to);
```

## Filtering Slots

Use `availableSlots()` to remove busy slots from returned days:

```php
$day = $calendar
    ->query()
    ->availableSlots()
    ->on('2026-10-05');
```

This affects the returned slot collection but does not change the resolved status of the day.

It can be combined with day filters:

```php
$result = $calendar
    ->query()
    ->status('open')
    ->availableSlots()
    ->between($from, $to);
```

## Selecting Output Fields

Use `select()` to control which `Day` fields are included during serialization:

```php
$result = $calendar
    ->query()
    ->select('date', 'status')
    ->between($from, $to);
```

Each serialized day will contain only:

```php
[
    'date' => '2026-10-05',
    'status' => 'open',
]
```

Available fields are:

```text
date
weekday
available
status
slots
```

Selection affects serialization only.

The result object still retains its complete domain data:

```php
$day = $calendar
    ->query()
    ->select('status')
    ->on('2026-10-05');

$day->toArray();
// ['status' => 'open']

$day->date();       // still available
$day->slots();      // still available
$day->available();  // still available
```

When used with a range, `select()` applies to each `Day`. Range metadata remains available:

```php
$result = $calendar
    ->query()
    ->select('date', 'status')
    ->between(
        '2026-10-05',
        '2026-10-11'
    )
    ->toArray();
```

Example:

```php
[
    'from' => '2026-10-05',
    'to' => '2026-10-11',
    'timezone' => 'Atlantic/Cape_Verde',

    'days' => [
        [
            'date' => '2026-10-05',
            'status' => 'open',
        ],
        [
            'date' => '2026-10-06',
            'status' => 'open',
        ],
    ],
]
```

## Day Results

A resolved `Day` provides:

```php
$day->date();
$day->weekday();

$day->status();
$day->available();

$day->slots();
$day->availableSlots();

$day->isAvailable('10:00');

$day->toArray();
$day->toJson();
```

Possible statuses are:

```php
Day::OPEN;
Day::CLOSED;
Day::FULL;
Day::DAY_OFF;
Day::HOLIDAY;
```

Their meanings are:

| Status    | Meaning                                               |
| --------- | ----------------------------------------------------- |
| `open`    | At least one generated slot is available              |
| `closed`  | No availability is defined for the date               |
| `full`    | Availability exists, but all generated slots are busy |
| `day_off` | The date was explicitly marked as a day off           |
| `holiday` | The date was explicitly marked as a holiday           |

## Slot Results

Each `Slot` provides:

```php
$slot->start();
$slot->end();

$slot->status();
$slot->available();

$slot->toArray();
$slot->toJson();
```

Possible statuses are:

```php
Slot::AVAILABLE;
Slot::BUSY;
```

`start()` and `end()` return `DateTimeImmutable` instances.

## Range Results

A range provides:

```php
$range->from();
$range->to();
$range->days();

$range->toArray();
$range->toJson();
```

Query filters may cause some dates inside the requested range to be absent from `days()`.

For example:

```php
$range = $calendar
    ->query()
    ->status('open')
    ->between(
        '2026-10-01',
        '2026-10-31'
    );
```

The requested boundaries remain October 1 and October 31, while `days()` contains only matching days.

## Checking a Booking Time

The same calendar rules used to display availability can also be used to validate a requested booking time:

```php
```
