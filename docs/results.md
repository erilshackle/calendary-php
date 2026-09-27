# Results

Calendary returns result objects rather than raw arrays.

The primary result types are:

```text
Day
Slot
Range
````

Each implements the `Result` contract and supports:

```php
$result->toArray();
$result->toJson();
```

They are also JSON serializable.

## Day

A `Day` represents the resolved state of one calendar date.

```php
$day->date();
$day->weekday();

$day->status();
$day->available();

$day->slots();
$day->availableSlots();

$day->isAvailable('10:00');
```

### Day Statuses

| Status    | Meaning                                              |
| --------- | ---------------------------------------------------- |
| `open`    | At least one generated slot is available             |
| `closed`  | No availability applies to the date                  |
| `full`    | Availability exists but all generated slots are busy |
| `day_off` | The date is explicitly marked as a day off           |
| `holiday` | The date is explicitly marked as a holiday           |

Constants are available:

```php
Day::OPEN;
Day::CLOSED;
Day::FULL;
Day::DAY_OFF;
Day::HOLIDAY;
```

## Slot

A `Slot` represents one generated time period.

```php
$slot->start();
$slot->end();

$slot->status();
$slot->available();
```

Slot statuses are:

`available`
`busy`

Constants:

```php
Slot::AVAILABLE;
Slot::BUSY;
```

`start()` and `end()` return `DateTimeImmutable`.

## Range

A `Range` contains resolved days for a requested date range.

```php
$range->from();
$range->to();
$range->days();
```

The requested range boundaries remain unchanged when filters are used.

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

`from()` and `to()` still represent the requested October range, while
`days()` contains only matching days.

## Serialization

```php
$data = $result->toArray();
```

Or:

```php
$json = $result->toJson();
```

You may also use normal JSON serialization:

```php
$json = json_encode(
    $result,
    JSON_THROW_ON_ERROR
);
```
