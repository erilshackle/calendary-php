<?php

declare(strict_types=1);

namespace Eril\Calendary\Result;

use DateTimeImmutable;
use Eril\Calendary\Contracts\Result;

/**
 * Represents the resolved calendar days within a date range.
 *
 * Calendar ranges are inclusive: both the start and end dates belong
 * to the requested range.
 *
 * Query filters may cause some dates inside the requested range to be
 * absent from the resulting list of days.
 */
final class Range implements Result
{
    /**
     * Create a resolved calendar range.
     *
     * @param DateTimeImmutable $from Inclusive start date.
     * @param DateTimeImmutable $to Inclusive end date.
     * @param string $timezone Timezone used to resolve the calendar.
     * @param list<Day> $days Resolved days included in the result.
     */
    public function __construct(
        private DateTimeImmutable $from,
        private DateTimeImmutable $to,
        private string $timezone,
        private array $days,
    ) {}

    /**
     * Get the inclusive start date of the requested range.
     *
     * @return DateTimeImmutable
     */
    public function from(): DateTimeImmutable
    {
        return $this->from;
    }

    /**
     * Get the inclusive end date of the requested range.
     *
     * @return DateTimeImmutable
     */
    public function to(): DateTimeImmutable
    {
        return $this->to;
    }

    /**
     * Get the resolved days included in the range.
     *
     * The returned list may contain fewer days than the requested
     * date range when query filters are active.
     *
     * @return list<Day>
     */
    public function days(): array
    {
        return $this->days;
    }

    /**
     * Convert the range to an array.
     *
     * The range metadata is always included. Field selection applies
     * only to the serialized representation of each Day.
     *
     * @return array{
     *     from: string,
     *     to: string,
     *     timezone: string,
     *     days: list<array<string, mixed>>
     * }
     */
    public function toArray(): array
    {
        return [
            'from' => $this->from->format('Y-m-d'),
            'to' => $this->to->format('Y-m-d'),
            'timezone' => $this->timezone,
            'days' => array_map(
                fn (Day $day) => $day->toArray(),
                $this->days
            ),
        ];
    }

    /**
     * Get the data that should be serialized to JSON.
     *
     * @return array{
     *     from: string,
     *     to: string,
     *     timezone: string,
     *     days: list<array<string, mixed>>
     * }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Convert the range to JSON.
     *
     * @param int $flags JSON encoding flags.
     *
     * @return string
     *
     * @throws \JsonException If the range cannot be encoded.
     */
    public function toJson(int $flags = 0): string
    {
        return json_encode(
            $this,
            $flags | JSON_THROW_ON_ERROR
        );
    }
}