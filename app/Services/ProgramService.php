<?php

namespace App\Services;

use App\Models\Program;
use Illuminate\Database\Eloquent\Collection;

class ProgramService
{
    /**
     * Get all programs with their statistics.
     */
    public function getAllProgramsWithStatistics(array $filters = []): \Illuminate\Support\Collection
    {
        $query = Program::with(['language', 'level', 'enrollments.user']);

        // Apply filters
        if (isset($filters['language_id'])) {
            $query->where('language_id', $filters['language_id']);
        }

        if (isset($filters['level_id'])) {
            $query->where('level_id', $filters['level_id']);
        }

        if (isset($filters['active'])) {
            $query->where('active', $filters['active']);
        }

        return $query->get()->map(function ($program) {
            $enrollments = $program->enrollments;
            $approvedEnrollments = $enrollments->whereNotNull('access_granted_at');

            return [
                'id' => $program->id,
                'title' => $program->title,
                'description' => $program->description,
                'active' => $program->active,
                'language' => [
                    'id' => $program->language->id,
                    'name' => $program->language->name,
                    'code' => $program->language->code,
                ],
                'level' => [
                    'id' => $program->level->id,
                    'name' => $program->level->name,
                    'order' => $program->level->order,
                ],
                'statistics' => [
                    'total_enrollments' => $enrollments->count(),
                    'approved_enrollments' => $approvedEnrollments->count(),
                    'pending_enrollments' => $enrollments->whereNull('access_granted_at')->count(),
                    'approval_rate' => $enrollments->count() > 0 
                        ? round(($approvedEnrollments->count() / $enrollments->count()) * 100, 2) 
                        : 0,
                ],
                'created_at' => $program->created_at,
                'updated_at' => $program->updated_at,
            ];
        });
    }

    /**
     * Get detailed information about a specific program.
     */
    public function getProgramDetails(Program $program): array
    {
        $program->load(['language', 'level', 'enrollments.user', 'quizzes', 'meetings']);

        $enrollments = $program->enrollments;
        $approvedEnrollments = $enrollments->whereNotNull('access_granted_at');

        return [
            'program' => [
                'id' => $program->id,
                'title' => $program->title,
                'description' => $program->description,
                'active' => $program->active,
                'created_at' => $program->created_at,
                'updated_at' => $program->updated_at,
            ],
            'language' => [
                'id' => $program->language->id,
                'name' => $program->language->name,
                'code' => $program->language->code,
                'active' => $program->language->active,
            ],
            'level' => [
                'id' => $program->level->id,
                'name' => $program->level->name,
                'order' => $program->level->order,
            ],
            'statistics' => [
                'total_enrollments' => $enrollments->count(),
                'approved_enrollments' => $approvedEnrollments->count(),
                'pending_enrollments' => $enrollments->whereNull('access_granted_at')->count(),
                'total_quizzes' => $program->quizzes->count(),
                'total_meetings' => $program->meetings->count(),
                'approval_rate' => $enrollments->count() > 0 
                    ? round(($approvedEnrollments->count() / $enrollments->count()) * 100, 2) 
                    : 0,
            ],
            'recent_enrollments' => $enrollments->sortByDesc('created_at')->take(5)->map(function ($enrollment) {
                return [
                    'id' => $enrollment->id,
                    'student' => [
                        'id' => $enrollment->user->id,
                        'name' => $enrollment->user->name,
                        'email' => $enrollment->user->email,
                    ],
                    'assigned_at' => $enrollment->assigned_at,
                    'access_granted_at' => $enrollment->access_granted_at,
                    'has_access' => !is_null($enrollment->access_granted_at),
                ];
            })->values(),
        ];
    }

    /**
     * Update a program.
     */
    public function updateProgram(Program $program, array $data): Program
    {
        $program->update($data);
        return $program->fresh();
    }

    /**
     * Get all students enrolled in a program.
     */
    public function getProgramStudents(Program $program): Collection
    {
        return $program->enrollments()
            ->with('user')
            ->orderBy('assigned_at', 'desc')
            ->get()
            ->map(function ($enrollment) {
                return [
                    'enrollment_id' => $enrollment->id,
                    'student' => [
                        'id' => $enrollment->user->id,
                        'name' => $enrollment->user->name,
                        'email' => $enrollment->user->email,
                        'phone' => $enrollment->user->phone,
                    ],
                    'assigned_at' => $enrollment->assigned_at,
                    'access_granted_at' => $enrollment->access_granted_at,
                    'has_access' => !is_null($enrollment->access_granted_at),
                    'approved_by' => $enrollment->approved_by,
                ];
            });
    }

    /**
     * Get program statistics.
     */
    public function getProgramStatistics(Program $program): array
    {
        $program->load(['enrollments.user', 'quizzes.quizAttempts', 'meetings']);

        $enrollments = $program->enrollments;
        $approvedEnrollments = $enrollments->whereNotNull('access_granted_at');
        $quizzes = $program->quizzes;
        $meetings = $program->meetings;

        // Calculate quiz statistics
        $totalQuizAttempts = $quizzes->sum(function ($quiz) {
            return $quiz->quizAttempts->count();
        });

        $passedQuizAttempts = $quizzes->sum(function ($quiz) {
            return $quiz->quizAttempts->where('passed', true)->count();
        });

        return [
            'program' => [
                'id' => $program->id,
                'title' => $program->title,
                'language' => $program->language->name,
                'level' => $program->level->name,
            ],
            'enrollment_statistics' => [
                'total_enrollments' => $enrollments->count(),
                'approved_enrollments' => $approvedEnrollments->count(),
                'pending_enrollments' => $enrollments->whereNull('access_granted_at')->count(),
                'approval_rate' => $enrollments->count() > 0 
                    ? round(($approvedEnrollments->count() / $enrollments->count()) * 100, 2) 
                    : 0,
            ],
            'content_statistics' => [
                'total_quizzes' => $quizzes->count(),
                'total_meetings' => $meetings->count(),
                'upcoming_meetings' => $meetings->where('start_time', '>', now())->count(),
            ],
            'activity_statistics' => [
                'total_quiz_attempts' => $totalQuizAttempts,
                'passed_quiz_attempts' => $passedQuizAttempts,
                'quiz_pass_rate' => $totalQuizAttempts > 0 
                    ? round(($passedQuizAttempts / $totalQuizAttempts) * 100, 2) 
                    : 0,
            ],
        ];
    }

    /**
     * Get pending enrollments for a program.
     */
    public function getProgramPendingEnrollments(Program $program): Collection
    {
        return $program->enrollments()
            ->whereNull('access_granted_at')
            ->with('user')
            ->orderBy('assigned_at', 'desc')
            ->get()
            ->map(function ($enrollment) {
                return [
                    'id' => $enrollment->id,
                    'student' => [
                        'id' => $enrollment->user->id,
                        'name' => $enrollment->user->name,
                        'email' => $enrollment->user->email,
                        'phone' => $enrollment->user->phone,
                    ],
                    'assigned_at' => $enrollment->assigned_at,
                    'days_pending' => $enrollment->assigned_at->diffInDays(now()),
                ];
            });
    }

    /**
     * Get approved enrollments for a program.
     */
    public function getProgramApprovedEnrollments(Program $program): Collection
    {
        return $program->enrollments()
            ->whereNotNull('access_granted_at')
            ->with(['user', 'approvedBy'])
            ->orderBy('access_granted_at', 'desc')
            ->get()
            ->map(function ($enrollment) {
                return [
                    'id' => $enrollment->id,
                    'student' => [
                        'id' => $enrollment->user->id,
                        'name' => $enrollment->user->name,
                        'email' => $enrollment->user->email,
                        'phone' => $enrollment->user->phone,
                    ],
                    'assigned_at' => $enrollment->assigned_at,
                    'access_granted_at' => $enrollment->access_granted_at,
                    'approved_by' => $enrollment->approvedBy ? [
                        'id' => $enrollment->approvedBy->id,
                        'name' => $enrollment->approvedBy->name,
                    ] : null,
                ];
            });
    }

    /**
     * Check if a program can be safely deleted.
     */
    public function canDeleteProgram(Program $program): array
    {
        $hasEnrollments = $program->enrollments()->exists();
        $hasQuizzes = $program->quizzes()->exists();
        $hasMeetings = $program->meetings()->exists();

        return [
            'can_delete' => !($hasEnrollments || $hasQuizzes || $hasMeetings),
            'reasons' => [
                'has_enrollments' => $hasEnrollments,
                'has_quizzes' => $hasQuizzes,
                'has_meetings' => $hasMeetings,
            ],
        ];
    }
}