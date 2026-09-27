<?php

declare(strict_types=1);

namespace Eril\Calendary;

use DateInterval;
use DateTimeImmutable;
use Eril\Calendary\Result\Day;
use Eril\Calendary\Result\Slot;

/**
 * Resolves calendar definitions into concrete days and slots.
 *
 * Resolution precedence:
 *
 * 1. holidays and days off close the date;
 * 2. date-specific availability overrides weekly availability;
 * 3. weekly availability is used as fallback;
 * 4. candidate slots are generated;
 * 5. busy periods are applied to generated slots.
 *
 * @internal
 */
final class Resolver
{
    /**
     * Create a resolver for a calendar.
     *
     * @param Calendary $calendar Calendar definition to resolve.
     */
    public function __construct(
        private Calendary $calendar
    ) {}

    /**
     * Resolve a calendar date into a Day.
     *
     * @param DateTimeImmutable $date Date to resolve.
     *
     * @return Day
     */
    public function day(DateTimeImmutable $date): Day
    {
        $key = $date->format('Y-m-d');

        if (in_array(
            $key,
            $this->calendar->holidayDates(),
            true
        )) {
            return new Day(
                $date,
                [],
                Day::HOLIDAY
            );
        }

        if (in_array(
            $key,
            $this->calendar->daysOffDates(),
            true
        )) {
            return new Day(
                $date,
                [],
                Day::DAY_OFF
            );
        }

        $availability = $this->availabilityFor($date);

        if ($availability === []) {
            return new Day(
                $date,
                [],
                Day::CLOSED
            );
        }

        $busy = $this->busyFor($date);
        $slots = [];

        foreach ($availability as $period) {
            foreach (
                $this->generateSlots(
                    $date,
                    $period[0],
                    $period[1]
                ) as $slot
            ) {
                $isBusy = false;

                foreach ($busy as $busyPeriod) {
                    if ($slot->overlaps($busyPeriod)) {
                        $isBusy = true;
                        break;
                    }
                }

                $slots[] = new Slot(
                    $slot,
                    $isBusy
                        ? Slot::BUSY
                        : Slot::AVAILABLE
                );
            }
        }

        $available = array_filter(
            $slots,
            fn(Slot $slot) => $slot->available()
        );

        return new Day(
            $date,
            $slots,
            $available === []
                ? Day::FULL
                : Day::OPEN
        );
    }

    /**
     * Get the availability periods applicable to a date.
     *
     * Date-specific availability takes precedence over recurring
     * weekly availability, including explicitly empty definitions.
     *
     * @param DateTimeImmutable $date Date being resolved.
     *
     * @return list<array{0: string, 1: string}>
     */
    private function availabilityFor(
        DateTimeImmutable $date
    ): array {
        $dates = $this->calendar->dates();
        $key = $date->format('Y-m-d');

        if (array_key_exists($key, $dates)) {
            return $dates[$key];
        }

        $weekday = (int) $date->format('N');

        return $this->calendar->weekly()[$weekday] ?? [];
    }

    /**
     * Generate candidate slots within an availability period.
     *
     * A slot is generated only when its complete duration fits inside
     * the availability period.
     *
     * @param DateTimeImmutable $date Date being resolved.
     * @param string $start Availability start time in HH:MM format.
     * @param string $end Availability end time in HH:MM format.
     *
     * @return list<Period>
     */
    private function generateSlots(
        DateTimeImmutable $date,
        string $start,
        string $end
    ): array {
        $timezone = $this->calendar->getTimezone();

        $cursor = new DateTimeImmutable(
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

        while ($cursor < $until) {
            $slotEnd = $cursor->add($duration);

            if ($slotEnd > $until) {
                break;
            }

            $slots[] = new Period(
                $cursor,
                $slotEnd
            );

            $cursor = $cursor->add($interval);
        }

        return $slots;
    }

    /**
     * Get busy periods that may affect the given date.
     *
     * @param DateTimeImmutable $date Date being resolved.
     *
     * @return list<Period>
     */
    private function busyFor(
        DateTimeImmutable $date
    ): array {
        $periods = [];

        $day = new Period(
            $date->setTime(0, 0),
            $date->setTime(0, 0)->modify('+1 day')
        );

        foreach ($this->calendar->busyPeriods() as $busy) {
            $period = $this->normalizeBusy($busy);

            if ($period->overlaps($day)) {
                $periods[] = $period;
            }
        }

        return $periods;
    }

    /**
     * Normalize a busy definition into a concrete Period.
     *
     * Supported definitions:
     *
     * - [date] blocks the complete date;
     * - [datetime] uses the configured slot duration and break time;
     * - [start, end] defines an explicit period.
     *
     * Break time is only applied when the end of the busy period is inferred
     * from the configured slot duration.
     *
     * @param array{0: string, 1?: string} $busy Busy definition.
     *
     * @return Period
     */
    private function normalizeBusy(array $busy): Period
    {
        $timezone = $this->calendar->getTimezone();

        $start = new DateTimeImmutable(
            $busy[0],
            $timezone
        );

        // Explicit period: [start, end]
        if (isset($busy[1])) {
            return new Period(
                $start,
                new DateTimeImmutable(
                    $busy[1],
                    $timezone
                )
            );
        }

        // Whole day: [date]
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $busy[0])) {
            return new Period(
                $start->setTime(0, 0),
                $start->setTime(0, 0)->modify('+1 day')
            );
        }

        // Implicit booking: [datetime]
        $minutes = $this->calendar->getDuration()
            + $this->calendar->getBreakTime();

        return new Period(
            $start,
            $start->add(
                new DateInterval('PT' . $minutes . 'M')
            )
        );
    }
}
