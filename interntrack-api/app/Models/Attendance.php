<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Attendance extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'latitude',
        'longitude',
        'selfie_path',
        'recorded_at',
        'geofence_id',
        'distance_meters',
        'synced_offline',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'distance_meters' => 'float',
        'recorded_at' => 'datetime',
        'synced_offline' => 'boolean',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = ['selfie_url'];

    /**
     * Attendance type constants.
     */
    public const TYPE_CLOCK_IN = 'clock_in';
    public const TYPE_CLOCK_OUT = 'clock_out';
    public const TYPE_BREAK_IN = 'break_in';
    public const TYPE_BREAK_OUT = 'break_out';

    /**
     * Get the user that owns this attendance record.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the geofence where this attendance was recorded.
     */
    public function geofence(): BelongsTo
    {
        return $this->belongsTo(CompanyGeofence::class, 'geofence_id');
    }

    /**
     * Get the full URL to the selfie image.
     *
     * @return string|null
     */
    public function getSelfieUrlAttribute(): ?string
    {
        if (!$this->selfie_path) {
            return null;
        }

        return asset('storage/' . $this->selfie_path);
    }

    /**
     * Scope to get today's attendance records.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('recorded_at', now()->toDateString());
    }

    /**
     * Scope to get attendance by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get clock-in records.
     */
    public function scopeClockIns($query)
    {
        return $query->where('type', self::TYPE_CLOCK_IN);
    }

    /**
     * Scope to get clock-out records.
     */
    public function scopeClockOuts($query)
    {
        return $query->where('type', self::TYPE_CLOCK_OUT);
    }

    /**
     * Check if this is a clock-in record.
     */
    public function isClockIn(): bool
    {
        return $this->type === self::TYPE_CLOCK_IN;
    }

    /**
     * Check if this is a clock-out record.
     */
    public function isClockOut(): bool
    {
        return $this->type === self::TYPE_CLOCK_OUT;
    }
}

