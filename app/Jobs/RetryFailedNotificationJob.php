<?php

namespace App\Jobs;

use App\Models\NotificationLog;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RetryFailedNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected NotificationLog $notificationLog;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 2;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(NotificationLog $notificationLog)
    {
        $this->notificationLog = $notificationLog;
        
        // Set the queue for notifications
        $this->onQueue('notifications');
    }

    /**
     * Execute the job.
     */
    public function handle(NotificationService $notificationService): void
    {
        // Only retry failed notifications
        if (!$this->notificationLog->hasFailed()) {
            Log::info('Skipping retry for non-failed notification', [
                'log_id' => $this->notificationLog->id,
                'status' => $this->notificationLog->status,
            ]);
            return;
        }

        $user = $this->notificationLog->user;
        if (!$user) {
            Log::error('Cannot retry notification: user not found', [
                'log_id' => $this->notificationLog->id,
                'user_id' => $this->notificationLog->user_id,
            ]);
            return;
        }

        try {
            // Create a new notification based on the logged data
            $notification = $this->recreateNotification();
            
            if ($notification) {
                // Send the notification
                $notificationService->sendNotificationToUser($user, $notification);
                
                Log::info('Failed notification retried successfully', [
                    'log_id' => $this->notificationLog->id,
                    'user_id' => $user->id,
                    'type' => $this->notificationLog->type,
                ]);
            } else {
                Log::error('Could not recreate notification for retry', [
                    'log_id' => $this->notificationLog->id,
                    'type' => $this->notificationLog->type,
                ]);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to retry notification', [
                'log_id' => $this->notificationLog->id,
                'user_id' => $user->id,
                'type' => $this->notificationLog->type,
                'error' => $e->getMessage(),
            ]);
            
            throw $e;
        }
    }

    /**
     * Recreate the notification from logged data.
     */
    protected function recreateNotification()
    {
        $payload = $this->notificationLog->payload;
        $type = $this->notificationLog->type;

        // Map notification types to their classes
        $notificationClasses = [
            'enrollment' => \App\Notifications\EnrollmentNotification::class,
            'access_approval' => \App\Notifications\AccessApprovalNotification::class,
            'meeting_created' => \App\Notifications\MeetingNotification::class,
            'meeting_updated' => \App\Notifications\MeetingNotification::class,
            'meeting_cancelled' => \App\Notifications\MeetingNotification::class,
            'meeting_reminder' => \App\Notifications\MeetingNotification::class,
        ];

        if (!isset($notificationClasses[$type])) {
            Log::error('Unknown notification type for retry', [
                'type' => $type,
                'log_id' => $this->notificationLog->id,
            ]);
            return null;
        }

        try {
            // This is a simplified recreation - in a real scenario, you might need
            // to fetch the actual models from the database using the IDs in the payload
            $notificationClass = $notificationClasses[$type];
            
            // For now, we'll create a generic notification with the stored data
            // In practice, you'd want to reconstruct the actual notification objects
            return new class($payload, $type) extends \App\Notifications\BaseNotification {
                public function toMail($notifiable) {
                    return (new \Illuminate\Notifications\Messages\MailMessage)
                        ->subject('Retry: ' . ($this->data['subject'] ?? 'Notification'))
                        ->line('This is a retry of a previously failed notification.')
                        ->line(json_encode($this->data, JSON_PRETTY_PRINT));
                }
                
                public function toWhatsApp($notifiable): string {
                    return "Retry notification: " . json_encode($this->data);
                }
            };
            
        } catch (\Exception $e) {
            Log::error('Error recreating notification', [
                'type' => $type,
                'log_id' => $this->notificationLog->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Notification retry job failed permanently', [
            'log_id' => $this->notificationLog->id,
            'user_id' => $this->notificationLog->user_id,
            'type' => $this->notificationLog->type,
            'error' => $exception->getMessage(),
        ]);
    }
}