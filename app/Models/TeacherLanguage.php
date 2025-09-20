<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TeacherLanguage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'language_id',
        'assigned_at',
        'assigned_by',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
    ];

    /**
     * Get the teacher (user) that owns this assignment.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the user that owns this assignment (alias for teacher).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the language that owns this assignment.
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    /**
     * Get the admin who assigned this teacher to the language.
     */
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Scope to get assignments for a specific teacher.
     */
    public function scopeForTeacher($query, User $teacher)
    {
        return $query->where('user_id', $teacher->id);
    }

    /**
     * Scope to get assignments for a specific language.
     */
    public function scopeForLanguage($query, Language $language)
    {
        return $query->where('language_id', $language->id);
    }
}