<?php

namespace App\Listeners;

use App\Models\NotificationLog;
use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Support\Facades\Log;

class NotificationSentListener
{
    /**
     * Handle the event.
     */
    public function handle(NotificationSent $event): void
    {
        Log::info('Notification sent successfully', [
            'notifiable_type' => get_class($event->notifiable),
            'notifiable_id' => $event->notifiable->getKey(),
            'notification_type' => get_class($event->notification),
            'channel' => $event->channel,
        ]);

        // Find the corresponding notification log and mark it as sent
        if (method_exists($event->notification, 'getNotificationType')) {
            $notificationLog = NotificationLog::where('user_id', $event->notifiable->id)
                ->where('type', $event->notification->getNotificationType())
                ->where('channel', $this->mapChannelName($event->channel))
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->first();

            if ($notificationLog) {
                $notificationLog->markAsSent();
                
                Log::debug('Notification log marked as sent', [
                    'log_id' => $notificationLog->id,
                    'user_id' => $event->notifiable->id,
                    'type' => $notificationLog->type,
                    'channel' => $notificationLog->channel,
                ]);
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
}