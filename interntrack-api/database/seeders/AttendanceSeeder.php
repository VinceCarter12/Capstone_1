<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\CompanyGeofence;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AttendanceSeeder extends Seeder
{
    /**
     * Seed the database with test data for attendance/geofencing feature.
     * 
     * This creates:
     * - A test company with geofence
     * - A test student user assigned to the company
     */
    public function run(): void
    {
        // Create test company
        $company = Company::firstOrCreate(
            ['name' => 'COMPANY KO'],
            [
                'address' => 'Iron Street, Santa Maria, Bulacan',
                'phone' => '+63 912 345 6789',
            ]
        );

        $this->command->info("Company created/found: {$company->name}");

        // Create geofence for the company
        $geofence = CompanyGeofence::firstOrCreate(
            [
                'company_id' => $company->id,
                'name' => 'Main Office',
            ],
            [
                'latitude' => 14.876203,
                'longitude' => 120.996477,
                'radius_meters' => 50, // 50 meter radius as per requirements
                'is_active' => true,
            ]
        );

        $this->command->info("Geofence created/found: {$geofence->name} at ({$geofence->latitude}, {$geofence->longitude})");

        // Create a test student user (or update existing)
        $testStudent = User::firstOrCreate(
            ['email' => 'teststudent@interntrack.test'],
            [
                'fname' => 'Test',
                'lname' => 'Student',
                'password' => Hash::make('password123'),
                'role' => 'student',
                'student_id' => 'STU-2026-001',
                'ojt_status' => 'active',
                'is_activated' => true,
                'company_id' => $company->id,
            ]
        );

        // Ensure student is assigned to company
        if ($testStudent->company_id !== $company->id) {
            $testStudent->update(['company_id' => $company->id]);
        }

        $this->command->info("Test student created/found: {$testStudent->email}");
        $this->command->info("  - Assigned to company: {$company->name}");
        $this->command->info("  - Password: password123");

        // Create a test faculty user for admin access
        $testFaculty = User::firstOrCreate(
            ['email' => 'testfaculty@interntrack.test'],
            [
                'fname' => 'Test',
                'lname' => 'Faculty',
                'password' => Hash::make('password123'),
                'role' => 'faculty',
                'ojt_status' => 'pending',
                'is_activated' => true,
            ]
        );

        $this->command->info("Test faculty created/found: {$testFaculty->email}");
        $this->command->info("  - Password: password123");

        $this->command->newLine();
        $this->command->info('=== Attendance Seeder Complete ===');
        $this->command->info("Geofence Location: Iron Street, Santa Maria, Bulacan");
        $this->command->info("Coordinates: 14.876203, 120.996477");
        $this->command->info("Radius: 50 meters");
    }
}

