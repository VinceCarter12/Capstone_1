<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Announcement extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'announcements';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'title',
        'content',
        'created_by',
        'target_course',
        'target_section',
        'target_year',
        'is_active',
        'published_at',
        'expires_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_active' => 'boolean',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the admin who created this announcement.
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }

    /**
     * Get users who have read this announcement.
     */
    public function readers(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'announcement_reads')
            ->withPivot('read_at')
            ->withTimestamps();
    }

    /**
     * Scope for active announcements.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            });
    }

    /**
     * Scope for published announcements.
     */
    public function scopePublished($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('published_at')
                ->orWhere('published_at', '<=', now());
        });
    }

    /**
     * Check if announcement is visible to a specific user.
     */
    public function isVisibleTo(User $user): bool
    {
        // If no targeting, visible to all
        if (!$this->target_course && !$this->target_section && !$this->target_year) {
            return true;
        }

        // Check course match
        if ($this->target_course && $user->course !== $this->target_course) {
            return false;
        }

        // Check section match
        if ($this->target_section && $user->section !== $this->target_section) {
            return false;
        }

        // Check year match
        if ($this->target_year && $user->year !== $this->target_year) {
            return false;
        }

        return true;
    }

    /**
     * Mark as read by user.
     */
    public function markAsReadBy(User $user): void
    {
        if (!$this->readers()->where('user_id', $user->id)->exists()) {
            $this->readers()->attach($user->id, ['read_at' => now()]);
        }
    }
}
