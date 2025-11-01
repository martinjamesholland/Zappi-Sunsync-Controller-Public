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
        // dim_date
        Schema::create('dim_date', function (Blueprint $table) {
            $table->integer('date_key')->primary(); // YYYYMMDD
            $table->date('date');
            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month');
            $table->unsignedTinyInteger('day');
            $table->unsignedTinyInteger('week');
            $table->boolean('is_weekend')->default(false);
            $table->timestamps();
        });

        // dim_time (per minute of day)
        Schema::create('dim_time', function (Blueprint $table) {
            $table->unsignedSmallInteger('time_key')->primary(); // HHMM (e.g., 1330)
            $table->unsignedTinyInteger('hour');
            $table->unsignedTinyInteger('minute');
            $table->string('daypart', 16)->nullable();
            $table->timestamps();
        });

        // Aggregation: 15-minute buckets
        Schema::create('agg_energy_15min', function (Blueprint $table) {
            $table->id();
            $table->integer('date_key');
            $table->unsignedSmallInteger('time_key'); // start minute of the 15-min bucket
            $table->dateTime('window_start');
            $table->dateTime('window_end');

            // Aggregated metrics (sums / averages as appropriate)
            $table->decimal('sum_grid_power', 14, 3)->default(0);
            $table->decimal('sum_total_pv_power', 14, 3)->default(0);
            $table->decimal('sum_home_load_power', 14, 3)->default(0);
            $table->decimal('sum_battery_power', 14, 3)->default(0);
            $table->decimal('sum_zappi_node', 14, 3)->default(0);

            $table->unsignedInteger('sample_count')->default(0);

            $table->timestamps();

            $table->unique(['window_start', 'window_end'], 'uniq_agg15_window');
            $table->index(['date_key', 'time_key'], 'idx_agg15_date_time');
        });

        // Aggregation: daily
        Schema::create('agg_energy_daily', function (Blueprint $table) {
            $table->id();
            $table->integer('date_key');
            $table->date('date');

            $table->decimal('sum_grid_power', 16, 3)->default(0);
            $table->decimal('sum_total_pv_power', 16, 3)->default(0);
            $table->decimal('sum_home_load_power', 16, 3)->default(0);
            $table->decimal('sum_battery_power', 16, 3)->default(0);
            $table->decimal('sum_zappi_node', 16, 3)->default(0);

            $table->unsignedInteger('sample_count')->default(0);

            $table->timestamps();

            $table->unique(['date_key'], 'uniq_agg_daily_date');
            $table->index(['date_key'], 'idx_agg_daily_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agg_energy_daily');
        Schema::dropIfExists('agg_energy_15min');
        Schema::dropIfExists('dim_time');
        Schema::dropIfExists('dim_date');
    }
};


