<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create test student
        User::factory()->create([
            'fname' => 'Test',
            'lname' => 'Student',
            'email' => 'student@test.com',
            'password' => bcrypt('password'),
            'role' => 'student',
            'student_id' => '2021-00001',
            'ojt_status' => 'active',
        ]);

        // Create test faculty
        User::factory()->create([
            'fname' => 'Test',
            'lname' => 'Faculty',
            'email' => 'faculty@test.com',
            'password' => bcrypt('password'),
            'role' => 'faculty',
            'student_id' => null,
            'ojt_status' => 'pending',
        ]);
    }
}
