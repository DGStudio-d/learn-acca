<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Traits\OptimizedQueries;
use App\Traits\CacheInvalidation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens, HasRoles, SoftDeletes, OptimizedQueries, CacheInvalidation;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'password',
        'role',
        'preferred_language',
        'preferred_locale',
        'notify_email',
        'notify_whatsapp',
        'image_path',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'notify_email' => 'boolean',
            'notify_whatsapp' => 'boolean',
        ];
    }

    /**
     * Get the enrollments for this user.
     */
    public function enrollments()
    {
        return $this->hasMany(Enrollment::class);
    }

    /**
     * Get the programs this user is enrolled in.
     */
    public function programs()
    {
        return $this->belongsToMany(Program::class, 'enrollments')
                    ->withPivot(['assigned_at', 'access_granted_at', 'approved_by'])
                    ->withTimestamps();
    }

    /**
     * Get the approved programs for this user.
     */
    public function approvedPrograms()
    {
        return $this->programs()->wherePivotNotNull('access_granted_at');
    }

    /**
     * Get the languages this teacher is assigned to.
     */
    public function teacherLanguages()
    {
        return $this->belongsToMany(Language::class, 'teacher_languages')
                    ->withPivot(['assigned_at', 'assigned_by'])
                    ->withTimestamps();
    }

    /**
     * Get the quiz attempts for this user.
     */
    public function quizAttempts()
    {
        return $this->hasMany(QuizAttempt::class, 'student_id');
    }

    /**
     * Get the notification logs for this user.
     */
    public function notificationLogs()
    {
        return $this->hasMany(NotificationLog::class);
    }

    /**
     * Check if user is a student.
     */
    public function isStudent(): bool
    {
        return $this->role === 'student';
    }

    /**
     * Check if user is a teacher.
     */
    public function isTeacher(): bool
    {
        return $this->role === 'teacher';
    }

    /**
     * Check if user is an admin.
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }
}
