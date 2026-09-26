<?php

declare(strict_types=1);

namespace Eril\Calendary\Result;

use DateTimeImmutable;
use Eril\Calendary\Contracts\Result;

/**
 * Represents the resolved availability of a calendar day.
 *
 * A day contains its resolved status and the slots generated from
 * the applicable availability rules.
 *
 * The serialized representation may be limited by fields selected
 * through the calendar query.
 */
final class Day implements Result
{
    /**
     * The day has at least one available slot.
     */
    public const OPEN = 'open';

    /**
     * No availability schedule exists for the day.
     */
    public const CLOSED = 'closed';

    /**
     * The day has availability configured, but every slot is busy.
     */
    public const FULL = 'full';

    /**
     * The day is explicitly marked as a day off.
     */
    public const DAY_OFF = 'day_off';

    /**
     * The day is explicitly marked as a holiday.
     */
    public const HOLIDAY = 'holiday';

    /**
     * Create a resolved calendar day.
     *
     * Selected fields affect only serialization. They do not remove
     * information from the Day object itself.
     *
     * @param DateTimeImmutable $date The represented calendar date.
     * @param list<Slot> $slots The resolved slots for the day.
     * @param 'open'|'closed'|'full'|'day_off'|'holiday' $status The resolved day status.
     * @param list<'date'|'weekday'|'available'|'status'|'slots'>|null $fields
     *        Fields included when serializing the day, or null for all fields.
     */
    public function __construct(
        private DateTimeImmutable $date,
        private array $slots,
        private string $status,
        private ?array $fields = null,
    ) {}

    /**
     * Get the represented calendar date.
     *
     * @return DateTimeImmutable
     */
    public function date(): DateTimeImmutable
    {
        return $this->date;
    }

    /**
     * Get the ISO-8601 numeric weekday.
     *
     * Values range from 1 (Monday) to 7 (Sunday).
     *
     * @return int<1, 7>
     */
    public function weekday(): int
    {
        return (int) $this->date->format('N');
    }

    /**
     * Get the resolved status of the day.
     *
     * @return 'open'|'closed'|'full'|'day_off'|'holiday'
     */
    public function status(): string
    {
        return $this->status;
    }

    /**
     * Get all resolved slots for the day.
     *
     * @return list<Slot>
     */
    public function slots(): array
    {
        return $this->slots;
    }

    /**
     * Get only the available slots for the day.
     *
     * @return list<Slot>
     */
    public function availableSlots(): array
    {
        return array_values(
            array_filter(
                $this->slots,
                fn (Slot $slot) => $slot->available()
            )
        );
    }

    /**
     * Determine whether the day has at least one available slot.
     *
     * @return bool
     */
    public function available(): bool
    {
        return $this->availableSlots() !== [];
    }

    /**
     * Determine whether a specific slot start time is available.
     *
     * The time is matched against the start time of the resolved slots
     * using the 24-hour HH:MM format.
     *
     * @param string $time Time in HH:MM format, for example "09:30".
     *
     * @return bool
     */
    public function isAvailable(string $time): bool
    {
        foreach ($this->slots as $slot) {
            if (
                $slot->start()->format('H:i') === $time
                && $slot->available()
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Convert the day to an array.
     *
     * When fields were selected through the query, only those fields
     * are included in the returned representation.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = [
            'date' => $this->date->format('Y-m-d'),
            'weekday' => $this->weekday(),
            'available' => $this->available(),
            'status' => $this->status(),
            'slots' => array_map(
                fn (Slot $slot) => $slot->toArray(),
                $this->slots
            ),
        ];

        if ($this->fields === null) {
            return $data;
        }

        return array_intersect_key(
            $data,
            array_flip($this->fields)
        );
    }

    /**
     * Get the data that should be serialized to JSON.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Convert the day to JSON.
     *
     * @param int $flags JSON encoding flags.
     *
     * @return string
     *
     * @throws \JsonException If the day cannot be encoded.
     */
    public function toJson(int $flags = 0): string
    {
        return json_encode(
            $this,
            $flags | JSON_THROW_ON_ERROR
        );
    }
}