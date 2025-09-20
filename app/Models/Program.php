<?php

namespace App\Models;

use App\Traits\OptimizedQueries;
use App\Traits\CacheInvalidation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    use HasFactory, OptimizedQueries, CacheInvalidation;

    protected $fillable = [
        'language_id',
        'level_id',
        'title',
        'description',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Get the language that owns this program.
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    /**
     * Get the level that owns this program.
     */
    public function level(): BelongsTo
    {
        return $this->belongsTo(Level::class);
    }

    /**
     * Get the enrollments for this program.
     */
    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Get the enrolled students for this program.
     */
    public function students()
    {
        return $this->belongsToMany(User::class, 'enrollments')
                    ->withPivot(['assigned_at', 'access_granted_at', 'approved_by'])
                    ->withTimestamps();
    }

    /**
     * Get the quizzes for this program.
     */
    public function quizzes(): HasMany
    {
        return $this->hasMany(Quiz::class);
    }

    /**
     * Get the meetings for this program.
     */
    public function meetings(): HasMany
    {
        return $this->hasMany(Meeting::class);
    }

    /**
     * Scope to get only active programs.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Get enrollment count for this program.
     */
    public function getEnrollmentCountAttribute()
    {
        return $this->enrollments()->count();
    }

    /**
     * Get approved enrollment count for this program.
     */
    public function getApprovedEnrollmentCountAttribute()
    {
        return $this->enrollments()->whereNotNull('access_granted_at')->count();
    }
}