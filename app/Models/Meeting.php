<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Meeting extends Model
{
    use HasFactory;

    protected $fillable = [
        'program_id',
        'teacher_id',
        'title',
        'description',
        'meeting_link',
        'start_time',
        'timezone',
        'active',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'active' => 'boolean',
    ];

    /**
     * Get the program that owns this meeting.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Get the teacher who created this meeting.
     */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /**
     * Scope to get only active meetings.
     */
    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    /**
     * Scope to get upcoming meetings.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_time', '>', now());
    }

    /**
     * Scope to get past meetings.
     */
    public function scopePast($query)
    {
        return $query->where('start_time', '<', now());
    }

    /**
     * Scope to get meetings for today.
     */
    public function scopeToday($query)
    {
        return $query->whereDate('start_time', today());
    }

    /**
     * Check if the meeting is upcoming.
     */
    public function isUpcoming(): bool
    {
        return $this->start_time->isFuture();
    }

    /**
     * Check if the meeting is in progress (within 1 hour window).
     */
    public function isInProgress(): bool
    {
        $now = now();
        $meetingStart = $this->start_time;
        $meetingEnd = $meetingStart->copy()->addHour();

        return $now->between($meetingStart, $meetingEnd);
    }

    /**
     * Get the meeting status.
     */
    public function getStatusAttribute(): string
    {
        if ($this->isInProgress()) {
            return 'in_progress';
        }

        if ($this->isUpcoming()) {
            return 'upcoming';
        }

        return 'completed';
    }
}