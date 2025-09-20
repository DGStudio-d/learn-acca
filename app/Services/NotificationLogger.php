<?php

namespace App\Services;

use App\Models\NotificationLog;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class NotificationLogger
{
    /**
     * Log a notification attempt with status.
     */
    public function logNotificationAttempt(
        User $user,
        $notification,
        string $status,
        ?string $message = null
    ): void {
        Log::info('Notification attempt logged', [
            'user_id' => $user->id,
            'notification_type' => get_class($notification),
            'status' => $status,
            'message' => $message,
        ]);
    }

    /**
     * Log a notification attempt.
     */
    public function logNotification(
        User $user,
        string $type,
        string $channel,
        array $payload,
        ?string $recipient = null,
        ?string $subject = null
    ): NotificationLog {
        // Determine recipient based on channel if not provided
        if (!$recipient) {
            $recipient = $channel === 'email' ? $user->email : $user->phone;
        }
        
        // Extract subject from payload if not provided
        if (!$subject && isset($payload['subject'])) {
            $subject = $payload['subject'];
        }

        return NotificationLog::create([
            'user_id' => $user->id,
            'type' => $type,
            'channel' => $channel,
            'recipient' => $recipient,
            'subject' => $subject,
            'payload' => $payload,
            'status' => 'pending',
        ]);
    }

    /**
     * Mark a notification as sent.
     */
    public function markAsSent(NotificationLog $log): void
    {
        $log->markAsSent();
        
        Log::info('Notification marked as sent', [
            'log_id' => $log->id,
            'user_id' => $log->user_id,
            'type' => $log->type,
            'channel' => $log->channel,
        ]);
    }

    /**
     * Mark a notification as failed.
     */
    public function markAsFailed(NotificationLog $log, string $errorMessage): void
    {
        $log->markAsFailed($errorMessage);
        
        Log::error('Notification marked as failed', [
            'log_id' => $log->id,
            'user_id' => $log->user_id,
            'type' => $log->type,
            'channel' => $log->channel,
            'error' => $errorMessage,
        ]);
    }

    /**
     * Get notification history for a user.
     */
    public function getUserNotificationHistory(User $user, int $limit = 50): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return $user->notificationLogs()
            ->orderBy('created_at', 'desc')
            ->paginate($limit);
    }

    /**
     * Get notification statistics for a user.
     */
    public function getUserNotificationStats(User $user): array
    {
        $logs = $user->notificationLogs();

        return [
            'total' => $logs->count(),
            'sent' => $logs->clone()->successful()->count(),
            'failed' => $logs->clone()->failed()->count(),
            'pending' => $logs->clone()->pending()->count(),
            'by_channel' => [
                'email' => $logs->clone()->byChannel('email')->count(),
                'whatsapp' => $logs->clone()->byChannel('whatsapp')->count(),
            ],
            'by_type' => $logs->clone()
                ->selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray(),
        ];
    }

    /**
     * Get system-wide notification statistics.
     */
    public function getSystemNotificationStats(): array
    {
        $logs = NotificationLog::query();

        return [
            'total' => $logs->count(),
            'sent' => $logs->clone()->successful()->count(),
            'failed' => $logs->clone()->failed()->count(),
            'pending' => $logs->clone()->pending()->count(),
            'by_channel' => [
                'email' => $logs->clone()->byChannel('email')->count(),
                'whatsapp' => $logs->clone()->byChannel('whatsapp')->count(),
            ],
            'by_type' => $logs->clone()
                ->selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type')
                ->toArray(),
            'recent_failures' => $logs->clone()
                ->failed()
                ->with('user:id,name,email')
                ->orderBy('created_at', 'desc')
                ->limit(10)
                ->get(),
        ];
    }

    /**
     * Clean up old notification logs.
     */
    public function cleanupOldLogs(int $daysToKeep = 90): int
    {
        $cutoffDate = now()->subDays($daysToKeep);
        
        $deletedCount = NotificationLog::where('created_at', '<', $cutoffDate)->delete();
        
        Log::info('Cleaned up old notification logs', [
            'deleted_count' => $deletedCount,
            'cutoff_date' => $cutoffDate->toDateString(),
        ]);
        
        return $deletedCount;
    }
}