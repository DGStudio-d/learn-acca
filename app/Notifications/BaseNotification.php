<?php

namespace App\Notifications;

use App\Notifications\Channels\EmailChannel;
use App\Notifications\Channels\WhatsAppChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected array $data;
    protected string $notificationType;

    public function __construct(array $data, string $notificationType)
    {
        $this->data = $data;
        $this->notificationType = $notificationType;
        
        // Set the queue for notifications
        $this->onQueue('notifications');
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        $channels = [];

        if ($notifiable->notify_email) {
            $channels[] = 'custom_email';
        }

        if ($notifiable->notify_whatsapp && $notifiable->phone) {
            $channels[] = 'custom_whatsapp';
        }

        return $channels;
    }

    /**
     * Get the notification data.
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Get the notification type.
     */
    public function getNotificationType(): string
    {
        return $this->notificationType;
    }
}