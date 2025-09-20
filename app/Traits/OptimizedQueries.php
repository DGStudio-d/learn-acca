<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;

trait OptimizedQueries
{
    /**
     * Scope to eager load common relationships for users.
     */
    public function scopeWithUserRelations(Builder $query): Builder
    {
        return $query->with([
            'enrollments.program.language',
            'enrollments.program.level',
            'teacherLanguages',
            'roles'
        ]);
    }

    /**
     * Scope to eager load program relationships.
     */
    public function scopeWithProgramRelations(Builder $query): Builder
    {
        return $query->with([
            'language',
            'level',
            'enrollments' => function ($query) {
                $query->select('id', 'user_id', 'program_id', 'access_granted_at');
            }
        ]);
    }

    /**
     * Scope to eager load quiz relationships.
     */
    public function scopeWithQuizRelations(Builder $query): Builder
    {
        return $query->with([
            'program.language',
            'program.level',
            'teacher:id,name,image_path',
            'quizQuestions:id,quiz_id,question,choices,correct_answer'
        ]);
    }

    /**
     * Scope to count relationships efficiently.
     */
    public function scopeWithCounts(Builder $query): Builder
    {
        return $query->withCount([
            'enrollments',
            'enrollments as approved_enrollments_count' => function ($query) {
                $query->whereNotNull('access_granted_at');
            }
        ]);
    }

    /**
     * Scope for optimized user dashboard queries.
     */
    public function scopeForDashboard(Builder $query): Builder
    {
        return $query->select([
            'id', 'name', 'email', 'role', 'preferred_language', 'image_path'
        ])->with([
            'approvedPrograms' => function ($query) {
                $query->select('programs.id', 'title', 'language_id', 'level_id')
                    ->with(['language:id,name,code', 'level:id,name,order']);
            },
            'quizAttempts' => function ($query) {
                $query->select('id', 'quiz_id', 'student_id', 'score', 'passed', 'submitted_at')
                    ->latest('submitted_at')
                    ->limit(5);
            }
        ]);
    }

    /**
     * Scope for optimized program listing with statistics.
     */
    public function scopeWithStatistics(Builder $query): Builder
    {
        return $query->withCount([
            'enrollments',
            'enrollments as approved_enrollments_count' => function ($query) {
                $query->whereNotNull('access_granted_at');
            },
            'quizzes',
            'meetings' => function ($query) {
                $query->where('start_time', '>=', now());
            }
        ]);
    }

    /**
     * Scope for teacher's assigned content.
     */
    public function scopeForTeacher(Builder $query, int $teacherId): Builder
    {
        return $query->whereHas('program.language.teacherLanguages', function ($query) use ($teacherId) {
            $query->where('user_id', $teacherId);
        })->with([
            'program' => function ($query) {
                $query->select('id', 'title', 'language_id', 'level_id')
                    ->with(['language:id,name,code', 'level:id,name']);
            }
        ]);
    }

    /**
     * Scope for guest accessible content.
     */
    public function scopeGuestAccessible(Builder $query): Builder
    {
        return $query->where('allow_guest_access', true)
            ->where('active', true)
            ->select(['id', 'title', 'description', 'type', 'pass_score']);
    }

    /**
     * Scope for recent activity queries.
     */
    public function scopeRecentActivity(Builder $query, int $days = 7): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days))
            ->orderBy('created_at', 'desc');
    }

    /**
     * Scope for paginated results with minimal data.
     */
    public function scopeMinimal(Builder $query): Builder
    {
        return $query->select(['id', 'name', 'created_at', 'updated_at']);
    }
}