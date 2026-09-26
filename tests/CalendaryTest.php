<?php

declare(strict_types=1);

namespace Tests;

use Eril\Calendary\Calendary;
use Eril\Calendary\Contracts\Result;
use PHPUnit\Framework\TestCase;

final class CalendaryTest extends TestCase
{
    private function calendar(): Calendary
    {
        return Calendary::load([
            'weekly' => [
                1 => [
                    ['09:00', '12:00'],
                    ['14:00', '18:00'],
                ],

                2 => [
                    ['09:00', '18:00'],
                ],

                3 => [
                    ['09:00', '18:00'],
                ],

                4 => [
                    ['09:00', '18:00'],
                ],

                5 => [
                    ['09:00', '17:00'],
                ],
            ],
        ])
            ->timezone('Atlantic/Cape_Verde')
            ->duration(60)
            ->interval(60);
    }

    public function test_it_generates_slots_from_weekly_schedule(): void
    {
        $day = $this->calendar()
            ->query()
            ->on('2026-10-05');

        $slots = $day->slots();

        $this->assertCount(7, $slots);

        $this->assertSame(
            '09:00',
            $slots[0]->start()->format('H:i')
        );

        $this->assertSame(
            '10:00',
            $slots[0]->end()->format('H:i')
        );

        $this->assertSame(
            '17:00',
            $slots[6]->start()->format('H:i')
        );
    }

    public function test_specific_date_overrides_weekly_schedule(): void
    {
        $cal = Calendary::load([
            'weekly' => [
                4 => [
                    ['09:00', '18:00'],
                ],
            ],

            'dates' => [
                '2026-10-08' => [
                    ['10:00', '14:00'],
                ],
            ],
        ])
            ->timezone('Atlantic/Cape_Verde')
            ->duration(60);

        $day = $cal
            ->query()
            ->on('2026-10-08');

        $this->assertCount(4, $day->slots());

        $this->assertSame(
            '10:00',
            $day->slots()[0]->start()->format('H:i')
        );

        $this->assertSame(
            '14:00',
            $day->slots()[3]->end()->format('H:i')
        );
    }

    public function test_busy_time_uses_duration(): void
    {
        $cal = $this->calendar()
            ->busy([
                ['2026-10-05 10:00'],
            ]);

        $day = $cal
            ->query()
            ->on('2026-10-05');

        $this->assertFalse(
            $day->isAvailable('10:00')
        );

        $this->assertTrue(
            $day->isAvailable('11:00')
        );
    }

    public function test_busy_period_blocks_overlapping_slots(): void
    {
        $cal = $this->calendar()
            ->busy([
                [
                    '2026-10-05 14:20',
                    '2026-10-05 15:40',
                ],
            ]);

        $day = $cal
            ->query()
            ->on('2026-10-05');

        $this->assertFalse($day->isAvailable('14:00'));
        $this->assertFalse($day->isAvailable('15:00'));
        $this->assertTrue($day->isAvailable('16:00'));
    }

    public function test_day_off_has_no_slots(): void
    {
        $day = $this->calendar()
            ->daysOff(['2026-10-05'])
            ->query()
            ->on('2026-10-05');

        $this->assertFalse($day->available());
        $this->assertSame('day_off', $day->status());
        $this->assertSame([], $day->slots());
    }

    public function test_holiday_has_no_slots(): void
    {
        $day = $this->calendar()
            ->holidays(['2026-10-05'])
            ->query()
            ->on('2026-10-05');

        $this->assertFalse($day->available());
        $this->assertSame('holiday', $day->status());
        $this->assertSame([], $day->slots());
    }

    public function test_closed_day_is_reported_as_closed(): void
    {
        $day = $this->calendar()
            ->query()
            ->on('2026-10-11');

        $this->assertFalse($day->available());
        $this->assertSame('closed', $day->status());
        $this->assertSame([], $day->slots());
    }

    public function test_interval_can_be_smaller_than_duration(): void
    {
        $cal = Calendary::load([
            'dates' => [
                '2026-10-05' => [
                    ['09:00', '12:00'],
                ],
            ],
        ])
            ->timezone('Atlantic/Cape_Verde')
            ->duration(60)
            ->interval(30);

        $day = $cal
            ->query()
            ->on('2026-10-05');

        $this->assertCount(5, $day->slots());

        $this->assertSame(
            ['09:00', '09:30', '10:00', '10:30', '11:00'],
            array_map(
                fn($slot) => $slot->start()->format('H:i'),
                $day->slots()
            )
        );
    }

    public function test_between_returns_every_day_inclusively(): void
    {
        $range = $this->calendar()
            ->query()
            ->between(
                '2026-10-05',
                '2026-10-11'
            );

        $this->assertCount(7, $range->days());
    }

    public function test_result_can_be_serialized_to_json(): void
    {
        $day = $this->calendar()
            ->query()
            ->on('2026-10-05');

        $this->assertSame(
            $day->toArray(),
            json_decode(
                $day->toJson(),
                true,
                flags: JSON_THROW_ON_ERROR
            )
        );
    }

    public function test_query_can_select_day_fields(): void
    {
        $result = $this->calendar()
            ->query()
            ->select('date', 'status')
            ->on('2026-10-05')
            ->toArray();

        $this->assertSame(
            [
                'date' => '2026-10-05',
                'status' => 'open',
            ],
            $result
        );
    }

    public function test_select_is_applied_to_days_inside_range(): void
    {
        $result = $this->calendar()
            ->query()
            ->select('date', 'status')
            ->between(
                '2026-10-05',
                '2026-10-07'
            )
            ->toArray();

        $this->assertSame(
            '2026-10-05',
            $result['from']
        );

        $this->assertSame(
            '2026-10-07',
            $result['to']
        );

        $this->assertArrayHasKey(
            'timezone',
            $result
        );

        $this->assertSame(
            ['date', 'status'],
            array_keys($result['days'][0])
        );
    }

    public function test_query_can_filter_days_by_status(): void
    {
        $cal = $this->calendar()
            ->daysOff([
                '2026-10-07',
            ]);

        $result = $cal
            ->query()
            ->status('open')
            ->between(
                '2026-10-05',
                '2026-10-11'
            );

        foreach ($result->days() as $day) {
            $this->assertSame(
                'open',
                $day->status()
            );
        }
    }

    public function test_query_can_filter_multiple_statuses(): void
    {
        $cal = $this->calendar()
            ->daysOff([
                '2026-10-07',
            ]);

        $result = $cal
            ->query()
            ->status('open', 'day_off')
            ->between(
                '2026-10-05',
                '2026-10-11'
            );

        foreach ($result->days() as $day) {
            $this->assertContains(
                $day->status(),
                ['open', 'day_off']
            );
        }
    }

    public function test_query_can_return_only_available_days(): void
    {
        $result = $this->calendar()
            ->query()
            ->available()
            ->between(
                '2026-10-05',
                '2026-10-11'
            );

        foreach ($result->days() as $day) {
            $this->assertTrue(
                $day->available()
            );
        }
    }

    public function test_query_can_return_only_available_slots(): void
    {
        $cal = $this->calendar()
            ->busy([
                ['2026-10-05 10:00'],
            ]);

        $day = $cal
            ->query()
            ->availableSlots()
            ->on('2026-10-05');

        foreach ($day->slots() as $slot) {
            $this->assertTrue(
                $slot->available()
            );
        }

        $times = array_map(
            fn($slot) => $slot->start()->format('H:i'),
            $day->slots()
        );

        $this->assertNotContains(
            '10:00',
            $times
        );
    }

    public function test_query_can_combine_filters_and_selection(): void
    {
        $cal = $this->calendar()
            ->busy([
                ['2026-10-05 10:00'],
            ])
            ->daysOff([
                '2026-10-07',
            ]);

        $result = $cal
            ->query()
            ->status('open')
            ->availableSlots()
            ->select('date', 'slots')
            ->between(
                '2026-10-05',
                '2026-10-11'
            )
            ->toArray();

        foreach ($result['days'] as $day) {
            $this->assertSame(
                ['date', 'slots'],
                array_keys($day)
            );

            foreach ($day['slots'] as $slot) {
                $this->assertTrue(
                    $slot['available']
                );
            }
        }
    }
}
