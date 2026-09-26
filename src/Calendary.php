<?php

declare(strict_types=1);

namespace Eril\Calendary;

use DateTimeInterface;
use DateTimeZone;
use Eril\Calendary\Query\CalendarQuery;

final class Calendary
{
    private array $weekly = [];
    private array $dates = [];
    private array $busy = [];
    private array $daysOff = [];
    private array $holidays = [];

    private DateTimeZone $timezone;
    private int $duration = 60;
    private ?int $interval = null;

    public function __construct()
    {
        $this->timezone = new DateTimeZone(date_default_timezone_get());
    }

    public static function load(array $schedule): self
    {
        $calendar = new self();

        $calendar->weekly = $schedule['weekly'] ?? [];
        $calendar->dates = $schedule['dates'] ?? [];

        return $calendar;
    }

    public function timezone(string|DateTimeZone $timezone): self
    {
        $this->timezone = $timezone instanceof DateTimeZone
            ? $timezone
            : new DateTimeZone($timezone);

        return $this;
    }

    public function duration(int $minutes): self
    {
        if ($minutes <= 0) {
            throw new \InvalidArgumentException(
                'Duration must be greater than zero.'
            );
        }

        $this->duration = $minutes;

        return $this;
    }

    public function interval(int $minutes): self
    {
        if ($minutes <= 0) {
            throw new \InvalidArgumentException(
                'Interval must be greater than zero.'
            );
        }

        $this->interval = $minutes;

        return $this;
    }

    public function busy(array $busy): self
    {
        if ($busy === []) {
            return $this;
        }

        // Uma única ocupação.
        if (!is_array(reset($busy))) {
            $busy = [$busy];
        }

        foreach ($busy as $period) {
            $this->busy[] = $period;
        }

        return $this;
    }

    public function daysOff(array $dates): self
    {
        foreach ($dates as $date) {
            $this->daysOff[] = $this->normalizeDateValue($date);
        }

        return $this;
    }

    public function holidays(array $dates): self
    {
        foreach ($dates as $date) {
            $this->holidays[] = $this->normalizeDateValue($date);
        }

        return $this;
    }

    public function query(): CalendarQuery
    {
        return new CalendarQuery($this);
    }

    /*
     * Internal API used by CalendarQuery / Resolver.
     */

    public function weekly(): array
    {
        return $this->weekly;
    }

    public function dates(): array
    {
        return $this->dates;
    }

    public function busyPeriods(): array
    {
        return $this->busy;
    }

    public function daysOffDates(): array
    {
        return $this->daysOff;
    }

    public function holidayDates(): array
    {
        return $this->holidays;
    }

    public function getTimezone(): DateTimeZone
    {
        return $this->timezone;
    }

    public function getDuration(): int
    {
        return $this->duration;
    }

    public function getInterval(): int
    {
        return $this->interval ?? $this->duration;
    }

    private function normalizeDateValue(
        string|DateTimeInterface $date
    ): string {
        if ($date instanceof DateTimeInterface) {
            return $date->format('Y-m-d');
        }

        return $date;
    }
}