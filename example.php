<?php

require __DIR__ . '/vendor/autoload.php';

use Eril\Calendary\Calendary;

$cal = Calendary::load([
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

    'dates' => [
        '2026-10-08' => [
            ['10:00', '14:00'],
        ],
    ],
]);

$cal->timezone('Atlantic/Cape_Verde')
    ->duration(60)
    ->interval(60)
    ->daysOff([
        '2026-10-07',
    ])
    ->holidays([
        '2026-10-12',
    ])
    ->busy([
        ['2026-10-05 10:00'],
        ['2026-10-05 14:20', '2026-10-05 15:40'],
        ['2026-10-06 11:00'],
    ]);

$result = $cal->query()
    ->available()
    // ->select('slots','status')
    ->between(
        '2026-10-05',
        '2026-10-12'
    );


echo '<pre>';

echo $result->toJson(
    JSON_PRETTY_PRINT|
    JSON_UNESCAPED_SLASHES
);

echo '</pre>';
