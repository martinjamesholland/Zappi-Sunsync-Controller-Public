<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CostSetting extends Model
{
    use HasFactory;
    
    protected $fillable = ['key', 'value', 'description'];
    
    protected $casts = [
        'value' => 'decimal:4',
    ];
    
    /**
     * Get a setting value by key
     */
    public static function getValue(string $key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        return $setting ? $setting->value : $default;
    }
    
    /**
     * Set a setting value by key
     */
    public static function setValue(string $key, $value, string $description = null)
    {
        $setting = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'description' => $description,
                'updated_at' => now()
            ]
        );
        return $setting;
    }
    
    /**
     * Get all settings as array
     */
    public static function getAllSettings(): array
    {
        return self::pluck('value', 'key')->toArray();
    }
}
