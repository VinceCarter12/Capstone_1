<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates announcements table for admin-to-student notifications.
     */
    public function up(): void
    {
        Schema::create('announcements', function (Blueprint $table) {
            $table->id();
            
            $table->string('title');
            $table->text('content');
            
            // Target audience (null = all students)
            $table->string('target_course')->nullable(); // e.g., 'BSIT'
            $table->string('target_section')->nullable(); // e.g., '3-1'
            $table->string('target_year')->nullable(); // e.g., '3'
            
            // Priority level for display
            $table->enum('priority', ['low', 'normal', 'high', 'urgent'])
                ->default('normal');
            
            // Scheduling
            $table->timestamp('publish_at')->nullable(); // When to publish
            $table->timestamp('expires_at')->nullable(); // When to hide
            
            $table->boolean('is_active')->default(true);
            
            // Created by admin
            $table->foreignId('created_by')
                ->constrained('admin_users')
                ->onDelete('cascade');
            
            $table->timestamps();
            
            // Indexes
            $table->index('is_active');
            $table->index('publish_at');
            $table->index('created_at');
        });

        // Track which users have read which announcements
        Schema::create('announcement_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('announcement_id')
                ->constrained('announcements')
                ->onDelete('cascade');
            $table->foreignId('user_id')
                ->constrained('users')
                ->onDelete('cascade');
            $table->timestamp('read_at');
            
            $table->unique(['announcement_id', 'user_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('announcement_reads');
        Schema::dropIfExists('announcements');
    }
};
