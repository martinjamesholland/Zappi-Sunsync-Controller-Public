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
            // Add new columns if they don't exist
            if (!Schema::hasColumn('energy_flow_logs', 'grid_power_sunsync')) {
                $table->float('grid_power_sunsync')->nullable()->after('grid_power');
            }
            
            // Rename car node connection fields for consistency with the data array
            // Only rename if the source column exists
            if (Schema::hasColumn('energy_flow_logs', 'car_node_pst')) {
                $table->renameColumn('car_node_pst', 'car_node_connection');
            }
            if (Schema::hasColumn('energy_flow_logs', 'car_node_zmo')) {
                $table->renameColumn('car_node_zmo', 'car_node_Mode');
            }
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