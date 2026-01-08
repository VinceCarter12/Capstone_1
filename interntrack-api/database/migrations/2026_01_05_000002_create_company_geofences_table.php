<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates company_geofences table to store geofence locations
     * for each company. Companies can have multiple geofence zones
     * (e.g., main office, branch offices).
     */
    public function up(): void
    {
        Schema::create('company_geofences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')
                ->constrained('companies')
                ->onDelete('cascade');
            
            // GPS coordinates with high precision
            $table->decimal('latitude', 10, 8);   // Range: -90 to 90
            $table->decimal('longitude', 11, 8); // Range: -180 to 180
            
            // Geofence radius in meters (default 50m as per requirements)
            $table->unsignedInteger('radius_meters')->default(50);
            
            // Human-readable name for the location
            $table->string('name', 100)->nullable(); // e.g., "Main Office", "Branch 2"
            
            // Whether this geofence is currently active
            $table->boolean('is_active')->default(true);
            
            $table->timestamps();
            
            // Index for faster lookups by company
            $table->index('company_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('company_geofences');
    }
};

