<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Creates admin_users table for web portal authentication.
     * Separate from mobile app users for security.
     */
    public function up(): void
    {
        Schema::create('admin_users', function (Blueprint $table) {
            $table->id();
            $table->string('admin_name', 50)->unique();
            $table->string('email')->unique();
            $table->string('password'); // bcrypt hashed
            $table->string('full_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();
            
            $table->index('admin_name');
            $table->index('email');
        });

        // Create default admin account
        DB::table('admin_users')->insert([
            'admin_name' => 'admin',
            'email' => 'admin@interntrack.online',
            'password' => Hash::make('Admin@123'), // Change this in production!
            'full_name' => 'System Administrator',
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_users');
    }
};
