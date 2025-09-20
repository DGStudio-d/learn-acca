<?php

namespace App\Services;

use App\Models\User;
use App\Models\Program;
use App\Models\Enrollment;
use Illuminate\Database\Eloquent\Collection;

class AccessControlService
{
    public function __construct(
        private NotificationService $notificationService
    ) {}

    /**
     * Grant access to a student for a specific program.
     */
    public function grantProgramAccess(User $student, Program $program, User $approvedBy): bool
    {
        $enrollment = Enrollment::where('user_id', $student->id)
            ->where('program_id', $program->id)
            ->first();

        if (!$enrollment) {
            return false;
        }

        $enrollment->update([
            'access_granted_at' => now(),
            'approved_by' => $approvedBy->id,
        ]);

        // Send notification to student about access approval
        $this->notificationService->sendAccessApprovalNotification($student, $program);

        return true;
    }

    /**
     * Grant access to multiple students for a program (bulk approval).
     */
    public function bulkGrantProgramAccess(Collection $studentIds, Program $program, User $approvedBy): int
    {
        $approvedCount = 0;

        foreach ($studentIds as $studentId) {
            $student = User::find($studentId);
            if ($student && $this->grantProgramAccess($student, $program, $approvedBy)) {
                $approvedCount++;
            }
        }

        return $approvedCount;
    }

    /**
     * Revoke access from a student for a specific program.
     */
    public function revokeProgramAccess(User $student, Program $program): bool
    {
        $enrollment = Enrollment::where('user_id', $student->id)
            ->where('program_id', $program->id)
            ->first();

        if (!$enrollment) {
            return false;
        }

        $enrollment->update([
            'access_granted_at' => null,
            'approved_by' => null,
        ]);

        return true;
    }

    /**
     * Check if a student can access program content.
     */
    public function canStudentAccess(User $student, Program $program): bool
    {
        $enrollment = Enrollment::where('user_id', $student->id)
            ->where('program_id', $program->id)
            ->first();

        return $enrollment && $enrollment->access_granted_at !== null;
    }

    /**
     * Get all students pending approval for a specific program.
     */
    public function getPendingStudentsForProgram(Program $program): Collection
    {
        return $program->enrollments()
            ->whereNull('access_granted_at')
            ->with(['user'])
            ->get()
            ->pluck('user');
    }

    /**
     * Get all approved students for a specific program.
     */
    public function getApprovedStudentsForProgram(Program $program): Collection
    {
        return $program->enrollments()
            ->whereNotNull('access_granted_at')
            ->with(['user'])
            ->get()
            ->pluck('user');
    }

    /**
     * Bulk approve enrollments by enrollment IDs.
     */
    public function bulkApproveEnrollmentsByIds(Collection $enrollmentIds, User $approvedBy): int
    {
        $approvedCount = 0;

        foreach ($enrollmentIds as $enrollmentId) {
            $enrollment = Enrollment::with(['user', 'program'])->find($enrollmentId);
            
            if ($enrollment && is_null($enrollment->access_granted_at)) {
                $enrollment->update([
                    'access_granted_at' => now(),
                    'approved_by' => $approvedBy->id,
                ]);

                // Send notification to student about access approval
                $this->notificationService->sendAccessApprovalNotification(
                    $enrollment->user, 
                    $enrollment->program
                );

                $approvedCount++;
            }
        }

        return $approvedCount;
    }
}