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
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();
        
        // Check if indexes exist using database-specific syntax
        $indexExists = false;
        $compositeIndexExists = false;
        
        if ($driver === 'mysql' || $driver === 'mariadb') {
            $indexExists = collect($connection
                ->select("SHOW INDEX FROM sun_sync_settings WHERE Key_name = 'sun_sync_settings_inverter_sn_index'"))
                ->isNotEmpty();
            
            $compositeIndexExists = collect($connection
                ->select("SHOW INDEX FROM sun_sync_settings WHERE Key_name = 'sun_sync_settings_inverter_sn_last_updated_index'"))
                ->isNotEmpty();
        } elseif ($driver === 'sqlite') {
            // For SQLite, check index list
            $indexes = $connection->select("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='sun_sync_settings'");
            $indexNames = collect($indexes)->pluck('name')->toArray();
            
            $indexExists = in_array('sun_sync_settings_inverter_sn_index', $indexNames);
            $compositeIndexExists = in_array('sun_sync_settings_inverter_sn_last_updated_index', $indexNames);
        }
        
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
        $connection = Schema::getConnection();
        $driver = $connection->getDriverName();
        
        // Check if indexes exist using database-specific syntax
        $indexExists = false;
        $compositeIndexExists = false;
        
        if ($driver === 'mysql' || $driver === 'mariadb') {
            $indexExists = collect($connection
                ->select("SHOW INDEX FROM sun_sync_settings WHERE Key_name = 'sun_sync_settings_inverter_sn_index'"))
                ->isNotEmpty();
            
            $compositeIndexExists = collect($connection
                ->select("SHOW INDEX FROM sun_sync_settings WHERE Key_name = 'sun_sync_settings_inverter_sn_last_updated_index'"))
                ->isNotEmpty();
        } elseif ($driver === 'sqlite') {
            // For SQLite, check index list
            $indexes = $connection->select("SELECT name FROM sqlite_master WHERE type='index' AND tbl_name='sun_sync_settings'");
            $indexNames = collect($indexes)->pluck('name')->toArray();
            
            $indexExists = in_array('sun_sync_settings_inverter_sn_index', $indexNames);
            $compositeIndexExists = in_array('sun_sync_settings_inverter_sn_last_updated_index', $indexNames);
        }
        
        Schema::table('sun_sync_settings', function (Blueprint $table) use ($indexExists, $compositeIndexExists) {
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
