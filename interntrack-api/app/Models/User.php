<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'fname',
        'mname',
        'lname',
        'email',
        'contact_number',
        'course',
        'year',
        'section',
        'password',
        'role',
        'student_id',
        'ojt_status',
        'verification_status',
        'required_ojt_hours',
        'total_hours_completed',
        'approved_placement_id',
        'is_activated',
        'activated_at',
        'company_id',
        'status',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * Send the password reset notification.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * Get the company the user is assigned to (for internship).
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get all attendance records for this user.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Get today's attendance records for this user.
     */
    public function todayAttendances(): HasMany
    {
        return $this->hasMany(Attendance::class)
            ->whereDate('recorded_at', now()->toDateString());
    }

    /**
     * Check if user has clocked in today.
     */
    public function hasClockedInToday(): bool
    {
        return $this->attendances()
            ->where('type', Attendance::TYPE_CLOCK_IN)
            ->whereDate('recorded_at', now()->toDateString())
            ->exists();
    }

    /**
     * Check if user has clocked out today.
     */
    public function hasClockedOutToday(): bool
    {
        return $this->attendances()
            ->where('type', Attendance::TYPE_CLOCK_OUT)
            ->whereDate('recorded_at', now()->toDateString())
            ->exists();
    }

    /**
     * Check if user is a student.
     */
    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    /**
     * Check if user is faculty.
     */
    public function isFaculty(): bool
    {
        return $this->role === 'faculty';
    }

    /**
     * Get placement requests submitted by this user.
     */
    public function placementRequests(): HasMany
    {
        return $this->hasMany(PlacementRequest::class);
    }

    /**
     * Get the latest/active placement request.
     */
    public function latestPlacementRequest(): HasOne
    {
        return $this->hasOne(PlacementRequest::class)->latestOfMany();
    }

    /**
     * Get approved placement request.
     */
    public function approvedPlacement(): BelongsTo
    {
        return $this->belongsTo(PlacementRequest::class, 'approved_placement_id');
    }

    /**
     * Get announcements read by this user.
     */
    public function readAnnouncements(): BelongsToMany
    {
        return $this->belongsToMany(Announcement::class, 'announcement_reads')
            ->withPivot('read_at')
            ->withTimestamps();
    }

    /**
     * Check if user's placement is verified/approved.
     */
    public function isVerified(): bool
    {
        return $this->verification_status === 'approved';
    }

    /**
     * Check if user can clock in (verified and has company assigned).
     */
    public function canClockIn(): bool
    {
        return $this->isVerified() && $this->company_id !== null;
    }

    /**
     * Calculate total hours worked from attendance records.
     */
    public function calculateTotalHours(): float
    {
        $attendances = $this->attendances()
            ->orderBy('recorded_at')
            ->get();

        $totalMinutes = 0;
        $clockInTime = null;
        $breakStartTime = null;

        foreach ($attendances as $attendance) {
            switch ($attendance->type) {
                case Attendance::TYPE_CLOCK_IN:
                    $clockInTime = $attendance->recorded_at;
                    break;
                case Attendance::TYPE_BREAK_IN:
                    $breakStartTime = $attendance->recorded_at;
                    break;
                case Attendance::TYPE_BREAK_OUT:
                    // Don't count break time
                    $breakStartTime = null;
                    break;
                case Attendance::TYPE_CLOCK_OUT:
                    if ($clockInTime) {
                        $totalMinutes += $clockInTime->diffInMinutes($attendance->recorded_at);
                        $clockInTime = null;
                    }
                    break;
            }
        }

        return round($totalMinutes / 60, 2);
    }

    /**
     * Get OJT completion percentage.
     */
    public function getCompletionPercentageAttribute(): float
    {
        if ($this->required_ojt_hours <= 0) {
            return 0;
        }
        
        return min(100, round(($this->total_hours_completed / $this->required_ojt_hours) * 100, 1));
    }

    /**
     * Check if OJT hours are completed.
     */
    public function hasCompletedOjt(): bool
    {
        return $this->total_hours_completed >= $this->required_ojt_hours;
    }
}
