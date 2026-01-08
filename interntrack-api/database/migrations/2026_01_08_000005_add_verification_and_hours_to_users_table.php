<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds verification status and OJT hours tracking to users table
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Verification status for OJT placement
            $table->enum('verification_status', ['pending', 'reviewing', 'approved', 'rejected'])
                ->nullable()
                ->after('ojt_status');
            
            // OJT hours tracking
            $table->integer('required_ojt_hours')->default(400)->after('verification_status');
            $table->decimal('total_hours_completed', 10, 2)->default(0)->after('required_ojt_hours');
            
            // Approved company info (populated after placement approval)
            $table->foreignId('approved_placement_id')
                ->nullable()
                ->after('total_hours_completed')
                ->constrained('placement_requests')
                ->onDelete('set null');
            
            // Index for filtering by verification status
            $table->index('verification_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['approved_placement_id']);
            $table->dropColumn([
                'verification_status',
                'required_ojt_hours',
                'total_hours_completed',
                'approved_placement_id'
            ]);
        });
    }
};
