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
            $table->float('home_load_sunsync')->default(0);
            $table->float('combined_load_node_sunsync')->default(0);
            $table->float('combined_load_node')->default(0);
            $table->float('zappi_node')->default(0);
            $table->string('car_node_pst')->nullable();
            $table->string('car_node_zmo')->nullable();
            $table->integer('car_node_sta')->nullable();
            $table->float('last_consumption')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('energy_flow_logs', function (Blueprint $table) {
            $table->dropColumn([
                'home_load_sunsync',
                'combined_load_node_sunsync',
                'combined_load_node',
                'zappi_node',
                'car_node_pst',
                'car_node_zmo',
                'car_node_sta',
                'last_consumption'
            ]);
        });
    }
}; 