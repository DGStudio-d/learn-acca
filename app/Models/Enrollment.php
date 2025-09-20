<?php

namespace App\Models;

use App\Traits\CacheInvalidation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Enrollment extends Model
{
    use HasFactory, CacheInvalidation;

    protected $fillable = [
        'user_id',
        'program_id',
        'assigned_at',
        'access_granted_at',
        'approved_by',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'access_granted_at' => 'datetime',
    ];

    /**
     * Get the user that owns this enrollment.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the program that owns this enrollment.
     */
    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Get the admin who approved this enrollment.
     */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Scope to get approved enrollments.
     */
    public function scopeApproved($query)
    {
        return $query->whereNotNull('access_granted_at');
    }

    /**
     * Scope to get pending enrollments.
     */
    public function scopePending($query)
    {
        return $query->whereNull('access_granted_at');
    }

    /**
     * Check if the enrollment has been approved.
     */
    public function isApproved(): bool
    {
        return !is_null($this->access_granted_at);
    }

    /**
     * Grant access to this enrollment.
     */
    public function grantAccess(User $approver): void
    {
        $this->update([
            'access_granted_at' => now(),
            'approved_by' => $approver->id,
        ]);
    }
}