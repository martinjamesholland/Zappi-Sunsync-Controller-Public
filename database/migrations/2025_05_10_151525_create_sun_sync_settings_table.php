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
        Schema::create('sun_sync_settings', function (Blueprint $table) {
            $table->id();
            $table->string('inverter_sn');
            $table->json('settings');
            $table->timestamp('last_updated');
            $table->timestamps();
            
            // Add index for faster lookups
            $table->index('inverter_sn');
            $table->index('last_updated');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sun_sync_settings');
    }
};
