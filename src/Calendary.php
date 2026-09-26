<?php

declare(strict_types=1);

namespace Eril\Calendary;

use DateTimeInterface;
use DateTimeZone;
use Eril\Calendary\Query\CalendarQuery;
use InvalidArgumentException;

/**
 * Defines calendar availability, occupancy and scheduling rules.
 *
 * Calendary receives recurring weekly availability, date-specific
 * availability and unavailable periods, then exposes a query API
 * for resolving days, ranges and slots.
 *
 * Date-specific availability overrides weekly availability for the
 * same date.
 * 
 * @author Erilando erilandocarvalho@gmail.com
 * @version 1.0.0
 * @link https://github.com/erilshackle/calendary-php
 */
final class Calendary
{
    /**
     * Recurring weekly availability indexed by ISO weekday.
     *
     * @var array<int, list<array{0: string, 1: string}>>
     */
    private array $weekly = [];

    /**
     * Date-specific availability indexed by YYYY-MM-DD.
     *
     * @var array<string, list<array{0: string, 1: string}>>
     */
    private array $dates = [];

    /**
     * Busy date or date-time definitions.
     *
     * @var list<array{0: string, 1?: string}>
     */
    private array $busy = [];

    /**
     * Explicit days off.
     *
     * @var list<string>
     */
    private array $daysOff = [];

    /**
     * Explicit holidays.
     *
     * @var list<string>
     */
    private array $holidays = [];

    /**
     * Timezone used for calendar resolution.
     */
    private DateTimeZone $timezone;

    /**
     * Slot duration in minutes.
     */
    private int $duration = 60;

    /**
     * Interval between candidate slot starts, in minutes.
     *
     * When null, the configured duration is used.
     */
    private ?int $interval = null;

    /**
     * Create an empty calendar definition.
     */
    public function __construct()
    {
        $this->timezone = new DateTimeZone(
            date_default_timezone_get()
        );
    }

    /**
     * Create a calendar from an availability definition.
     *
     * Weekly availability is indexed using ISO-8601 weekdays:
     * 1 for Monday through 7 for Sunday.
     *
     * Date-specific availability uses YYYY-MM-DD keys and completely
     * overrides the weekly availability for the corresponding date.
     * An empty date-specific definition explicitly closes that date.
     *
     * Example:
     *
     * [
     *     'weekly' => [
     *         1 => [['09:00', '12:00'], ['14:00', '18:00']],
     *         2 => [['09:00', '18:00']],
     *     ],
     *     'dates' => [
     *         '2026-10-08' => [['10:00', '14:00']],
     *         '2026-10-09' => [],
     *     ],
     * ]
     *
     * @param array{
     *     weekly?: array<int, list<array{0: string, 1: string}>>,
     *     dates?: array<string, list<array{0: string, 1: string}>>
     * } $schedule Availability definition.
     *
     * @return self
     */
    public static function load(array $schedule): self
    {
        $calendar = new self();

        $calendar->weekly = $schedule['weekly'] ?? [];
        $calendar->dates = $schedule['dates'] ?? [];

        return $calendar;
    }

    /**
     * Set the timezone used to resolve calendar dates and times.
     *
     * @param string|DateTimeZone $timezone Valid timezone identifier
     *        or DateTimeZone instance.
     *
     * @return $this
     *
     * @throws \Exception If an invalid timezone identifier is provided.
     */
    public function timezone(string|DateTimeZone $timezone): self
    {
        $this->timezone = $timezone instanceof DateTimeZone
            ? $timezone
            : new DateTimeZone($timezone);

        return $this;
    }

    /**
     * Set the duration of each generated slot.
     *
     * @param int $minutes Duration in minutes. Must be greater than zero.
     *
     * @return $this
     *
     * @throws InvalidArgumentException If the duration is not greater than zero.
     */
    public function duration(int $minutes): self
    {
        if ($minutes <= 0) {
            throw new InvalidArgumentException(
                'Duration must be greater than zero.'
            );
        }

        $this->duration = $minutes;

        return $this;
    }

    /**
     * Set the interval between candidate slot start times.
     *
     * The interval may be shorter than the slot duration, allowing
     * overlapping candidate slots.
     *
     * When no interval is configured, the slot duration is used.
     *
     * @param int $minutes Interval in minutes. Must be greater than zero.
     *
     * @return $this
     *
     * @throws InvalidArgumentException If the interval is not greater than zero.
     */
    public function interval(int $minutes): self
    {
        if ($minutes <= 0) {
            throw new InvalidArgumentException(
                'Interval must be greater than zero.'
            );
        }

        $this->interval = $minutes;

        return $this;
    }

    /**
     * Add busy periods to the calendar.
     *
     * Each entry may contain:
     *
     * - a date (YYYY-MM-DD), blocking the entire day;
     * - a start datetime, using the configured slot duration;
     * - a start and end datetime, defining an explicit busy period.
     *
     * A single busy entry may also be passed directly.
     *
     * Examples:
     *
     * [
     *     ['2026-10-05 10:00'],
     *     ['2026-10-05 14:20', '2026-10-05 15:40'],
     *     ['2026-10-06'],
     * ]
     *
     * @param array<int, array{0: string, 1?: string}>|array{0: string, 1?: string} $busy
     *        Busy date or date-time definitions.
     *
     * @return $this
     */
    public function busy(array $busy): self
    {
        if ($busy !== [] && !is_array($busy[0] ?? null)) {
            $busy = [$busy];
        }

        foreach ($busy as $period) {
            $this->busy[] = $period;
        }

        return $this;
    }

    /**
     * Mark dates as explicit days off.
     *
     * Days off take precedence over weekly and date-specific
     * availability.
     *
     * @param list<string|DateTimeInterface> $dates Dates to mark as days off.
     *
     * @return $this
     */
    public function daysOff(array $dates): self
    {
        foreach ($dates as $date) {
            $this->daysOff[] = $date instanceof DateTimeInterface
                ? $date->format('Y-m-d')
                : $date;
        }

        return $this;
    }

    /**
     * Mark dates as holidays.
     *
     * Holidays take precedence over weekly and date-specific
     * availability.
     *
     * @param list<string|DateTimeInterface> $dates Dates to mark as holidays.
     *
     * @return $this
     */
    public function holidays(array $dates): self
    {
        foreach ($dates as $date) {
            $this->holidays[] = $date instanceof DateTimeInterface
                ? $date->format('Y-m-d')
                : $date;
        }

        return $this;
    }

    /**
     * Start a calendar query.
     *
     * @return CalendarQuery
     */
    public function query(): CalendarQuery
    {
        return new CalendarQuery($this);
    }

    /**
     * Get the recurring weekly availability definition.
     *
     * @return array<int, list<array{0: string, 1: string}>>
     *
     * @internal
     */
    public function weekly(): array
    {
        return $this->weekly;
    }

    /**
     * Get the date-specific availability definition.
     *
     * @return array<string, list<array{0: string, 1: string}>>
     *
     * @internal
     */
    public function dates(): array
    {
        return $this->dates;
    }

    /**
     * Get the configured busy definitions.
     *
     * @return list<array{0: string, 1?: string}>
     *
     * @internal
     */
    public function busyPeriods(): array
    {
        return $this->busy;
    }

    /**
     * Get the configured days off.
     *
     * @return list<string>
     *
     * @internal
     */
    public function daysOffDates(): array
    {
        return $this->daysOff;
    }

    /**
     * Get the configured holidays.
     *
     * @return list<string>
     *
     * @internal
     */
    public function holidayDates(): array
    {
        return $this->holidays;
    }

    /**
     * Get the calendar timezone.
     *
     * @return DateTimeZone
     *
     * @internal
     */
    public function getTimezone(): DateTimeZone
    {
        return $this->timezone;
    }

    /**
     * Get the configured slot duration in minutes.
     *
     * @return int
     *
     * @internal
     */
    public function getDuration(): int
    {
        return $this->duration;
    }

    /**
     * Get the interval between candidate slot starts.
     *
     * Returns the slot duration when no explicit interval has
     * been configured.
     *
     * @return int
     *
     * @internal
     */
    public function getInterval(): int
    {
        return $this->interval ?? $this->duration;
    }
}