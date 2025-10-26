<?php

declare(strict_types=1);

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
        Schema::create('ev_charging_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('value');
            $table->timestamps();
            
            // Add index for faster lookups
            $table->index('key');
        });
        
        // Migrate existing CSV data if it exists
        $this->migrateFromCsv();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ev_charging_settings');
    }
    
    /**
     * Migrate data from CSV file if it exists
     */
    private function migrateFromCsv(): void
    {
        $csvPath = storage_path('app/ev-charging-settings.csv');
        
        if (!file_exists($csvPath)) {
            // Insert default values
            $defaults = [
                ['key' => 'default_sell_time', 'value' => '22:00'],
                ['key' => 'default_cap', 'value' => '20'],
                ['key' => 'night_start', 'value' => '23:30'],
                ['key' => 'night_end', 'value' => '05:30'],
            ];
            
            foreach ($defaults as $setting) {
                DB::table('ev_charging_settings')->insert([
                    'key' => $setting['key'],
                    'value' => $setting['value'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            return;
        }
        
        // Read CSV and migrate data
        $content = file_get_contents($csvPath);
        $lines = explode("\n", trim($content));
        
        // Skip header row
        array_shift($lines);
        
        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }
            
            $data = str_getcsv($line);
            if (count($data) >= 2) {
                DB::table('ev_charging_settings')->insert([
                    'key' => $data[0],
                    'value' => $data[1],
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
};
