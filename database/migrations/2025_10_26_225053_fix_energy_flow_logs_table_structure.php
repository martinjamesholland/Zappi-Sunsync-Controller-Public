<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop the table if it exists and recreate with correct structure
        Schema::dropIfExists('energy_flow_logs');
        
        Schema::create('energy_flow_logs', function (Blueprint $table) {
            $table->id();
            
            // PV Power columns
            $table->decimal('pv1_power', 10, 2)->nullable();
            $table->decimal('pv2_power', 10, 2)->nullable();
            $table->decimal('total_pv_power', 10, 2)->nullable();
            
            // Grid Power columns
            $table->decimal('grid_power', 10, 2)->nullable();
            $table->decimal('grid_power_sunsync', 10, 2)->nullable();
            
            // Battery columns
            $table->decimal('battery_power', 10, 2)->nullable();
            $table->decimal('battery_soc', 5, 2)->nullable();
            
            // Load columns - NOT NULL with defaults
            $table->decimal('ups_load_power', 10, 2)->default(0)->nullable(false);
            $table->decimal('smart_load_power', 10, 2)->default(0)->nullable(false);
            $table->decimal('home_load_power', 10, 2)->default(0)->nullable(false);
            
            // Additional load calculations
            $table->decimal('home_load_sunsync', 10, 2)->default(0)->nullable(false);
            $table->decimal('combined_load_node_sunsync', 10, 2)->default(0)->nullable(false);
            $table->decimal('combined_load_node', 10, 2)->default(0)->nullable(false);
            $table->decimal('zappi_node', 10, 2)->default(0)->nullable(false);
            
            // Zappi/Car node columns
            $table->string('car_node_connection', 50)->nullable();
            $table->string('car_node_Mode', 20)->nullable();
            $table->tinyInteger('car_node_sta')->nullable();
            
            // Last consumption
            $table->decimal('last_consumption', 10, 2)->default(0)->nullable(false);
            
            // Timestamps
            $table->timestamp('sunsync_updated_at')->nullable();
            $table->timestamp('zappi_updated_at')->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['created_at', 'updated_at'], 'idx_timestamps');
            $table->index('battery_soc', 'idx_battery_soc');
        });
        
        // Set table engine to InnoDB with ROW_FORMAT=COMPRESSED
        DB::statement('ALTER TABLE `energy_flow_logs` ENGINE=InnoDB ROW_FORMAT=COMPRESSED');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate the old structure (simpler version)
        Schema::dropIfExists('energy_flow_logs');
        
        Schema::create('energy_flow_logs', function (Blueprint $table) {
            $table->id();
            $table->float('pv1_power')->nullable();
            $table->float('pv2_power')->nullable();
            $table->float('total_pv_power')->nullable();
            $table->float('grid_power')->nullable();
            $table->float('grid_power_sunsync')->nullable();
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
};