<?php

declare(strict_types=1);

namespace Tests;

use Eril\Calendary\Calendary;
use InvalidArgumentException;
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

    private function slotTimes(array $slots): array
    {
        return array_map(
            fn($slot) => $slot->start()->format('H:i'),
            $slots
        );
    }

    // -------------------------------------------------------------------------
    // Availability
    // -------------------------------------------------------------------------

    public function test_it_generates_slots_from_weekly_schedule(): void
    {
        $day = $this->calendar()
            ->query()
            ->on('2026-10-05');

        $this->assertSame(
            [
                '09:00',
                '10:00',
                '11:00',
                '14:00',
                '15:00',
                '16:00',
                '17:00',
            ],
            $this->slotTimes($day->slots())
        );

        $this->assertSame('open', $day->status());
    }

    public function test_specific_date_overrides_weekly_schedule(): void
    {
        $calendar = Calendary::load([
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

        $day = $calendar
            ->query()
            ->on('2026-10-08');

        $this->assertSame(
            ['10:00', '11:00', '12:00', '13:00'],
            $this->slotTimes($day->slots())
        );
    }

    public function test_empty_specific_date_closes_the_day(): void
    {
        $calendar = Calendary::load([
            'weekly' => [
                1 => [
                    ['09:00', '18:00'],
                ],
            ],
            'dates' => [
                '2026-10-05' => [],
            ],
        ])
            ->timezone('Atlantic/Cape_Verde')
            ->duration(60);

        $day = $calendar
            ->query()
            ->on('2026-10-05');

        $this->assertSame('closed', $day->status());
        $this->assertSame([], $day->slots());
    }

    public function test_interval_can_be_smaller_than_duration(): void
    {
        $calendar = Calendary::load([
            'dates' => [
                '2026-10-05' => [
                    ['09:00', '12:00'],
                ],
            ],
        ])
            ->timezone('Atlantic/Cape_Verde')
            ->duration(60)
            ->interval(30);

        $day = $calendar
            ->query()
            ->on('2026-10-05');

        $this->assertSame(
            ['09:00', '09:30', '10:00', '10:30', '11:00'],
            $this->slotTimes($day->slots())
        );
    }

    public function test_interval_defaults_to_duration(): void
    {
        $calendar = Calendary::load([
            'dates' => [
                '2026-10-05' => [
                    ['09:00', '12:00'],
                ],
            ],
        ])
            ->timezone('Atlantic/Cape_Verde')
            ->duration(60);

        $day = $calendar
            ->query()
            ->on('2026-10-05');

        $this->assertSame(
            ['09:00', '10:00', '11:00'],
            $this->slotTimes($day->slots())
        );
    }

    // -------------------------------------------------------------------------
    // Busy periods
    // -------------------------------------------------------------------------

    public function test_busy_time_uses_duration(): void
    {
        $day = $this->calendar()
            ->busy([
                ['2026-10-05 10:00'],
            ])
            ->query()
            ->on('2026-10-05');

        $this->assertFalse($day->isAvailable('10:00'));
        $this->assertTrue($day->isAvailable('11:00'));
    }

    public function test_busy_period_blocks_overlapping_slots(): void
    {
        $day = $this->calendar()
            ->busy([
                [
                    '2026-10-05 14:20',
                    '2026-10-05 15:40',
                ],
            ])
            ->query()
            ->on('2026-10-05');

        $this->assertFalse($day->isAvailable('14:00'));
        $this->assertFalse($day->isAvailable('15:00'));
        $this->assertTrue($day->isAvailable('16:00'));
    }

    public function test_break_time_extends_implicit_busy_period(): void
    {
        $calendar = Calendary::load([
            'dates' => [
                '2026-10-05' => [
                    ['09:00', '12:00'],
                ],
            ],
        ])
            ->timezone('Atlantic/Cape_Verde')
            ->duration(60)
            ->interval(15)
            ->breakTime(15)
            ->busy([
                ['2026-10-05 09:00'],
            ]);

        $day = $calendar
            ->query()
            ->on('2026-10-05');

        $this->assertFalse($day->isAvailable('09:00'));
        $this->assertFalse($day->isAvailable('09:15'));
        $this->assertFalse($day->isAvailable('09:30'));
        $this->assertFalse($day->isAvailable('09:45'));
        $this->assertFalse($day->isAvailable('10:00'));

        $this->assertTrue($day->isAvailable('10:15'));
    }

    public function test_break_time_defaults_to_zero(): void
    {
        $calendar = Calendary::load([
            'dates' => [
                '2026-10-05' => [
                    ['09:00', '12:00'],
                ],
            ],
        ])
            ->timezone('Atlantic/Cape_Verde')
            ->duration(60)
            ->interval(15)
            ->busy([
                ['2026-10-05 09:00'],
            ]);

        $day = $calendar
            ->query()
            ->on('2026-10-05');

        $this->assertFalse($day->isAvailable('09:45'));
        $this->assertTrue($day->isAvailable('10:00'));
    }

    public function test_break_time_does_not_extend_explicit_busy_period(): void
    {
        $calendar = Calendary::load([
            'dates' => [
                '2026-10-05' => [
                    ['09:00', '12:00'],
                ],
            ],
        ])
            ->timezone('Atlantic/Cape_Verde')
            ->duration(60)
            ->interval(15)
            ->breakTime(15)
            ->busy([
                [
                    '2026-10-05 09:00',
                    '2026-10-05 10:00',
                ],
            ]);

        $day = $calendar
            ->query()
            ->on('2026-10-05');

        $this->assertFalse($day->isAvailable('09:45'));
        $this->assertTrue($day->isAvailable('10:00'));
    }

    public function test_break_time_cannot_be_negative(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Calendary::load([])
            ->breakTime(-1);
    }

    // -------------------------------------------------------------------------
    // Day states
    // -------------------------------------------------------------------------

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

    public function test_day_without_availability_is_closed(): void
    {
        $day = $this->calendar()
            ->query()
            ->on('2026-10-11');

        $this->assertFalse($day->available());
        $this->assertSame('closed', $day->status());
        $this->assertSame([], $day->slots());
    }

    public function test_day_is_full_when_all_slots_are_busy(): void
    {
        $calendar = Calendary::load([
            'dates' => [
                '2026-10-05' => [
                    ['09:00', '11:00'],
                ],
            ],
        ])
            ->timezone('Atlantic/Cape_Verde')
            ->duration(60)
            ->busy([
                ['2026-10-05 09:00'],
                ['2026-10-05 10:00'],
            ]);

        $day = $calendar
            ->query()
            ->on('2026-10-05');

        $this->assertFalse($day->available());
        $this->assertSame('full', $day->status());
        $this->assertCount(2, $day->slots());
    }

    // -------------------------------------------------------------------------
    // Queries
    // -------------------------------------------------------------------------

    public function test_between_is_inclusive(): void
    {
        $range = $this->calendar()
            ->query()
            ->between(
                '2026-10-05',
                '2026-10-11'
            );

        $this->assertCount(7, $range->days());

        $this->assertSame(
            '2026-10-05',
            $range->days()[0]->date()->format('Y-m-d')
        );

        $this->assertSame(
            '2026-10-11',
            $range->days()[6]->date()->format('Y-m-d')
        );
    }

    public function test_query_can_filter_days_by_status(): void
    {
        $result = $this->calendar()
            ->daysOff(['2026-10-07'])
            ->query()
            ->status('day_off')
            ->between(
                '2026-10-05',
                '2026-10-11'
            );

        $this->assertCount(1, $result->days());
        $this->assertSame(
            '2026-10-07',
            $result->days()[0]->date()->format('Y-m-d')
        );
        $this->assertSame(
            'day_off',
            $result->days()[0]->status()
        );
    }

    public function test_query_can_filter_multiple_statuses(): void
    {
        $result = $this->calendar()
            ->daysOff(['2026-10-07'])
            ->query()
            ->status('day_off', 'closed')
            ->between(
                '2026-10-05',
                '2026-10-11'
            );

        $this->assertCount(3, $result->days());

        $this->assertSame(
            ['day_off', 'closed', 'closed'],
            array_map(
                fn($day) => $day->status(),
                $result->days()
            )
        );
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

        $this->assertCount(5, $result->days());

        foreach ($result->days() as $day) {
            $this->assertTrue($day->available());
        }
    }

    public function test_query_can_return_only_available_slots(): void
    {
        $day = $this->calendar()
            ->busy([
                ['2026-10-05 10:00'],
            ])
            ->query()
            ->availableSlots()
            ->on('2026-10-05');

        $this->assertSame(
            [
                '09:00',
                '11:00',
                '14:00',
                '15:00',
                '16:00',
                '17:00',
            ],
            $this->slotTimes($day->slots())
        );
    }

    // -------------------------------------------------------------------------
    // Projection and serialization
    // -------------------------------------------------------------------------

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

        $this->assertSame('2026-10-05', $result['from']);
        $this->assertSame('2026-10-07', $result['to']);
        $this->assertArrayHasKey('timezone', $result);

        foreach ($result['days'] as $day) {
            $this->assertSame(
                ['date', 'status'],
                array_keys($day)
            );
        }
    }

    public function test_query_can_combine_filters_and_selection(): void
    {
        $result = $this->calendar()
            ->busy([
                ['2026-10-05 10:00'],
            ])
            ->daysOff([
                '2026-10-07',
            ])
            ->query()
            ->status('open')
            ->availableSlots()
            ->select('date', 'slots')
            ->between(
                '2026-10-05',
                '2026-10-11'
            )
            ->toArray();

        $this->assertNotEmpty($result['days']);

        foreach ($result['days'] as $day) {
            $this->assertSame(
                ['date', 'slots'],
                array_keys($day)
            );

            foreach ($day['slots'] as $slot) {
                $this->assertTrue($slot['available']);
            }
        }
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
}