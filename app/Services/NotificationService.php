<?php

namespace App\Services;

use App\Jobs\SendBulkNotificationJob;
use App\Jobs\SendNotificationJob;
use App\Models\Meeting;
use App\Models\NotificationLog;
use App\Models\Program;
use App\Models\User;
use App\Notifications\AccessApprovalNotification;
use App\Notifications\EnrollmentNotification;
use App\Notifications\MeetingNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    protected NotificationLogger $logger;

    public function __construct(NotificationLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Send enrollment notification to admins.
     */
    public function sendEnrollmentNotification(User $student, Program $program): void
    {
        $admins = $this->getAdmins();
        
        if ($admins->isEmpty()) {
            Log::warning('No admins found to notify about enrollment', [
                'student_id' => $student->id,
                'program_id' => $program->id,
            ]);
            return;
        }

        $notification = new EnrollmentNotification($student, $program);
        
        // Send to all admins
        SendBulkNotificationJob::dispatch($admins, $notification);
        
        Log::info('Enrollment notification dispatched to admins', [
            'student_id' => $student->id,
            'program_id' => $program->id,
            'admin_count' => $admins->count(),
        ]);
    }

    /**
     * Send access approval notification to student.
     */
    public function sendAccessApprovalNotification(User $student, Program $program): void
    {
        $notification = new AccessApprovalNotification($student, $program);
        
        SendNotificationJob::dispatch($student, $notification);
        
        Log::info('Access approval notification dispatched to student', [
            'student_id' => $student->id,
            'program_id' => $program->id,
        ]);
    }

    /**
     * Send meeting notification to enrolled students.
     */
    public function sendMeetingNotification(Meeting $meeting, string $action): void
    {
        $students = $this->getEnrolledStudents($meeting);
        
        if ($students->isEmpty()) {
            Log::info('No enrolled students to notify about meeting', [
                'meeting_id' => $meeting->id,
                'action' => $action,
            ]);
            return;
        }

        $notification = new MeetingNotification($meeting, $action);
        
        SendBulkNotificationJob::dispatch($students, $notification);
        
        Log::info('Meeting notification dispatched to students', [
            'meeting_id' => $meeting->id,
            'action' => $action,
            'student_count' => $students->count(),
        ]);
    }

    /**
     * Send notification to a specific user.
     */
    public function sendNotificationToUser(User $user, $notification): void
    {
        SendNotificationJob::dispatch($user, $notification);
        
        Log::info('Notification dispatched to user', [
            'user_id' => $user->id,
            'notification_type' => get_class($notification),
        ]);
    }

    /**
     * Send notification to multiple users.
     */
    public function sendNotificationToUsers(Collection $users, $notification): void
    {
        if ($users->isEmpty()) {
            Log::warning('No users provided for notification', [
                'notification_type' => get_class($notification),
            ]);
            return;
        }

        SendBulkNotificationJob::dispatch($users, $notification);
        
        Log::info('Bulk notification dispatched to users', [
            'user_count' => $users->count(),
            'notification_type' => get_class($notification),
        ]);
    }

    /**
     * Get all admin users.
     */
    protected function getAdmins(): Collection
    {
        return User::where('role', 'admin')->get();
    }

    /**
     * Get all enrolled students with approved access for a meeting's program.
     */
    protected function getEnrolledStudents(Meeting $meeting): Collection
    {
        return User::whereHas('enrollments', function ($query) use ($meeting) {
            $query->where('program_id', $meeting->program_id)
                  ->whereNotNull('access_granted_at');
        })
        ->where('role', 'student')
        ->where(function ($query) {
            // Only include students who have at least one notification method enabled
            $query->where('notify_email', true)
                  ->orWhere(function ($subQuery) {
                      $subQuery->where('notify_whatsapp', true)
                               ->whereNotNull('phone');
                  });
        })
        ->get();
    }

    /**
     * Get notification statistics.
     */
    public function getNotificationStats(): array
    {
        return $this->logger->getSystemNotificationStats();
    }

    /**
     * Get user notification history.
     */
    public function getUserNotificationHistory(User $user, int $limit = 50)
    {
        return $this->logger->getUserNotificationHistory($user, $limit);
    }

    /**
     * Get user notification statistics.
     */
    public function getUserNotificationStats(User $user): array
    {
        return $this->logger->getUserNotificationStats($user);
    }

    /**
     * Retry failed notifications.
     */
    public function retryFailedNotifications(int $limit = 10): int
    {
        $failedLogs = NotificationLog::failed()
            ->with('user')
            ->limit($limit)
            ->get();

        $retryCount = 0;
        
        foreach ($failedLogs as $log) {
            if ($log->user) {
                \App\Jobs\RetryFailedNotificationJob::dispatch($log);
                $retryCount++;
            }
        }

        Log::info('Dispatched retry jobs for failed notifications', [
            'retry_count' => $retryCount,
            'total_failed' => $failedLogs->count(),
        ]);

        return $retryCount;
    }

    /**
     * Clean up old notification logs.
     */
    public function cleanupOldNotifications(int $daysToKeep = 90): int
    {
        return $this->logger->cleanupOldLogs($daysToKeep);
    }
}