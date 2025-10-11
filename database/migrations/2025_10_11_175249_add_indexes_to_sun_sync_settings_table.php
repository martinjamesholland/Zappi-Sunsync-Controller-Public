<?php

declare(strict_types=1);

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
        // Check if indexes exist using raw SQL
        $indexExists = collect(Schema::getConnection()
            ->select("SHOW INDEX FROM sun_sync_settings WHERE Key_name = 'sun_sync_settings_inverter_sn_index'"))
            ->isNotEmpty();
        
        $compositeIndexExists = collect(Schema::getConnection()
            ->select("SHOW INDEX FROM sun_sync_settings WHERE Key_name = 'sun_sync_settings_inverter_sn_last_updated_index'"))
            ->isNotEmpty();
        
        Schema::table('sun_sync_settings', function (Blueprint $table) use ($indexExists, $compositeIndexExists) {
            // Add index on inverter_sn for faster lookups if it doesn't exist
            if (!$indexExists) {
                $table->index('inverter_sn');
            }
            
            // Add composite index for cache lookups if it doesn't exist
            if (!$compositeIndexExists) {
                $table->index(['inverter_sn', 'last_updated']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sun_sync_settings', function (Blueprint $table) {
            // Check if indexes exist before dropping
            $indexExists = collect(Schema::getConnection()
                ->select("SHOW INDEX FROM sun_sync_settings WHERE Key_name = 'sun_sync_settings_inverter_sn_index'"))
                ->isNotEmpty();
            
            $compositeIndexExists = collect(Schema::getConnection()
                ->select("SHOW INDEX FROM sun_sync_settings WHERE Key_name = 'sun_sync_settings_inverter_sn_last_updated_index'"))
                ->isNotEmpty();
            
            // Drop the indexes if they exist
            if ($indexExists) {
                $table->dropIndex(['inverter_sn']);
            }
            if ($compositeIndexExists) {
                $table->dropIndex(['inverter_sn', 'last_updated']);
            }
        });
    }
};
