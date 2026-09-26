<?php

declare(strict_types=1);

namespace Eril\Calendary;

use DateTimeImmutable;

final class Period
{
    public function __construct(
        private DateTimeImmutable $start,
        private DateTimeImmutable $end,
    ) {
        if ($end <= $start) {
            throw new \InvalidArgumentException(
                'Period end must be after start.'
            );
        }
    }

    public function start(): DateTimeImmutable
    {
        return $this->start;
    }

    public function end(): DateTimeImmutable
    {
        return $this->end;
    }

    public function overlaps(self $other): bool
    {
        return $this->start < $other->end
            && $this->end > $other->start;
    }
}