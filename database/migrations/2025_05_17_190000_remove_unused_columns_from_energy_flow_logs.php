<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('energy_flow_logs', function (Blueprint $table) {
            $table->dropColumn([
                'pv_to_inverter',
                'to_grid',
                'to_battery',
                'battery_to_inverter',
                'grid_to_inverter'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('energy_flow_logs', function (Blueprint $table) {
            $table->boolean('pv_to_inverter')->default(false);
            $table->boolean('to_grid')->default(false);
            $table->boolean('to_battery')->default(false);
            $table->boolean('battery_to_inverter')->default(false);
            $table->boolean('grid_to_inverter')->default(false);
        });
    }
}; 