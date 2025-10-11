<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EvChargingSetting extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ev_charging_settings';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'key',
        'value',
    ];

    /**
     * Get a setting by key
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function getSetting(string $key, mixed $default = null): mixed
    {
        $setting = static::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }

    /**
     * Set a setting value
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public static function setSetting(string $key, mixed $value): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => (string) $value]
        );
    }

    /**
     * Get all settings as an associative array
     *
     * @return array<string, string>
     */
    public static function getAllSettings(): array
    {
        return static::pluck('value', 'key')->toArray();
    }

    /**
     * Update multiple settings at once
     *
     * @param array<string, mixed> $settings
     * @return void
     */
    public static function updateSettings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            static::setSetting($key, $value);
        }
    }
}
