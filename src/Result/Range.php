<?php

declare(strict_types=1);

namespace Eril\Calendary\Result;

use DateTimeImmutable;
use Eril\Calendary\Contracts\Result;

final class Range implements Result
{
    /**
     * @param Day[] $days
     */
    public function __construct(
        private DateTimeImmutable $from,
        private DateTimeImmutable $to,
        private string $timezone,
        private array $days,
        private ?Projection $projection = null,
    ) {}

    public function from(): DateTimeImmutable
    {
        return $this->from;
    }

    public function to(): DateTimeImmutable
    {
        return $this->to;
    }

    public function days(): array
    {
        return $this->days;
    }

    public function toArray(): array
    {
        $data = [
            'from' => $this->from->format('Y-m-d'),
            'to' => $this->to->format('Y-m-d'),
            'timezone' => $this->timezone,
            'days' => array_map(
                fn (Day $day) => $day->toArray(),
                $this->days
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