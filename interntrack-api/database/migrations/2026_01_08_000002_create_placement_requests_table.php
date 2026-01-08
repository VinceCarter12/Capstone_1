<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates placement_requests table for OJT verification workflow.
     * Students submit company info + proof of hiring for admin approval.
     */
    public function up(): void
    {
        Schema::create('placement_requests', function (Blueprint $table) {
            $table->id();
            
            // Student who submitted the request
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            
            // Company Information
            $table->string('company_name');
            $table->text('company_address');
            
            // Supervisor Information
            $table->string('supervisor_name');
            $table->string('supervisor_contact'); // Phone or email
            $table->string('supervisor_email')->nullable();
            
            // Location (pinned on map)
            $table->decimal('latitude', 10, 8);
            $table->decimal('longitude', 11, 8);
            
            // Proof of Hiring Document (AWS S3 URL)
            $table->string('proof_document_url', 500);
            $table->string('proof_document_type', 50)->default('image'); // image, pdf
            
            // Request Status
            $table->enum('status', ['pending', 'reviewing', 'approved', 'rejected'])
                ->default('pending');
            
            // Rejection Details (if rejected)
            $table->text('rejection_reason')->nullable();
            
            // Admin Review Info
            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('admin_users')
                ->onDelete('set null');
            $table->timestamp('reviewed_at')->nullable();
            
            // Notes from admin
            $table->text('admin_notes')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('user_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('placement_requests');
    }
};
