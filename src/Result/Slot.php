<?php

declare(strict_types=1);

namespace Eril\Calendary\Result;

use DateTimeImmutable;
use Eril\Calendary\Contracts\Result;
use Eril\Calendary\Period;

/**
 * Represents a resolved time slot within a calendar day.
 *
 * A slot has a fixed time period and indicates whether that period
 * is currently available or occupied.
 */
final class Slot implements Result
{
    /**
     * The slot is available for use.
     */
    public const AVAILABLE = 'available';

    /**
     * The slot overlaps a busy period.
     */
    public const BUSY = 'busy';

    /**
     * Create a resolved calendar slot.
     *
     * @param Period $period The time period represented by the slot.
     * @param 'available'|'busy' $status The resolved slot status.
     */
    public function __construct(
        private Period $period,
        private string $status,
    ) {}

    /**
     * Get the slot start date and time.
     *
     * @return DateTimeImmutable
     */
    public function start(): DateTimeImmutable
    {
        return $this->period->start();
    }

    /**
     * Get the slot end date and time.
     *
     * @return DateTimeImmutable
     */
    public function end(): DateTimeImmutable
    {
        return $this->period->end();
    }

    /**
     * Get the resolved slot status.
     *
     * @return 'available'|'busy'
     */
    public function status(): string
    {
        return $this->status;
    }

    /**
     * Determine whether the slot is available.
     *
     * @return bool
     */
    public function available(): bool
    {
        return $this->status === self::AVAILABLE;
    }

    /**
     * Convert the slot to an array.
     *
     * @return array{
     *     start: string,
     *     end: string,
     *     available: bool,
     *     status: 'available'|'busy'
     * }
     */
    public function toArray(): array
    {
        return [
            'start' => $this->start()->format('H:i'),
            'end' => $this->end()->format('H:i'),
            'available' => $this->available(),
            'status' => $this->status(),
        ];
    }

    /**
     * Get the data that should be serialized to JSON.
     *
     * @return array{
     *     start: string,
     *     end: string,
     *     available: bool,
     *     status: 'available'|'busy'
     * }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Convert the slot to JSON.
     *
     * @param int $flags JSON encoding flags.
     *
     * @return string
     *
     * @throws \JsonException If the slot cannot be encoded.
     */
    public function toJson(int $flags = 0): string
    {
        return json_encode(
            $this,
            $flags | JSON_THROW_ON_ERROR
        );
    }
}