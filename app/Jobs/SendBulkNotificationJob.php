<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class SendBulkNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Collection $users;
    public Notification $notification;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 300; // 5 minutes for bulk operations

    /**
     * Create a new job instance.
     */
    public function __construct($users, Notification $notification)
    {
        $this->users = $users instanceof Collection ? $users : collect($users);
        $this->notification = $notification;
        
        // Set the queue for notifications
        $this->onQueue('notifications');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $successCount = 0;
        $failureCount = 0;

        foreach ($this->users as $user) {
            try {
                // Dispatch individual notification jobs for better error handling
                SendNotificationJob::dispatch($user, $this->notification);
                $successCount++;
                
            } catch (\Exception $e) {
                $failureCount++;
                Log::error('Failed to dispatch notification for user', [
                    'user_id' => $user->id,
                    'notification_type' => get_class($this->notification),
                    'error' => $e->getMessage(),
                ]);
            }
        }

        Log::info('Bulk notification job completed', [
            'notification_type' => get_class($this->notification),
            'total_users' => $this->users->count(),
            'successful_dispatches' => $successCount,
            'failed_dispatches' => $failureCount,
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Bulk notification job failed permanently', [
            'notification_type' => get_class($this->notification),
            'total_users' => $this->users->count(),
            'error' => $exception->getMessage(),
        ]);
    }
}