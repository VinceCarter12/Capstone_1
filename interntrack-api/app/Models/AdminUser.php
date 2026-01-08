<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminUser extends Authenticatable
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'admin_users';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'admin_name',
        'email',
        'password',
        'last_login_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'last_login_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get announcements created by this admin.
     */
    public function announcements(): HasMany
    {
        return $this->hasMany(Announcement::class, 'created_by');
    }

    /**
     * Get placement requests reviewed by this admin.
     */
    public function reviewedPlacements(): HasMany
    {
        return $this->hasMany(PlacementRequest::class, 'reviewed_by');
    }

    /**
     * Get archived records created by this admin.
     */
    public function archivedRecords(): HasMany
    {
        return $this->hasMany(ArchivedRecord::class, 'archived_by');
    }
}
