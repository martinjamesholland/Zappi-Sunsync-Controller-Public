<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | SunSync API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for SunSync API integration including cache settings
    | and default values.
    |
    */

    'cache' => [
        // Cache TTL for inverter settings in seconds
        'settings_ttl' => env('SUNSYNC_CACHE_TTL', 60),
    ],

    'defaults' => [
        // Default night time range
        'night_start' => '23:30',
        'night_end' => '05:30',
        
        // Default sell time and cap
        'sell_time' => '22:00',
        'cap' => '20',
    ],

    'api' => [
        'base_url' => 'https://api.sunsynk.net',
        'timeout' => 10,
    ],
];

