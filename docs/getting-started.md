# Getting Started

## Installation

Install Calendary using Composer:

```bash
composer require eril/calendary
````

Then import the main class:

```php
use Eril\Calendary\Calendary;
```

## Creating a Calendar

Availability is loaded using `Calendary::load()`:

```php
$calendar = Calendary::load([
    'weekly' => [
        1 => [['09:00', '12:00'], ['14:00', '18:00']],
        2 => [['09:00', '18:00']],
        3 => [['09:00', '18:00']],
        4 => [['09:00', '18:00']],
        5 => [['09:00', '17:00']],
    ],
]);
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

## Configure the Calendar

```php
$calendar
    ->timezone('Atlantic/Cape_Verde')
    ->duration(60)
    ->interval(30);
```

`duration()` controls the length of each generated slot.

`interval()` controls the distance between candidate slot start times.

When no interval is configured, the duration is used as the interval.

## Add Occupancy

```php
$calendar->busy([
    ['2026-10-05 10:00'],
    ['2026-10-05 14:20', '2026-10-05 15:40'],
]);
```

## Query a Date

```php
$day = $calendar
    ->query()
    ->on('2026-10-05');
```

You can then inspect the resolved result:

```php
$day->status();
$day->available();
$day->slots();
$day->availableSlots();
```

Or serialize it:

```php
$array = $day->toArray();

$json = $day->toJson();
```

## Query a Range

```php
$range = $calendar
    ->query()
    ->between(
        '2026-10-05',
        '2026-10-12'
    );
```

Ranges are inclusive.

## A Typical Availability Query

For an API or booking interface, you may want only open days and available
slots:

```php
$result = $calendar
    ->query()
    ->available()
    ->availableSlots()
    ->select('date', 'slots')
    ->between(
        '2026-10-05',
        '2026-10-12'
    );

return $result->toArray();
```

The same calendar can later be used to validate a requested booking:

```php
$day = $calendar
    ->query()
    ->on('2026-10-05');

if (!$day->isAvailable('10:00')) {
    throw new DomainException(
        'The requested time is not available.'
    );
}
```
