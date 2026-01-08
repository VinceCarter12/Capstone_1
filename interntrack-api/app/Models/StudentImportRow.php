<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentImportRow extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'import_id',
        'student_id',
        'fname',
        'lname',
        'email',
        'status',
        'error_message',
        'user_id',
    ];

    /**
     * Get the import batch this row belongs to.
     */
    public function import(): BelongsTo
    {
        return $this->belongsTo(StudentImport::class, 'import_id');
    }

    /**
     * Get the created user account (if any).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
