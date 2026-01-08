<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds clock-in notes and removes offline sync from attendances table.
     */
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            // Clock-in notes (optional comment from student)
            $table->text('notes')->nullable()->after('distance_meters');
            
            // Location name for display
            $table->string('location_name', 255)->nullable()->after('notes');
        });

        // Remove synced_offline column if it exists
        if (Schema::hasColumn('attendances', 'synced_offline')) {
            Schema::table('attendances', function (Blueprint $table) {
                $table->dropColumn('synced_offline');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['notes', 'location_name']);
            $table->boolean('synced_offline')->default(false);
        });
    }
};
