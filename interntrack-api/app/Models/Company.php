<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'address',
        'phone',
        'email',
        'website',
        'description',
        'job_roles',
        'requirements',
        'is_partnered',
        'is_verified',
        'submitted_by_user_id',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'job_roles' => 'array',
        'requirements' => 'array',
        'is_partnered' => 'boolean',
        'is_verified' => 'boolean',
    ];

    /**
     * Get the geofences for the company.
     * A company can have multiple geofence locations (e.g., main office, branches).
     */
    public function geofences(): HasMany
    {
        return $this->hasMany(CompanyGeofence::class);
    }

    /**
     * Get only active geofences for the company.
     */
    public function activeGeofences(): HasMany
    {
        return $this->hasMany(CompanyGeofence::class)->where('is_active', true);
    }

    /**
     * Get the users (students/interns) assigned to this company.
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Get only student users assigned to this company.
     */
    public function students(): HasMany
    {
        return $this->hasMany(User::class)->where('role', 'student');
    }

    /**
     * Get the user who submitted this company (if from placement request).
     */
    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    /**
     * Scope for partnered companies.
     */
    public function scopePartnered($query)
    {
        return $query->where('is_partnered', true);
    }

    /**
     * Scope for verified companies.
     */
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    /**
     * Get placement requests for this company.
     */
    public function placementRequests(): HasMany
    {
        return $this->hasMany(PlacementRequest::class, 'approved_company_id');
    }
}

