<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Level extends Model
{
    use HasFactory;

    protected $fillable = [
        'language_id',
        'name',
        'order',
    ];

    protected $casts = [
        'order' => 'integer',
    ];

    /**
     * Get the language that owns this level.
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class);
    }

    /**
     * Get the programs for this level.
     */
    public function programs(): HasMany
    {
        return $this->hasMany(Program::class);
    }

    /**
     * Scope to order levels by their order field.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}