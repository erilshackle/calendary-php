# Calendary

Calendary is a lightweight, framework-agnostic PHP library for resolving
calendar availability, busy periods and bookable time slots.

It lets your application define **when a resource can be available**, provide
the periods when it is already occupied, and query the resulting calendar.

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
    ->duration(60)
    ->interval(30)
    ->busy([
        ['2026-10-05 10:00'],
    ]);

$result = $calendar
    ->query()
    ->available()
    ->availableSlots()
    ->between('2026-10-05', '2026-10-09');
```

## Why Calendary?

Scheduling usually combines two different concerns:

1. **Availability** — when a resource is allowed to work or receive bookings.
2. **Occupancy** — when that resource is already unavailable.

Calendary keeps these concepts separate from your database and application
infrastructure.

```text
Application
    ↓
availability + busy periods
    ↓
Calendary
    ↓
resolved days and slots
    ↓
Application / API / UI
```

Calendary does not require an ORM, database, HTTP framework or external
calendar provider.

## Features

* Recurring weekly availability
* Date-specific availability
* Busy periods
* Days off and holidays
* Configurable slot duration
* Configurable slot interval
* Timezone-aware resolution
* Day and slot filtering
* Output field selection
* Array and JSON serialization
* Framework-independent architecture

## Installation

```bash
composer require eril/calendary
```

Calendary requires PHP 8.2 or later.

## Next

Start with [Getting Started](getting-started.md) to create your first
calendar.
