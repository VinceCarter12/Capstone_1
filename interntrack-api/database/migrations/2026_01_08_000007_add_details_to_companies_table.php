<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds more details to companies table for partnered companies display.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            // Company details for display
            $table->text('description')->nullable()->after('phone');
            $table->text('job_roles')->nullable()->after('description'); // JSON array
            $table->text('requirements')->nullable()->after('job_roles'); // JSON array
            
            // Partnership status
            $table->boolean('is_partnered')->default(false)->after('requirements');
            $table->boolean('is_verified')->default(false)->after('is_partnered');
            
            // Who submitted this company (if from student request)
            $table->foreignId('submitted_by_user_id')
                ->nullable()
                ->after('is_verified')
                ->constrained('users')
                ->onDelete('set null');
            
            // Contact details
            $table->string('email')->nullable()->after('phone');
            $table->string('website')->nullable()->after('email');
            
            $table->index('is_partnered');
            $table->index('is_verified');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropIndex(['is_partnered']);
            $table->dropIndex(['is_verified']);
            $table->dropForeign(['submitted_by_user_id']);
            $table->dropColumn([
                'description',
                'job_roles',
                'requirements',
                'is_partnered',
                'is_verified',
                'submitted_by_user_id',
                'email',
                'website',
            ]);
        });
    }
};
