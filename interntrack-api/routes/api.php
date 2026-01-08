<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AnnouncementController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PlacementController;
use App\Http\Controllers\Api\StudentImportController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application.
| These routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group.
|
*/

// Public routes (no authentication required)
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/reset-password', [AuthController::class, 'resetPassword']);

// Protected routes (require authentication)
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // Student Import routes (faculty/admin only)
    Route::get('/student-imports', [StudentImportController::class, 'index']);
    Route::post('/student-imports', [StudentImportController::class, 'store']);
    Route::get('/student-imports/{id}', [StudentImportController::class, 'show']);
    Route::post('/student-imports/{id}/confirm', [StudentImportController::class, 'confirm']);
    Route::post('/student-imports/{id}/cancel', [StudentImportController::class, 'cancel']);

    // =========================================================================
    // Placement Request Routes (Students)
    // =========================================================================
    Route::prefix('placement')->group(function () {
        Route::post('/submit', [PlacementController::class, 'submit']);
        Route::get('/status', [PlacementController::class, 'status']);
        Route::post('/resubmit', [PlacementController::class, 'resubmit']);
    });
    
    // Get partnered companies for map display
    Route::get('/companies/partnered', [PlacementController::class, 'getPartneredCompanies']);

    // =========================================================================
    // Announcements Routes (Students)
    // =========================================================================
    Route::prefix('announcements')->group(function () {
        Route::get('/', [AnnouncementController::class, 'index']);
        Route::post('/{id}/read', [AnnouncementController::class, 'markAsRead']);
        Route::post('/read-all', [AnnouncementController::class, 'markAllAsRead']);
    });

    // =========================================================================
    // Attendance & Clock-In Routes (Students)
    // =========================================================================
    
    // Get geofence data for user's assigned company
    Route::get('/company/geofence', [AttendanceController::class, 'getCompanyGeofence']);
    
    // Clock-in and clock-out with location and selfie
    Route::post('/attendance/clock-in', [AttendanceController::class, 'clockIn']);
    Route::post('/attendance/clock-out', [AttendanceController::class, 'clockOut']);
    
    // Break in/out
    Route::post('/attendance/break-in', [AttendanceController::class, 'breakIn']);
    Route::post('/attendance/break-out', [AttendanceController::class, 'breakOut']);
    
    // Get today's attendance status
    Route::get('/attendance/today', [AttendanceController::class, 'getTodayStatus']);
    
    // Get attendance history
    Route::get('/attendance/history', [AttendanceController::class, 'getHistory']);
    
    // Get hours summary
    Route::get('/attendance/hours-summary', [AttendanceController::class, 'getHoursSummary']);

    // =========================================================================
    // Admin Routes (Faculty only)
    // =========================================================================
    Route::prefix('admin')->group(function () {
        // Company management
        Route::get('/companies', [AdminController::class, 'getCompanies']);
        Route::post('/companies', [AdminController::class, 'createCompany']);
        Route::put('/companies/{id}', [AdminController::class, 'updateCompany']);
        
        // Geofence management
        Route::post('/companies/{companyId}/geofences', [AdminController::class, 'addGeofence']);
        Route::put('/geofences/{id}', [AdminController::class, 'updateGeofence']);
        Route::delete('/geofences/{id}', [AdminController::class, 'deleteGeofence']);
        
        // Student management
        Route::get('/students', [AdminController::class, 'getStudents']);
        Route::put('/users/{userId}/assign-company', [AdminController::class, 'assignUserToCompany']);
    });
});
