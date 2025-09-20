<?php

namespace App\Services;

use App\Models\User;
use App\Models\Program;
use App\Models\Enrollment;
use App\Repositories\UserRepository;
use Illuminate\Database\Eloquent\Collection;

class EnrollmentService
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    /**
     * Enroll student in programs based on their preferred language and level.
     */
    public function enrollStudentInPrograms(User $student, string $preferredLanguage, int $levelId): Collection
    {
        // Get matching programs
        $programs = $this->userRepository->getMatchingPrograms($preferredLanguage, $levelId);

        if ($programs->isEmpty()) {
            return new Collection();
        }

        // Assign student to programs
        $this->userRepository->assignToPrograms($student, $programs);

        return $programs;
    }

    /**
     * Get student's enrollments with access status.
     */
    public function getStudentEnrollments(User $student): Collection
    {
        return $student->enrollments()->with(['program.language', 'program.level'])->get();
    }

    /**
     * Check if student has access to a specific program.
     */
    public function hasAccessToProgram(User $student, Program $program): bool
    {
        $enrollment = $student->enrollments()
            ->where('program_id', $program->id)
            ->first();

        return $enrollment && $enrollment->access_granted_at !== null;
    }

    /**
     * Get all pending enrollments (students waiting for access approval).
     */
    public function getPendingEnrollments(array $filters = []): Collection
    {
        if (empty($filters)) {
            return Enrollment::whereNull('access_granted_at')
                ->with(['user', 'program.language', 'program.level'])
                ->orderBy('assigned_at', 'desc')
                ->get();
        }

        return $this->getAllPendingEnrollments($filters);
    }

    /**
     * Get enrollments for a specific program.
     */
    public function getProgramEnrollments(Program $program): Collection
    {
        return $program->enrollments()
            ->with(['user'])
            ->orderBy('assigned_at', 'desc')
            ->get();
    }

    /**
     * Get approved enrollments for a specific program.
     */
    public function getApprovedProgramEnrollments(Program $program): Collection
    {
        return $program->enrollments()
            ->whereNotNull('access_granted_at')
            ->with(['user'])
            ->orderBy('access_granted_at', 'desc')
            ->get();
    }

    /**
     * Get system-wide enrollment statistics.
     */
    public function getSystemEnrollmentStatistics(): array
    {
        $totalEnrollments = Enrollment::count();
        $approvedEnrollments = Enrollment::whereNotNull('access_granted_at')->count();
        $pendingEnrollments = Enrollment::whereNull('access_granted_at')->count();

        // Get statistics by language
        $languageStats = Enrollment::join('programs', 'enrollments.program_id', '=', 'programs.id')
            ->join('languages', 'programs.language_id', '=', 'languages.id')
            ->selectRaw('
                languages.id,
                languages.name,
                languages.code,
                COUNT(*) as total_enrollments,
                SUM(CASE WHEN enrollments.access_granted_at IS NOT NULL THEN 1 ELSE 0 END) as approved_enrollments,
                SUM(CASE WHEN enrollments.access_granted_at IS NULL THEN 1 ELSE 0 END) as pending_enrollments
            ')
            ->groupBy('languages.id', 'languages.name', 'languages.code')
            ->get();

        // Get recent enrollment activity
        $recentEnrollments = Enrollment::with(['user', 'program.language', 'program.level'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($enrollment) {
                return [
                    'id' => $enrollment->id,
                    'student_name' => $enrollment->user->name,
                    'program_title' => $enrollment->program->title,
                    'language' => $enrollment->program->language->name,
                    'level' => $enrollment->program->level->name,
                    'assigned_at' => $enrollment->assigned_at,
                    'access_granted_at' => $enrollment->access_granted_at,
                    'status' => $enrollment->access_granted_at ? 'approved' : 'pending',
                ];
            });

        return [
            'summary' => [
                'total_enrollments' => $totalEnrollments,
                'approved_enrollments' => $approvedEnrollments,
                'pending_enrollments' => $pendingEnrollments,
                'approval_rate' => $totalEnrollments > 0 
                    ? round(($approvedEnrollments / $totalEnrollments) * 100, 2) 
                    : 0,
            ],
            'by_language' => $languageStats->map(function ($stat) {
                return [
                    'language' => [
                        'id' => $stat->id,
                        'name' => $stat->name,
                        'code' => $stat->code,
                    ],
                    'total_enrollments' => $stat->total_enrollments,
                    'approved_enrollments' => $stat->approved_enrollments,
                    'pending_enrollments' => $stat->pending_enrollments,
                    'approval_rate' => $stat->total_enrollments > 0 
                        ? round(($stat->approved_enrollments / $stat->total_enrollments) * 100, 2) 
                        : 0,
                ];
            }),
            'recent_activity' => $recentEnrollments,
        ];
    }

    /**
     * Get all pending enrollments with optional filtering.
     */
    public function getAllPendingEnrollments(array $filters = []): Collection
    {
        $query = Enrollment::whereNull('access_granted_at')
            ->with(['user', 'program.language', 'program.level']);

        // Apply filters
        if (isset($filters['language_id'])) {
            $query->whereHas('program', function ($q) use ($filters) {
                $q->where('language_id', $filters['language_id']);
            });
        }

        if (isset($filters['level_id'])) {
            $query->whereHas('program', function ($q) use ($filters) {
                $q->where('level_id', $filters['level_id']);
            });
        }

        if (isset($filters['program_id'])) {
            $query->where('program_id', $filters['program_id']);
        }

        if (isset($filters['days_pending'])) {
            $daysAgo = now()->subDays($filters['days_pending']);
            $query->where('assigned_at', '<=', $daysAgo);
        }

        return $query->orderBy('assigned_at', 'desc')->get();
    }

    /**
     * Enroll a student in a specific program.
     */
    public function enrollStudentInProgram(User $student, Program $program): Enrollment
    {
        return Enrollment::firstOrCreate(
            [
                'user_id' => $student->id,
                'program_id' => $program->id,
            ],
            [
                'assigned_at' => now(),
            ]
        );
    }

    /**
     * Check if a student is enrolled in a specific program.
     */
    public function isStudentEnrolled(User $student, Program $program): bool
    {
        return Enrollment::where('user_id', $student->id)
            ->where('program_id', $program->id)
            ->exists();
    }

    /**
     * Get enrollment statistics for a specific program.
     */
    public function getEnrollmentStats(Program $program): array
    {
        $totalEnrollments = $program->enrollments()->count();
        $approvedEnrollments = $program->enrollments()->whereNotNull('access_granted_at')->count();
        $pendingEnrollments = $program->enrollments()->whereNull('access_granted_at')->count();

        return [
            'total_enrollments' => $totalEnrollments,
            'approved_enrollments' => $approvedEnrollments,
            'pending_enrollments' => $pendingEnrollments,
        ];
    }
}