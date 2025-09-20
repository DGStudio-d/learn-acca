<?php

namespace App\Models;

use App\Traits\OptimizedQueries;
use App\Traits\CacheInvalidation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Quiz extends Model
{
    use HasFactory, OptimizedQueries, CacheInvalidation;

    protected $fillable = [
        'program_id',
        'teacher_id',
        'title',
        'description',
        'type',
        'file_path',
        'pass_score',
        'allow_guest_access',
        'active',
    ];

    protected $casts = [
        'pass_score' => 'integer',
        'allow_guest_access' => 'boolean',
        'active' => 'boolean',
    ];

    /**
     * Get the program that owns this quiz.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Get the questions for this quiz.
     */
    public function quizQuestions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class);
    }

    /**
     * Get the attempts for this quiz.
     */
    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    /**
     * Scope to get only active quizzes.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope to get quizzes that allow guest access.
     */
    public function scopeGuestAccessible($query)
    {
        return $query->where('allow_guest_access', true);
    }

    /**
     * Check if this is a file-based quiz.
     */
    public function isFileType(): bool
    {
        return $this->type === 'file';
    }

    /**
     * Check if this is an inline quiz.
     */
    public function isInlineType(): bool
    {
        return $this->type === 'inline';
    }

    /**
     * Get the teacher that owns this quiz.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Get the total number of questions.
     */
    public function getTotalQuestionsAttribute(): int
    {
        return $this->quizQuestions()->count();
    }
}