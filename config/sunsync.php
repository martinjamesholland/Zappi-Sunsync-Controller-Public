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
        
        // Battery Discharge to Grid Settings
        'battery_size_wh' => '10000',           // Total battery capacity in Wh (e.g., 10kWh)
        'discharge_rate_w' => '2750',           // Discharge rate in Watts (e.g., 2750W)
        'house_load_w' => '350',                // Average house load in Watts (e.g., 350W)
        'discharge_to_soc' => '20',             // Minimum SOC to discharge to (e.g., 20%)
        'discharge_check_time' => '20:00',      // Time to check if discharge should be enabled (e.g., 8pm)
        'discharge_min_soc' => '50',            // Minimum SOC at check time to enable discharge (e.g., 50%)
        'discharge_stop_time' => '23:45',       // Time to stop discharge and return to normal (e.g., 11:45pm)
        'discharge_enabled' => 'false',         // Enable/disable battery discharge to grid feature
    ],

    'api' => [
        'base_url' => 'https://api.sunsynk.net',
        'timeout' => 10,
    ],
];

