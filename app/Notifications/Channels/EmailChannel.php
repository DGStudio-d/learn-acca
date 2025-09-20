<?php

namespace App\Notifications\Channels;

use App\Services\NotificationLogger;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailChannel
{
    protected NotificationLogger $logger;

    public function __construct(NotificationLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Send the given notification.
     */
    public function send($notifiable, Notification $notification): void
    {
        if (!$notifiable->email || !$notifiable->notify_email) {
            return;
        }

        $mailMessage = $notification->toMail($notifiable);
        
        // Log the notification attempt
        $logEntry = $this->logger->logNotification(
            $notifiable,
            $notification->getNotificationType(),
            'email',
            [
                'email' => $notifiable->email,
                'subject' => $mailMessage->subject,
                'greeting' => $mailMessage->greeting,
            ],
            $notifiable->email,
            $mailMessage->subject
        );

        try {
            // Send email
            Mail::to($notifiable->email)->send(
                new NotificationMail($mailMessage, $notifiable)
            );
            
            // Mark as sent
            $this->logger->markAsSent($logEntry);
            
            Log::info('Email notification sent successfully', [
                'user_id' => $notifiable->id,
                'email' => $notifiable->email,
                'type' => $notification->getNotificationType(),
                'subject' => $mailMessage->subject,
            ]);
            
        } catch (\Exception $e) {
            // Mark as failed
            $this->logger->markAsFailed($logEntry, $e->getMessage());
            
            Log::error('Email notification failed', [
                'user_id' => $notifiable->id,
                'email' => $notifiable->email,
                'type' => $notification->getNotificationType(),
                'error' => $e->getMessage(),
            ]);
        }
    }
}

/**
 * Mailable class for notification emails.
 */
class NotificationMail extends Mailable
{
    protected $mailMessage;
    protected $notifiable;

    public function __construct($mailMessage, $notifiable)
    {
        $this->mailMessage = $mailMessage;
        $this->notifiable = $notifiable;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        $message = $this->subject($this->mailMessage->subject)
                        ->from(config('mail.from.address'), config('mail.from.name'));

        // Set the view based on the mail message
        if ($this->mailMessage->view) {
            $message->view($this->mailMessage->view, $this->mailMessage->viewData);
        } else {
            // Use Laravel's default notification email template
            $message->markdown('notifications::email', [
                'greeting' => $this->mailMessage->greeting,
                'introLines' => $this->mailMessage->introLines,
                'actionText' => $this->mailMessage->actionText,
                'actionUrl' => $this->mailMessage->actionUrl,
                'outroLines' => $this->mailMessage->outroLines,
                'salutation' => $this->mailMessage->salutation,
                'level' => $this->mailMessage->level ?? 'info',
            ]);
        }

        // Add attachments if any
        foreach ($this->mailMessage->attachments as $attachment) {
            $message->attach($attachment['file'], $attachment['options']);
        }

        // Add raw attachments if any
        foreach ($this->mailMessage->rawAttachments as $attachment) {
            $message->attachData(
                $attachment['data'],
                $attachment['name'],
                $attachment['options']
            );
        }

        return $message;
    }
}