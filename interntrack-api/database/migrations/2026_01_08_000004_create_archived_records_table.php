<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates archived_records table for storing completed OJT records
     * by academic year, course, and section.
     */
    public function up(): void
    {
        Schema::create('archived_records', function (Blueprint $table) {
            $table->id();
            
            // Academic period
            $table->string('academic_year', 20); // e.g., "2025-2026"
            $table->string('semester', 20)->nullable(); // e.g., "1st Semester"
            
            // Filter criteria used for archival
            $table->string('course')->nullable(); // e.g., 'BSIT'
            $table->string('section')->nullable(); // e.g., '3-1'
            $table->string('year_level')->nullable(); // e.g., '3'
            
            // Archive metadata
            $table->string('archive_name'); // Human-readable name
            $table->text('description')->nullable();
            
            // Archived data (JSON snapshot)
            $table->json('student_records'); // Array of student data with attendance
            $table->integer('total_students');
            $table->integer('total_attendance_records');
            
            // Stats summary
            $table->decimal('avg_hours_completed', 8, 2)->nullable();
            $table->integer('students_completed')->default(0);
            $table->integer('students_incomplete')->default(0);
            
            // Who archived
            $table->foreignId('archived_by')
                ->constrained('admin_users')
                ->onDelete('cascade');
            
            $table->timestamps();
            
            // Indexes
            $table->index('academic_year');
            $table->index('course');
            $table->index(['academic_year', 'course', 'section']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('archived_records');
    }
};
