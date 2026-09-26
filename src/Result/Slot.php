<?php

declare(strict_types=1);

namespace Eril\Calendary\Result;

use DateTimeImmutable;
use Eril\Calendary\Contracts\Result;
use Eril\Calendary\Period;

final class Slot implements Result
{
    public const AVAILABLE = 'available';
    public const BUSY = 'busy';

    public function __construct(
        private Period $period,
        private string $status = self::AVAILABLE,
        private ?Projection $projection = null,
    ) {}

    public function start(): DateTimeImmutable
    {
        return $this->period->start();
    }

    public function end(): DateTimeImmutable
    {
        return $this->period->end();
    }

    public function status(): string
    {
        return $this->status;
    }

    public function available(): bool
    {
        return $this->status === self::AVAILABLE;
    }

    public function toArray(): array
    {
        $data = [
            'start' => $this->start()->format('H:i'),
            'end' => $this->end()->format('H:i'),
            'available' => $this->available(),
            'status' => $this->status(),
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