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
        Schema::create('energy_flow_logs', function (Blueprint $table) {
            $table->id();
            $table->float('pv1_power')->nullable();
            $table->float('pv2_power')->nullable();
            $table->float('total_pv_power')->nullable();
            $table->float('grid_power')->nullable();
            $table->float('battery_power')->nullable();
            $table->float('battery_soc')->nullable();
            $table->float('ups_load_power')->nullable();
            $table->float('smart_load_power')->nullable();
            $table->float('home_load_power')->nullable();
            $table->float('total_load_power')->nullable();
            $table->boolean('grid_connected')->default(true);
            $table->boolean('pv_to_inverter')->default(false);
            $table->boolean('to_load')->default(false);
            $table->boolean('to_smart_load')->default(false);
            $table->boolean('to_ups_load')->default(false);
            $table->boolean('to_home_load')->default(false);
            $table->boolean('to_grid')->default(false);
            $table->boolean('to_battery')->default(false);
            $table->boolean('battery_to_inverter')->default(false);
            $table->boolean('grid_to_inverter')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('energy_flow_logs');
    }
};
