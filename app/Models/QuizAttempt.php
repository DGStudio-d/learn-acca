<?php

namespace App\Models;

use App\Traits\CacheInvalidation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAttempt extends Model
{
    use HasFactory, CacheInvalidation;

    protected $fillable = [
        'quiz_id',
        'student_id',
        'answers',
        'score',
        'passed',
        'submitted_at',
    ];

    protected $casts = [
        'answers' => 'array',
        'score' => 'integer',
        'passed' => 'boolean',
        'submitted_at' => 'datetime',
    ];

    /**
     * Get the quiz that this attempt belongs to.
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * Get the student who made this attempt.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Scope to get only passed attempts.
     */
    public function scopePassed($query)
    {
        return $query->where('passed', true);
    }

    /**
     * Scope to get only failed attempts.
     */
    public function scopeFailed($query)
    {
        return $query->where('passed', false);
    }

    /**
     * Scope to get guest attempts (no student_id).
     */
    public function scopeGuest($query)
    {
        return $query->whereNull('student_id');
    }

    /**
     * Scope to get student attempts (has student_id).
     */
    public function scopeStudent($query)
    {
        return $query->whereNotNull('student_id');
    }

    /**
     * Check if this is a guest attempt.
     */
    public function isGuestAttempt(): bool
    {
        return is_null($this->student_id);
    }

    /**
     * Get the percentage score.
     */
    public function getPercentageScoreAttribute(): float
    {
        // Score is already stored as percentage
        return (float) $this->score;
    }
}