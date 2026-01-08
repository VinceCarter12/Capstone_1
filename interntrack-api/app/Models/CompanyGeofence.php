<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CompanyGeofence extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'company_geofences';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'company_id',
        'latitude',
        'longitude',   
        'radius_meters',
        'name',
        'is_active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'radius_meters' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Earth's radius in meters (used for Haversine formula).
     */
    private const EARTH_RADIUS_METERS = 6371000;

    /**
     * Get the company that owns this geofence.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get attendances recorded at this geofence.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'geofence_id');
    }

    /**
     * Calculate the distance in meters from a given point to this geofence center.
     * Uses the Haversine formula for accurate great-circle distance.
     *
     * @param float $latitude User's latitude
     * @param float $longitude User's longitude
     * @return float Distance in meters
     */
    public function calculateDistance(float $latitude, float $longitude): float
    {
        $lat1 = deg2rad($this->latitude);
        $lat2 = deg2rad($latitude);
        $deltaLat = deg2rad($latitude - $this->latitude);
        $deltaLng = deg2rad($longitude - $this->longitude);

        $a = sin($deltaLat / 2) * sin($deltaLat / 2) +
             cos($lat1) * cos($lat2) *
             sin($deltaLng / 2) * sin($deltaLng / 2);

        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS_METERS * $c;
    }

    /**
     * Check if a given point is within this geofence's radius.
     *
     * @param float $latitude User's latitude
     * @param float $longitude User's longitude
     * @return bool True if within radius, false otherwise
     */
    public function isWithinRadius(float $latitude, float $longitude): bool
    {
        return $this->calculateDistance($latitude, $longitude) <= $this->radius_meters;
    }

    /**
     * Scope to get only active geofences.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}

