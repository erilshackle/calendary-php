# API Reference

This page provides a quick reference for Calendary's public API.

## Calendary

### `Calendary::load()`

```php
Calendary::load(array $schedule): Calendary
```

Creates a calendar from weekly and date-specific availability.

### `timezone()`

```php
$calendar->timezone(string|DateTimeZone $timezone): Calendary
```

Sets the timezone used during calendar resolution.

### `duration()`

```php
$calendar->duration(int $minutes): Calendary
```

Sets generated slot duration.

### `interval()`

```php
$calendar->interval(int $minutes): Calendary
```

Sets the interval between candidate slot starts.

### `breakTime()`

```php
$calendar->breakTime(15);
```

Sets the additional unavailable time after a busy entry whose end is inferred from the configured duration.
The default value is `0`.

### `busy()`

```php
$calendar->busy(array $busy): Calendary
```

Adds busy dates or time periods.

### `daysOff()`

```php
$calendar->daysOff(array $dates): Calendary
```

Marks dates as days off.

### `holidays()`

```php
$calendar->holidays(array $dates): Calendary
```

Marks dates as holidays.

### `query()`

```php
$calendar->query(): CalendarQuery
```

Starts a calendar query.

---

## CalendarQuery

### `select()`

```php
$query->select(string ...$fields): CalendarQuery
```

Controls which Day fields are serialized.

Accepted fields:

`date` `weekday` `available` `status` `slots` 


### `status()`

```php
$query->status(string ...$statuses): CalendarQuery
```

Filters resolved days by status.

Accepted statuses:

`open` `closed` `full` `day_off` `holiday`

### `available()`

```php
$query->available(): CalendarQuery
```

Returns only days containing at least one available slot.

### `availableSlots()`

```php
$query->availableSlots(): CalendarQuery
```

Removes busy slots from returned Day objects.

### `on()`

```php
$query->on(string|DateTimeInterface $date): Day
```

Resolves one date.

### `weekOf()`

```php
$query->weekOf(string|DateTimeInterface $date): Range
```

Resolves the ISO week containing a date.

### `between()`

```php
$query->between(
    string|DateTimeInterface $from,
    string|DateTimeInterface $to
): Range
```

Resolves an inclusive date range.

---

## Day

```php
$day->date(): DateTimeImmutable;

$day->weekday(): int;

$day->status(): string;

$day->slots(): array;

$day->availableSlots(): array;

$day->available(): bool;

$day->isAvailable(string $time): bool;

$day->toArray(): array;

$day->toJson(int $flags = 0): string;
```

---

## Slot

```php
$slot->start(): DateTimeImmutable;

$slot->end(): DateTimeImmutable;

$slot->status(): string;

$slot->available(): bool;

$slot->toArray(): array;

$slot->toJson(int $flags = 0): string;
```

---

## Range

```php
$range->from(): DateTimeImmutable;

$range->to(): DateTimeImmutable;

$range->days(): array;

$range->toArray(): array;

$range->toJson(int $flags = 0): string;
```

---

## Result

`Day`, `Slot` and `Range` implement:

```php
Eril\Calendary\Contracts\Result
```

The contract provides:

```php
public function toArray(): array;

public function toJson(int $flags = 0): string;
```

and extends PHP's `JsonSerializable`.
