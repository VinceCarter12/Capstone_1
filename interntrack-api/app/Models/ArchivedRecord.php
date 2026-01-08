<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArchivedRecord extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'archived_records';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'academic_year',
        'semester',
        'course',
        'section',
        'year_level',
        'students_data',
        'total_students',
        'avg_hours_completed',
        'archived_by',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'students_data' => 'array',
        'total_students' => 'integer',
        'avg_hours_completed' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Semester constants
     */
    const SEMESTER_FIRST = '1st';
    const SEMESTER_SECOND = '2nd';
    const SEMESTER_SUMMER = 'summer';

    /**
     * Get the admin who archived this batch.
     */
    public function archivedBy(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'archived_by');
    }

    /**
     * Get display title for the archive.
     */
    public function getTitleAttribute(): string
    {
        $title = "{$this->academic_year} - {$this->semester} Sem - {$this->course}";
        
        if ($this->section) {
            $title .= " - Section {$this->section}";
        }
        
        return $title;
    }

    /**
     * Get student count from archived data.
     */
    public function getStudentCountAttribute(): int
    {
        return count($this->students_data ?? []);
    }

    /**
     * Search archived students by name or ID.
     */
    public function searchStudents(string $query): array
    {
        $query = strtolower($query);
        
        return array_filter($this->students_data ?? [], function ($student) use ($query) {
            return str_contains(strtolower($student['fname'] ?? ''), $query)
                || str_contains(strtolower($student['lname'] ?? ''), $query)
                || str_contains(strtolower($student['student_id'] ?? ''), $query)
                || str_contains(strtolower($student['email'] ?? ''), $query);
        });
    }
}
