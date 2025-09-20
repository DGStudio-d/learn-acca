<?php

namespace App\Models;

use App\Traits\CacheInvalidation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Language extends Model
{
    use HasFactory, CacheInvalidation;

    protected $fillable = [
        'code',
        'name',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    /**
     * Get the levels for this language.
     */
    public function levels(): HasMany
    {
        return $this->hasMany(Level::class)->orderBy('order');
    }

    /**
     * Get the programs for this language.
     */
    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }

    /**
     * Get the teacher language relationships.
     */
    public function teacherLanguages(): HasMany
    {
        return $this->hasMany(TeacherLanguage::class);
    }

    /**
     * Get the teachers assigned to this language.
     */
    public function teachers()
    {
        return $this->belongsToMany(User::class, 'teacher_languages')
                    ->where('role', 'teacher');
    }

    /**
     * Scope to get only active languages.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}