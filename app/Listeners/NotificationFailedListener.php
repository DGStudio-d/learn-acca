<?php

namespace App\Listeners;

use App\Jobs\RetryFailedNotificationJob;
use App\Models\NotificationLog;
use Illuminate\Notifications\Events\NotificationFailed;
use Illuminate\Support\Facades\Log;

class NotificationFailedListener
{
    /**
     * Handle the event.
     */
    public function handle(NotificationFailed $event): void
    {
        Log::error('Notification failed', [
            'notifiable_type' => get_class($event->notifiable),
            'notifiable_id' => $event->notifiable->getKey(),
            'notification_type' => get_class($event->notification),
            'channel' => $event->channel,
            'data' => $event->data,
        ]);

        // Find the corresponding notification log and mark it as failed
        if (method_exists($event->notification, 'getNotificationType')) {
            $notificationLog = NotificationLog::where('user_id', $event->notifiable->id)
                ->where('type', $event->notification->getNotificationType())
                ->where('channel', $this->mapChannelName($event->channel))
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($notificationLog) {
                $errorMessage = $event->data['error'] ?? 'Unknown error occurred';
                $notificationLog->markAsFailed($errorMessage);

                // Schedule a retry after 5 minutes for critical notifications
                if ($this->shouldRetry($event->notification)) {
                    RetryFailedNotificationJob::dispatch($notificationLog)
                        ->delay(now()->addMinutes(5));
                    
                    Log::info('Scheduled retry for failed notification', [
                        'log_id' => $notificationLog->id,
                        'retry_delay' => '5 minutes',
                    ]);
                }
            }
        }
    }

    /**
     * Map channel class name to database channel name.
     */
    protected function mapChannelName(string $channel): string
    {
        return match ($channel) {
            'App\Notifications\Channels\EmailChannel' => 'email',
            'App\Notifications\Channels\WhatsAppChannel' => 'whatsapp',
            'mail' => 'email',
            default => strtolower(class_basename($channel)),
        };
    }

    /**
     * Determine if the notification should be retried.
     */
    protected function shouldRetry($notification): bool
    {
        // Retry critical notifications like enrollment and access approval
        $retryableTypes = [
            'enrollment',
            'access_approval',
            'meeting_created',
            'meeting_reminder',
        ];

        if (method_exists($notification, 'getNotificationType')) {
            return in_array($notification->getNotificationType(), $retryableTypes);
        }

        return false;
    }
}