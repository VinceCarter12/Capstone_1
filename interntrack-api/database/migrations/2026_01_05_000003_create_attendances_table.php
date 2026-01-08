<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates attendances table to store all clock-in/clock-out records
     * with GPS location proof and selfie verification.
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            
            // Type of attendance record
            $table->enum('type', ['clock_in', 'clock_out', 'break_in', 'break_out']);
            
            // GPS coordinates at time of recording
            $table->decimal('latitude', 10, 8);   // Range: -90 to 90
            $table->decimal('longitude', 11, 8); // Range: -180 to 180
            
            // Path to selfie image in storage
            $table->string('selfie_path', 255)->nullable();
            
            // Actual time the attendance was recorded (device time)
            $table->timestamp('recorded_at');
            
            // Optional: Reference to which geofence was used for verification
            $table->foreignId('geofence_id')
                ->nullable()
                ->constrained('company_geofences')
                ->onDelete('set null');
            
            // Distance from geofence center at time of recording (for auditing)
            $table->decimal('distance_meters', 8, 2)->nullable();
            
            // For offline sync: whether this was synced from offline queue
            $table->boolean('synced_offline')->default(false);
            
            $table->timestamps();
            
            // Indexes for common queries
            $table->index('user_id');
            $table->index('recorded_at');
            $table->index(['user_id', 'type', 'recorded_at']); // For checking today's attendance
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};

