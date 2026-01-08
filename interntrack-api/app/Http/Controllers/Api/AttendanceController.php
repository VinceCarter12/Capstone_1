<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\CompanyGeofence;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AttendanceController extends Controller
{
    /**
     * Get the geofence(s) for the authenticated user's assigned company.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getCompanyGeofence(Request $request): JsonResponse
    {
        $user = $request->user();

        // Check if user is assigned to a company
        if (!$user->company_id) {
            return response()->json([
                'message' => 'You are not assigned to any company yet. Please contact your administrator.',
                'error' => 'no_company_assigned',
            ], 422);
        }

        // Load company with active geofences
        $company = $user->company()->with('activeGeofences')->first();

        if (!$company) {
            return response()->json([
                'message' => 'Company not found.',
                'error' => 'company_not_found',
            ], 404);
        }

        $geofences = $company->activeGeofences;

        if ($geofences->isEmpty()) {
            return response()->json([
                'message' => 'No geofence configured for your company. Please contact your administrator.',
                'error' => 'no_geofence_configured',
            ], 422);
        }

        return response()->json([
            'company' => [
                'id' => $company->id,
                'name' => $company->name,
                'address' => $company->address,
            ],
            'geofences' => $geofences->map(function ($geofence) {
                return [
                    'id' => $geofence->id,
                    'name' => $geofence->name,
                    'latitude' => $geofence->latitude,
                    'longitude' => $geofence->longitude,
                    'radius_meters' => $geofence->radius_meters,
                ];
            }),
        ], 200);
    }

    /**
     * Record a clock-in with location and selfie verification.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function clockIn(Request $request): JsonResponse
    {
        return $this->recordAttendance($request, Attendance::TYPE_CLOCK_IN);
    }

    /**
     * Record a clock-out with location and selfie verification.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function clockOut(Request $request): JsonResponse
    {
        return $this->recordAttendance($request, Attendance::TYPE_CLOCK_OUT);
    }

    /**
     * Common method to record attendance (clock-in or clock-out).
     * 
     * @param Request $request
     * @param string $type
     * @return JsonResponse
     */
    private function recordAttendance(Request $request, string $type): JsonResponse
    {
        $user = $request->user();

        // Validate request
        $validated = $request->validate([
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'selfie' => 'required|image|mimes:jpeg,jpg,png|max:5120', // 5MB max
            'recorded_at' => 'nullable|date', // For offline sync
            'synced_offline' => 'nullable|boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        // Check if user is assigned to a company
        if (!$user->company_id) {
            return response()->json([
                'message' => 'You are not assigned to any company.',
                'error' => 'no_company_assigned',
            ], 422);
        }

        // Check for duplicate clock-in/out today
        $existingToday = Attendance::where('user_id', $user->id)
            ->where('type', $type)
            ->whereDate('recorded_at', now()->toDateString())
            ->first();

        if ($existingToday) {
            $typeLabel = $type === Attendance::TYPE_CLOCK_IN ? 'clocked in' : 'clocked out';
            return response()->json([
                'message' => "You have already {$typeLabel} today.",
                'error' => 'duplicate_attendance',
                'existing_record' => [
                    'id' => $existingToday->id,
                    'recorded_at' => $existingToday->recorded_at->toIso8601String(),
                ],
            ], 422);
        }

        // For clock-out, verify that user has clocked in today
        if ($type === Attendance::TYPE_CLOCK_OUT && !$user->hasClockedInToday()) {
            return response()->json([
                'message' => 'You must clock in before clocking out.',
                'error' => 'not_clocked_in',
            ], 422);
        }

        // Get company geofences and verify location
        $geofences = CompanyGeofence::where('company_id', $user->company_id)
            ->where('is_active', true)
            ->get();

        if ($geofences->isEmpty()) {
            return response()->json([
                'message' => 'No geofence configured for your company.',
                'error' => 'no_geofence_configured',
            ], 422);
        }

        // Find if user is within any geofence
        $latitude = (float) $validated['latitude'];
        $longitude = (float) $validated['longitude'];
        $validGeofence = null;
        $minDistance = PHP_FLOAT_MAX;

        foreach ($geofences as $geofence) {
            $distance = $geofence->calculateDistance($latitude, $longitude);
            if ($distance < $minDistance) {
                $minDistance = $distance;
            }
            if ($geofence->isWithinRadius($latitude, $longitude)) {
                $validGeofence = $geofence;
                break;
            }
        }

        if (!$validGeofence) {
            return response()->json([
                'message' => 'You are outside the allowed area. Please move closer to clock in.',
                'error' => 'outside_geofence',
                'distance_meters' => round($minDistance, 2),
                'required_radius_meters' => $geofences->first()->radius_meters,
            ], 422);
        }

        // Store selfie
        $selfiePath = $this->storeSelfie($request->file('selfie'), $user->id);

        // Create attendance record
        $attendance = Attendance::create([
            'user_id' => $user->id,
            'type' => $type,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'selfie_path' => $selfiePath,
            'recorded_at' => $validated['recorded_at'] ?? now(),
            'geofence_id' => $validGeofence->id,
            'distance_meters' => round($minDistance, 2),
            'synced_offline' => $validated['synced_offline'] ?? false,
            'notes' => $validated['notes'] ?? null,
        ]);

        $typeLabel = $type === Attendance::TYPE_CLOCK_IN ? 'Clock In' : 'Clock Out';

        return response()->json([
            'message' => "{$typeLabel} recorded successfully!",
            'attendance' => [
                'id' => $attendance->id,
                'type' => $attendance->type,
                'recorded_at' => $attendance->recorded_at->toIso8601String(),
                'latitude' => $attendance->latitude,
                'longitude' => $attendance->longitude,
                'selfie_url' => $attendance->selfie_url,
                'geofence_name' => $validGeofence->name,
                'distance_meters' => $attendance->distance_meters,
            ],
        ], 201);
    }

    /**
     * Store the selfie image and return the path.
     * 
     * @param \Illuminate\Http\UploadedFile $file
     * @param int $userId
     * @return string
     */
    private function storeSelfie($file, int $userId): string
    {
        $date = now()->format('Y-m-d');
        $timestamp = now()->format('His');
        $extension = $file->getClientOriginalExtension();
        $filename = "selfie_{$timestamp}.{$extension}";
        
        $path = "attendance_selfies/{$userId}/{$date}";
        
        // Use the default disk configured in FILESYSTEM_DISK
        return $file->storeAs($path, $filename);
    }

    /**
     * Get today's attendance status for the authenticated user.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getTodayStatus(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $todayAttendances = Attendance::where('user_id', $user->id)
            ->whereDate('recorded_at', now()->toDateString())
            ->orderBy('recorded_at', 'asc')
            ->get();

        $clockIn = $todayAttendances->firstWhere('type', Attendance::TYPE_CLOCK_IN);
        $clockOut = $todayAttendances->firstWhere('type', Attendance::TYPE_CLOCK_OUT);
        $breakIn = $todayAttendances->firstWhere('type', Attendance::TYPE_BREAK_IN);
        $breakOut = $todayAttendances->firstWhere('type', Attendance::TYPE_BREAK_OUT);

        // Calculate working hours if both clock in and out exist
        $workingMinutes = null;
        $breakMinutes = null;
        
        if ($clockIn && $clockOut) {
            $workingMinutes = $clockIn->recorded_at->diffInMinutes($clockOut->recorded_at);
        }
        
        // Calculate break time if both break in and out exist
        if ($breakIn && $breakOut) {
            $breakMinutes = $breakIn->recorded_at->diffInMinutes($breakOut->recorded_at);
            // Subtract break time from working hours
            if ($workingMinutes !== null) {
                $workingMinutes -= $breakMinutes;
            }
        }

        return response()->json([
            'date' => now()->toDateString(),
            'has_clocked_in' => $clockIn !== null,
            'has_clocked_out' => $clockOut !== null,
            'has_break_in' => $breakIn !== null,
            'has_break_out' => $breakOut !== null,
            'clock_in' => $clockIn ? [
                'id' => $clockIn->id,
                'recorded_at' => $clockIn->recorded_at->toIso8601String(),
                'selfie_url' => $clockIn->selfie_url,
            ] : null,
            'clock_out' => $clockOut ? [
                'id' => $clockOut->id,
                'recorded_at' => $clockOut->recorded_at->toIso8601String(),
                'selfie_url' => $clockOut->selfie_url,
            ] : null,
            'break_in' => $breakIn ? [
                'id' => $breakIn->id,
                'recorded_at' => $breakIn->recorded_at->toIso8601String(),
                'selfie_url' => $breakIn->selfie_url,
            ] : null,
            'break_out' => $breakOut ? [
                'id' => $breakOut->id,
                'recorded_at' => $breakOut->recorded_at->toIso8601String(),
                'selfie_url' => $breakOut->selfie_url,
            ] : null,
            'break_minutes' => $breakMinutes,
            'working_minutes' => $workingMinutes,
            'working_hours_formatted' => $workingMinutes 
                ? sprintf('%02d:%02d', floor($workingMinutes / 60), $workingMinutes % 60)
                : null,
        ], 200);
    }

    /**
     * Record a break-in.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function breakIn(Request $request): JsonResponse
    {
        return $this->recordAttendance($request, Attendance::TYPE_BREAK_IN);
    }

    /**
     * Record a break-out.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function breakOut(Request $request): JsonResponse
    {
        return $this->recordAttendance($request, Attendance::TYPE_BREAK_OUT);
    }

    /**
     * Get hours summary for the authenticated user.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getHoursSummary(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Get today's hours
        $todayMinutes = $this->calculateWorkingMinutes($user->id, now()->startOfDay(), now()->endOfDay());
        
        // Get this week's hours
        $weekMinutes = $this->calculateWorkingMinutes($user->id, now()->startOfWeek(), now()->endOfWeek());
        
        // Get this month's hours
        $monthMinutes = $this->calculateWorkingMinutes($user->id, now()->startOfMonth(), now()->endOfMonth());
        
        // Get total hours
        $totalMinutes = $this->calculateWorkingMinutes($user->id);
        
        return response()->json([
            'today' => [
                'minutes' => $todayMinutes,
                'hours' => round($todayMinutes / 60, 2),
                'formatted' => sprintf('%02d:%02d', floor($todayMinutes / 60), $todayMinutes % 60),
            ],
            'this_week' => [
                'minutes' => $weekMinutes,
                'hours' => round($weekMinutes / 60, 2),
                'formatted' => sprintf('%02d:%02d', floor($weekMinutes / 60), $weekMinutes % 60),
            ],
            'this_month' => [
                'minutes' => $monthMinutes,
                'hours' => round($monthMinutes / 60, 2),
                'formatted' => sprintf('%02d:%02d', floor($monthMinutes / 60), $monthMinutes % 60),
            ],
            'total' => [
                'minutes' => $totalMinutes,
                'hours' => round($totalMinutes / 60, 2),
                'formatted' => sprintf('%02d:%02d', floor($totalMinutes / 60), $totalMinutes % 60),
            ],
            'required_hours' => $user->required_ojt_hours ?? 400,
            'completion_percentage' => $user->required_ojt_hours > 0 
                ? min(100, round((($totalMinutes / 60) / $user->required_ojt_hours) * 100, 1))
                : 0,
        ], 200);
    }
    
    /**
     * Calculate working minutes for a user within a date range.
     */
    private function calculateWorkingMinutes(int $userId, $startDate = null, $endDate = null): int
    {
        $query = Attendance::where('user_id', $userId)
            ->orderBy('recorded_at');
            
        if ($startDate) {
            $query->where('recorded_at', '>=', $startDate);
        }
        if ($endDate) {
            $query->where('recorded_at', '<=', $endDate);
        }
        
        $attendances = $query->get();
        
        $totalMinutes = 0;
        $clockInTime = null;
        $breakStartTime = null;
        $breakMinutes = 0;
        
        foreach ($attendances as $attendance) {
            switch ($attendance->type) {
                case Attendance::TYPE_CLOCK_IN:
                    $clockInTime = $attendance->recorded_at;
                    $breakMinutes = 0;
                    break;
                case Attendance::TYPE_BREAK_IN:
                    $breakStartTime = $attendance->recorded_at;
                    break;
                case Attendance::TYPE_BREAK_OUT:
                    if ($breakStartTime) {
                        $breakMinutes += $breakStartTime->diffInMinutes($attendance->recorded_at);
                        $breakStartTime = null;
                    }
                    break;
                case Attendance::TYPE_CLOCK_OUT:
                    if ($clockInTime) {
                        $sessionMinutes = $clockInTime->diffInMinutes($attendance->recorded_at);
                        $totalMinutes += max(0, $sessionMinutes - $breakMinutes);
                        $clockInTime = null;
                        $breakMinutes = 0;
                    }
                    break;
            }
        }
        
        return $totalMinutes;
    }

    /**
     * Get attendance history for the authenticated user.
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getHistory(Request $request): JsonResponse
    {
        $user = $request->user();
        
        $validated = $request->validate([
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'type' => ['nullable', Rule::in([
                Attendance::TYPE_CLOCK_IN,
                Attendance::TYPE_CLOCK_OUT,
                Attendance::TYPE_BREAK_IN,
                Attendance::TYPE_BREAK_OUT,
            ])],
        ]);

        $query = Attendance::where('user_id', $user->id)
            ->orderBy('recorded_at', 'desc');

        // Apply filters
        if (isset($validated['start_date'])) {
            $query->whereDate('recorded_at', '>=', $validated['start_date']);
        }
        if (isset($validated['end_date'])) {
            $query->whereDate('recorded_at', '<=', $validated['end_date']);
        }
        if (isset($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        $perPage = $validated['per_page'] ?? 20;
        $attendances = $query->paginate($perPage);

        return response()->json([
            'data' => $attendances->map(function ($attendance) {
                return [
                    'id' => $attendance->id,
                    'type' => $attendance->type,
                    'recorded_at' => $attendance->recorded_at->toIso8601String(),
                    'date' => $attendance->recorded_at->toDateString(),
                    'time' => $attendance->recorded_at->format('H:i:s'),
                    'latitude' => $attendance->latitude,
                    'longitude' => $attendance->longitude,
                    'selfie_url' => $attendance->selfie_url,
                    'distance_meters' => $attendance->distance_meters,
                    'notes' => $attendance->notes,
                    'location_name' => $attendance->location_name,
                ];
            }),
            'pagination' => [
                'current_page' => $attendances->currentPage(),
                'last_page' => $attendances->lastPage(),
                'per_page' => $attendances->perPage(),
                'total' => $attendances->total(),
            ],
        ], 200);
    }
}

