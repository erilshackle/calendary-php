<?php

declare(strict_types=1);

namespace Eril\Calendary;

use DateInterval;
use DateTimeImmutable;
use Eril\Calendary\Result\Day;
use Eril\Calendary\Result\Slot;

final class Resolver
{
    public function __construct(
        private Calendary $calendar
    ) {}

    public function day(DateTimeImmutable $date): Day
    {
        $key = $date->format('Y-m-d');

        if (in_array($key, $this->calendar->holidayDates(), true)) {
            return new Day(
                $date,
                [],
                Day::HOLIDAY
            );
        }

        if (in_array($key, $this->calendar->daysOffDates(), true)) {
            return new Day(
                $date,
                [],
                Day::DAY_OFF
            );
        }

        $periods = $this->availabilityFor($date);

        if ($periods === []) {
            return new Day(
                $date,
                [],
                Day::CLOSED
            );
        }

        $busy = $this->busyFor($date);
        $slots = [];

        foreach ($periods as [$start, $end]) {
            foreach ($this->generateSlots($date, $start, $end) as $period) {
                $status = Slot::AVAILABLE;

                foreach ($busy as $busyPeriod) {
                    if ($period->overlaps($busyPeriod)) {
                        $status = Slot::BUSY;
                        break;
                    }
                }

                $slots[] = new Slot($period, $status);
            }
        }

        $available = array_filter(
            $slots,
            fn (Slot $slot) => $slot->available()
        );

        return new Day(
            $date,
            $slots,
            $available === []
                ? Day::FULL
                : Day::OPEN
        );
    }

    private function availabilityFor(DateTimeImmutable $date): array
    {
        $key = $date->format('Y-m-d');

        $dates = $this->calendar->dates();

        if (array_key_exists($key, $dates)) {
            return $dates[$key];
        }

        $weekday = (int) $date->format('N');

        return $this->calendar->weekly()[$weekday] ?? [];
    }

    /**
     * @return Period[]
     */
    private function generateSlots(
        DateTimeImmutable $date,
        string $start,
        string $end
    ): array {
        $timezone = $this->calendar->getTimezone();

        $from = new DateTimeImmutable(
            $date->format('Y-m-d') . ' ' . $start,
            $timezone
        );

        $until = new DateTimeImmutable(
            $date->format('Y-m-d') . ' ' . $end,
            $timezone
        );

        $duration = new DateInterval(
            'PT' . $this->calendar->getDuration() . 'M'
        );

        $interval = new DateInterval(
            'PT' . $this->calendar->getInterval() . 'M'
        );

        $slots = [];

        for ($cursor = $from; $cursor < $until; $cursor = $cursor->add($interval)) {
            $slotEnd = $cursor->add($duration);

            if ($slotEnd > $until) {
                break;
            }

            $slots[] = new Period(
                $cursor,
                $slotEnd
            );
        }

        return $slots;
    }

    /**
     * @return Period[]
     */
    private function busyFor(DateTimeImmutable $date): array
    {
        $periods = [];

        foreach ($this->calendar->busyPeriods() as $busy) {
            $period = $this->normalizeBusy($busy);

            if (
                $period->start()->format('Y-m-d') === $date->format('Y-m-d')
                || $period->end()->format('Y-m-d') === $date->format('Y-m-d')
            ) {
                $periods[] = $period;
            }
        }

        return $periods;
    }

    private function normalizeBusy(array $busy): Period
    {
        if ($busy === []) {
            throw new \InvalidArgumentException(
                'Busy period cannot be empty.'
            );
        }

        $timezone = $this->calendar->getTimezone();

        $start = (string) $busy[0];

        /*
         * Date only:
         *
         * ['2026-10-07']
         */
        if (
            count($busy) === 1
            && preg_match('/^\d{4}-\d{2}-\d{2}$/', $start)
        ) {
            $from = new DateTimeImmutable(
                $start . ' 00:00:00',
                $timezone
            );

            return new Period(
                $from,
                $from->modify('+1 day')
            );
        }

        $from = new DateTimeImmutable(
            $start,
            $timezone
        );

        /*
         * Start only:
         *
         * ['2026-10-05 10:00']
         */
        if (count($busy) === 1) {
            return new Period(
                $from,
                $from->modify(
                    '+' . $this->calendar->getDuration() . ' minutes'
                )
            );
        }

        /*
         * Explicit interval:
         *
         * ['2026-10-05 10:00', '2026-10-05 11:30']
         */
        $end = new DateTimeImmutable(
            (string) $busy[1],
            $timezone
        );

        return new Period(
            $from,
            $end
        );
    }
}