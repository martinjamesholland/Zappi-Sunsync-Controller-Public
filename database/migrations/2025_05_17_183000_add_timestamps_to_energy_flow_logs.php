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
            $table->timestamp('sunsync_updated_at')->nullable()->after('grid_to_inverter');
            $table->timestamp('zappi_updated_at')->nullable()->after('sunsync_updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('energy_flow_logs', function (Blueprint $table) {
            $table->dropColumn(['sunsync_updated_at', 'zappi_updated_at']);
        });
    }
}; 