<?php

declare(strict_types=1);

namespace Eril\Calendary\Query;

use DateTimeImmutable;
use DateTimeInterface;
use Eril\Calendary\Calendary;
use Eril\Calendary\Resolver;
use Eril\Calendary\Result\Day;
use Eril\Calendary\Result\Range;
use InvalidArgumentException;

/**
 * Builds and executes queries against a Calendary instance.
 *
 * Query configuration may control which days are returned, which
 * slots are included and which Day fields are serialized.
 *
 * Query filters do not modify the underlying calendar definition.
 */
final class CalendarQuery
{
    private Resolver $resolver;

    /**
     * Day fields included during serialization.
     *
     * @var list<'date'|'weekday'|'available'|'status'|'slots'>|null
     */
    private ?array $fields = null;

    /**
     * Accepted Day statuses.
     *
     * @var list<'open'|'closed'|'full'|'day_off'|'holiday'>
     */
    private array $statuses = [];

    /**
     * Whether only days containing available slots should be returned.
     */
    private bool $onlyAvailableDays = false;

    /**
     * Whether busy slots should be removed from returned days.
     */
    private bool $onlyAvailableSlots = false;

    /**
     * Create a query for a calendar.
     *
     * @param Calendary $calendar Calendar to query.
     */
    public function __construct(
        private Calendary $calendar
    ) {
        $this->resolver = new Resolver($calendar);
    }

    /**
     * Select which Day fields should be included during serialization.
     *
     * Selection affects only the serialized representation. The
     * resulting Day objects retain their complete resolved data.
     *
     * When used with a Range, the selection is applied to each Day.
     * Range metadata remains unchanged.
     *
     * @param 'date'|'weekday'|'available'|'status'|'slots' ...$fields
     *        Day fields to include.
     *
     * @return $this
     */
    public function select(string ...$fields): self
    {
        $this->fields = $fields ?: null;

        return $this;
    }

    /**
     * Filter days by one or more resolved statuses.
     *
     * @param 'open'|'closed'|'full'|'day_off'|'holiday' ...$statuses
     *        Accepted day statuses.
     *
     * @return $this
     */
    public function status(string ...$statuses): self
    {
        $this->statuses = $statuses;

        return $this;
    }

    /**
     * Return only days that contain at least one available slot.
     *
     * @return $this
     */
    public function available(): self
    {
        $this->onlyAvailableDays = true;

        return $this;
    }

    /**
     * Include only available slots in returned Day objects.
     *
     * This does not change the resolved status of the Day. It only
     * removes busy slots from the resulting slot collection.
     *
     * @return $this
     */
    public function availableSlots(): self
    {
        $this->onlyAvailableSlots = true;

        return $this;
    }

    /**
     * Resolve a single calendar date.
     *
     * @param string|DateTimeInterface $date Date to resolve.
     *
     * @return Day
     */
    public function on(string|DateTimeInterface $date): Day
    {
        return $this->prepareDay(
            $this->resolver->day(
                $this->date($date)
            )
        );
    }

    /**
     * Resolve the ISO week containing the given date.
     *
     * The resulting range starts on Monday and ends on Sunday.
     *
     * @param string|DateTimeInterface $date Date belonging to the desired week.
     *
     * @return Range
     */
    public function weekOf(string|DateTimeInterface $date): Range
    {
        $date = $this->date($date);

        $from = $date->modify('monday this week');
        $to = $from->modify('+6 days');

        return $this->resolveRange($from, $to);
    }

    /**
     * Resolve an inclusive calendar date range.
     *
     * Both the start and end dates belong to the requested range.
     *
     * @param string|DateTimeInterface $from Inclusive start date.
     * @param string|DateTimeInterface $to Inclusive end date.
     *
     * @return Range
     *
     * @throws InvalidArgumentException If the end date is before the start date.
     */
    public function between(
        string|DateTimeInterface $from,
        string|DateTimeInterface $to
    ): Range {
        return $this->resolveRange(
            $this->date($from),
            $this->date($to)
        );
    }

    /**
     * Resolve an inclusive range and apply active Day filters.
     *
     * @param DateTimeImmutable $from Inclusive start date.
     * @param DateTimeImmutable $to Inclusive end date.
     *
     * @return Range
     *
     * @throws InvalidArgumentException If the end date is before the start date.
     */
    private function resolveRange(
        DateTimeImmutable $from,
        DateTimeImmutable $to
    ): Range {
        if ($to < $from) {
            throw new InvalidArgumentException(
                'Range end must not be before start.'
            );
        }

        $days = [];

        for (
            $date = $from;
            $date <= $to;
            $date = $date->modify('+1 day')
        ) {
            $day = $this->resolver->day($date);

            if (!$this->matchesDay($day)) {
                continue;
            }

            $days[] = $this->prepareDay($day);
        }

        return new Range(
            $from,
            $to,
            $this->calendar->getTimezone()->getName(),
            $days
        );
    }

    /**
     * Determine whether a resolved Day matches the active filters.
     *
     * @param Day $day Resolved day.
     *
     * @return bool
     */
    private function matchesDay(Day $day): bool
    {
        if (
            $this->statuses !== []
            && !in_array($day->status(), $this->statuses, true)
        ) {
            return false;
        }

        if (
            $this->onlyAvailableDays
            && !$day->available()
        ) {
            return false;
        }

        return true;
    }

    /**
     * Prepare a Day for the query result.
     *
     * Applies slot filtering and serialization field selection without
     * changing the original resolved status of the Day.
     *
     * @param Day $day Resolved day.
     *
     * @return Day
     */
    private function prepareDay(Day $day): Day
    {
        $slots = $this->onlyAvailableSlots
            ? $day->availableSlots()
            : $day->slots();

        return new Day(
            $day->date(),
            $slots,
            $day->status(),
            $this->fields
        );
    }

    /**
     * Normalize a date into the calendar timezone.
     *
     * Only the calendar date is preserved when a DateTimeInterface
     * instance is provided.
     *
     * @param string|DateTimeInterface $date Date to normalize.
     *
     * @return DateTimeImmutable
     */
    private function date(
        string|DateTimeInterface $date
    ): DateTimeImmutable {
        if ($date instanceof DateTimeInterface) {
            return new DateTimeImmutable(
                $date->format('Y-m-d'),
                $this->calendar->getTimezone()
            );
        }

        return new DateTimeImmutable(
            $date,
            $this->calendar->getTimezone()
        );
    }
}