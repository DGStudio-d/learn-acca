<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\NotificationLogger;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public User $user;
    public Notification $notification;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(User $user, Notification $notification)
    {
        $this->user = $user;
        $this->notification = $notification;
        
        // Set the queue for notifications
        $this->onQueue('notifications');
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $logger = app(NotificationLogger::class);
        
        try {
            // Check if user has notification preferences enabled
            $shouldSend = false;
            
            if ($this->user->notify_email) {
                $shouldSend = true;
            }
            
            if ($this->user->notify_whatsapp && $this->user->phone) {
                $shouldSend = true;
            }
            
            if (!$shouldSend) {
                $logger->logNotificationAttempt($this->user, $this->notification, 'skipped', 'No notification preferences enabled');
                return;
            }
            
            // Send the notification
            $this->user->notify($this->notification);
            
            $logger->logNotificationAttempt($this->user, $this->notification, 'sent', 'Notification sent successfully');
            
            Log::info('Notification job completed successfully', [
                'user_id' => $this->user->id,
                'notification_type' => get_class($this->notification),
            ]);
            
        } catch (\Exception $e) {
            $logger->logNotificationAttempt($this->user, $this->notification, 'failed', $e->getMessage());
            
            Log::error('Notification job failed', [
                'user_id' => $this->user->id,
                'notification_type' => get_class($this->notification),
                'error' => $e->getMessage(),
                'attempt' => $this->attempts(),
            ]);
            
            // Re-throw the exception to trigger retry logic
            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Notification job failed permanently', [
            'user_id' => $this->user->id,
            'notification_type' => get_class($this->notification),
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts(),
        ]);
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [30, 60, 120]; // Wait 30s, then 60s, then 120s between retries
    }
}