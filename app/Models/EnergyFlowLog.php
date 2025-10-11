<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EnergyFlowLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'pv1_power',
        'pv2_power',
        'total_pv_power',
        'grid_power',
        'grid_power_sunsync',
        'battery_power',
        'battery_soc',
        'ups_load_power',
        'smart_load_power',
        'home_load_power',
        'total_load_power',
        'sunsync_updated_at',
        'zappi_updated_at',
        'home_load_sunsync',
        'combined_load_node_sunsync',
        'combined_load_node',
        'zappi_node',
        'car_node_connection',
        'car_node_Mode',
        'car_node_sta',
        'last_consumption'
    ];

    protected $casts = [
        'sunsync_updated_at' => 'datetime',
        'zappi_updated_at' => 'datetime'
    ];
}
