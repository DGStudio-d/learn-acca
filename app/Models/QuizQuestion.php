<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_id',
        'question',
        'choices',
        'correct_answer',
        'order',
    ];

    protected $casts = [
        'choices' => 'array',
        'order' => 'integer',
    ];

    /**
     * Get the quiz that owns this question.
     */
    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }

    /**
     * Scope to order questions by their order field.
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }

    /**
     * Check if the given answer is correct.
     */
    public function isCorrectAnswer(string $answer): bool
    {
        return $this->correct_answer === $answer;
    }

    /**
     * Get the choices as an array with keys.
     */
    public function getFormattedChoicesAttribute(): array
    {
        if (!is_array($this->choices)) {
            return [];
        }

        $formatted = [];
        foreach ($this->choices as $index => $choice) {
            $key = chr(65 + $index); // A, B, C, D...
            $formatted[$key] = $choice;
        }

        return $formatted;
    }
}