<?php

declare(strict_types=1);

namespace Eril\Calendary;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Represents an immutable period between two date-time boundaries.
 *
 * Periods use half-open interval semantics: [start, end).
 * The start boundary is included and the end boundary is excluded.
 *
 * This means that two periods where one ends exactly when another
 * begins do not overlap.
 */
final class Period
{
    /**
     * Create a time period.
     *
     * @param DateTimeImmutable $start Inclusive start date and time.
     * @param DateTimeImmutable $end Exclusive end date and time.
     *
     * @throws InvalidArgumentException If the end is not after the start.
     */
    public function __construct(
        private DateTimeImmutable $start,
        private DateTimeImmutable $end,
    ) {
        if ($end <= $start) {
            throw new InvalidArgumentException(
                'Period end must be after start.'
            );
        }
    }

    /**
     * Get the inclusive start of the period.
     *
     * @return DateTimeImmutable
     */
    public function start(): DateTimeImmutable
    {
        return $this->start;
    }

    /**
     * Get the exclusive end of the period.
     *
     * @return DateTimeImmutable
     */
    public function end(): DateTimeImmutable
    {
        return $this->end;
    }

    /**
     * Determine whether this period overlaps another period.
     *
     * Periods use half-open interval semantics. For example,
     * 10:00-11:00 does not overlap 11:00-12:00.
     *
     * @param Period $other Period to compare against.
     *
     * @return bool
     */
    public function overlaps(self $other): bool
    {
        return $this->start < $other->end
            && $this->end > $other->start;
    }
}