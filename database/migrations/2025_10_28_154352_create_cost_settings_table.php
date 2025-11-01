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
        Schema::create('cost_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->decimal('value', 10, 4);
            $table->string('description')->nullable();
            $table->timestamps();
            
            // Add index for faster lookups
            $table->index('key');
        });
        
        // Insert default cost settings
        $defaults = [
            ['key' => 'peak_rate', 'value' => 0.30, 'description' => 'Peak hours rate (05:30-23:30)'],
            ['key' => 'off_peak_rate', 'value' => 0.07, 'description' => 'Off-peak hours rate (23:30-05:30)'],
            ['key' => 'ev_charging_rate', 'value' => 0.07, 'description' => 'EV charging rate'],
            ['key' => 'export_credit_rate', 'value' => 0.15, 'description' => 'Export to grid credit rate'],
            ['key' => 'peak_start', 'value' => 530, 'description' => 'Peak hours start time (HHMM)'],
            ['key' => 'peak_end', 'value' => 2330, 'description' => 'Peak hours end time (HHMM)'],
        ];
        
        foreach ($defaults as $setting) {
            DB::table('cost_settings')->insert([
                'key' => $setting['key'],
                'value' => $setting['value'],
                'description' => $setting['description'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cost_settings');
    }
};
