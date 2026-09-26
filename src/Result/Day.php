<?php

declare(strict_types=1);

namespace Eril\Calendary\Result;

use DateTimeImmutable;
use Eril\Calendary\Contracts\Result;

final class Day implements Result
{
    public const OPEN = 'open';
    public const CLOSED = 'closed';
    public const FULL = 'full';
    public const DAY_OFF = 'day_off';
    public const HOLIDAY = 'holiday';

    /**
     * @param Slot[] $slots
     */
    public function __construct(
        private DateTimeImmutable $date,
        private array $slots,
        private string $status,
        private ?Projection $projection = null,
    ) {}

    public function date(): DateTimeImmutable
    {
        return $this->date;
    }

    public function weekday(): int
    {
        return (int) $this->date->format('N');
    }

    public function status(): string
    {
        return $this->status;
    }

    public function slots(): array
    {
        return $this->slots;
    }

    public function availableSlots(): array
    {
        return array_values(
            array_filter(
                $this->slots,
                fn (Slot $slot) => $slot->available()
            )
        );
    }

    public function available(): bool
    {
        return $this->availableSlots() !== [];
    }

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

        return $this->projection?->apply($data) ?? $data;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function toJson(int $flags = 0): string
    {
        return json_encode(
            $this,
            $flags | JSON_THROW_ON_ERROR
        );
    }
}