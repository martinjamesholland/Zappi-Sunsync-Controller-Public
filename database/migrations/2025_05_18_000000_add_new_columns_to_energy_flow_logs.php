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
            // Add new columns
            $table->float('grid_power_sunsync')->nullable()->after('grid_power');
            
            // Rename car node connection fields for consistency with the data array
            $table->renameColumn('car_node_pst', 'car_node_connection');
            $table->renameColumn('car_node_zmo', 'car_node_Mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('energy_flow_logs', function (Blueprint $table) {
            // Remove new columns
            $table->dropColumn('grid_power_sunsync');
            
            // Revert name changes
            $table->renameColumn('car_node_connection', 'car_node_pst');
            $table->renameColumn('car_node_Mode', 'car_node_zmo');
        });
    }
}; 