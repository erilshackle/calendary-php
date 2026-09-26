# Availability

Availability defines when a resource **can be available**.

It does not mean that every generated slot will actually be available.
Busy periods are applied later during calendar resolution.

## Loading Availability

`Calendary::load()` expects availability already normalized into the
Calendary schedule format.

```php
$calendar = Calendary::load([
    'weekly' => [
        1 => [['09:00', '12:00'], ['14:00', '18:00']],
        2 => [['09:00', '18:00']],
        // weekday => [[start, end], ...]
    ],

    'dates' => [
        '2026-10-08' => [['10:00', '14:00']],
        // date => [[start, end], ...]
    ],
]);
```

`weekly` uses ISO weekdays (`1` = Monday, `7` = Sunday), while `dates`
uses `YYYY-MM-DD` keys.

Calendary does not fetch availability from a database or ORM directly.
Your application should map its data into this structure before calling
`load()`.

For example:

```php
$weekly = [];

foreach ($rows as $row) {
    $weekly[$row['weekday']][] = [
        $row['start_time'],
        $row['end_time'],
    ];
}

$calendar = Calendary::load([
    'weekly' => $weekly,
]);
```

## Weekly Availability

Use `weekly` for recurring availability:

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
````

A weekday may contain multiple availability periods.

## Date-Specific Availability

Use `dates` for exceptions:

```php
$calendar = Calendary::load([
    'weekly' => [
        4 => [['09:00', '18:00']],
    ],

    'dates' => [
        '2026-10-08' => [
            ['10:00', '14:00'],
        ],
    ],
]);
```

Date-specific availability completely replaces weekly availability for that
date.

Therefore, October 8 is available from `10:00` to `14:00`, not from
`09:00` to `18:00`.

## Explicitly Closing a Date

An empty date-specific definition closes that date:

```php
'dates' => [
    '2026-10-08' => [],
]
```

This is different from simply omitting the date.

If the date is omitted, Calendary falls back to the weekly schedule.

## Resolution Precedence

Calendary resolves availability in this order:

```text
holiday / day off
        ↓
date-specific availability
        ↓
weekly availability
        ↓
generate slots
        ↓
apply busy periods
```

A holiday or day off closes the date regardless of its weekly or
date-specific availability.

## Days Off

```php
$calendar->daysOff([
    '2026-10-07',
]);
```

A resolved day will have the status:

```text
day_off
```

## Holidays

```php
$calendar->holidays([
    '2026-12-25',
    '2027-01-01',
]);
```

A resolved holiday has the status:

```text
holiday
```

## Slot Duration

```php
$calendar->duration(60);
```

For availability from `09:00` to `12:00`:

```text
09:00 - 10:00
10:00 - 11:00
11:00 - 12:00
```

## Slot Interval

The interval determines how frequently a candidate slot begins.

```php
$calendar
    ->duration(60)
    ->interval(30);
```

The same `09:00` to `12:00` availability produces:

```text
09:00 - 10:00
09:30 - 10:30
10:00 - 11:00
10:30 - 11:30
11:00 - 12:00
```

A candidate slot is included only when its complete duration fits inside
the availability period.

## Timezone

```php
$calendar->timezone('Atlantic/Cape_Verde');
```

You may also provide a `DateTimeZone`:

```php
$calendar->timezone(
    new DateTimeZone('Atlantic/Cape_Verde')
);
```

If no timezone is configured, PHP's current default timezone is used.
