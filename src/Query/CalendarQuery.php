<?php

declare(strict_types=1);

namespace Eril\Calendary\Query;

use DateTimeImmutable;
use DateTimeInterface;
use Eril\Calendary\Calendary;
use Eril\Calendary\Resolver;
use Eril\Calendary\Result\Day;
use Eril\Calendary\Result\Projection;
use Eril\Calendary\Result\Range;

final class CalendarQuery
{
    private Resolver $resolver;

    private ?array $fields = null;

    public function __construct(
        private Calendary $calendar
    ) {
        $this->resolver = new Resolver($calendar);
    }

    public function select(string ...$fields): self
    {
        $this->fields = $fields ?: null;

        return $this;
    }

    public function on(string|DateTimeInterface $date): Day
    {
        $day = $this->resolver->day(
            $this->date($date)
        );

        return new Day(
            $day->date(),
            $day->slots(),
            $day->status(),
            $this->projection()
        );
    }

    public function weekOf(string|DateTimeInterface $date): Range
    {
        $date = $this->date($date);

        $from = $date->modify('monday this week');
        $to = $from->modify('+6 days');

        return $this->resolveRange($from, $to);
    }

    public function between(
        string|DateTimeInterface $from,
        string|DateTimeInterface $to
    ): Range {
        return $this->resolveRange(
            $this->date($from),
            $this->date($to)
        );
    }

    private function resolveRange(
        DateTimeImmutable $from,
        DateTimeImmutable $to
    ): Range {
        if ($to < $from) {
            throw new \InvalidArgumentException(
                'Range end must not be before start.'
            );
        }

        $days = [];

        for (
            $date = $from;
            $date <= $to;
            $date = $date->modify('+1 day')
        ) {
            $days[] = $this->resolver->day($date);
        }

        return new Range(
            $from,
            $to,
            $this->calendar->getTimezone()->getName(),
            $days,
            $this->projection()
        );
    }

    private function projection(): ?Projection
    {
        return $this->fields !== null
            ? new Projection($this->fields)
            : null;
    }

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