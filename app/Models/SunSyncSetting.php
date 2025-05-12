<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class SunSyncSetting extends Model
{
    protected $fillable = [
        'inverter_sn',
        'settings',
        'last_updated'
    ];

    protected $casts = [
        'settings' => 'array',
        'last_updated' => 'datetime'
    ];

    public static function getCachedSettings(string $inverterSn): ?array
    {
        $settings = self::where('inverter_sn', $inverterSn)
            ->where('last_updated', '>=', Carbon::now()->subMinutes(1))
            ->latest()
            ->first();

        return $settings ? $settings->settings : null;
    }

    public static function updateSettings(string $inverterSn, array $settings): void
    {
        self::updateOrCreate(
            ['inverter_sn' => $inverterSn],
            [
                'settings' => $settings,
                'last_updated' => Carbon::now()
            ]
        );
    }
} 