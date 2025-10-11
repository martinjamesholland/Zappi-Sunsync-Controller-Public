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
            // Drop the old load-related boolean columns
            $table->dropColumn([
                'to_load',
                'to_smart_load',
                'to_ups_load',
                'to_home_load',
            ]);

            // Rename and modify the load power columns to be signed
            $table->float('ups_load_power')->change();
            $table->float('smart_load_power')->change();
            $table->float('home_load_power')->change();
            $table->float('total_load_power')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('energy_flow_logs', function (Blueprint $table) {
            // Add back the boolean columns
            $table->boolean('to_load')->default(false);
            $table->boolean('to_smart_load')->default(false);
            $table->boolean('to_ups_load')->default(false);
            $table->boolean('to_home_load')->default(false);
        });
    }
};
