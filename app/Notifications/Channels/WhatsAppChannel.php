<?php

namespace App\Notifications\Channels;

use App\Models\NotificationLog;
use App\Services\NotificationLogger;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppChannel
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
        if (!$notifiable->phone || !$notifiable->notify_whatsapp) {
            return;
        }

        $message = $notification->toWhatsApp($notifiable);
        
        // Log the notification attempt
        $logEntry = $this->logger->logNotification(
            $notifiable,
            $notification->getNotificationType(),
            'whatsapp',
            [
                'phone' => $notifiable->phone,
                'message' => $message,
            ],
            $notifiable->phone,
            'WhatsApp Message'
        );

        try {
            // Send WhatsApp message
            $this->sendWhatsAppMessage($notifiable->phone, $message);
            
            // Mark as sent
            $this->logger->markAsSent($logEntry);
            
            Log::info('WhatsApp notification sent successfully', [
                'user_id' => $notifiable->id,
                'phone' => $notifiable->phone,
                'type' => $notification->getNotificationType(),
            ]);
            
        } catch (\Exception $e) {
            // Mark as failed
            $this->logger->markAsFailed($logEntry, $e->getMessage());
            
            Log::error('WhatsApp notification failed', [
                'user_id' => $notifiable->id,
                'phone' => $notifiable->phone,
                'type' => $notification->getNotificationType(),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send WhatsApp message via API.
     */
    protected function sendWhatsAppMessage(string $phone, string $message): void
    {
        $whatsappApiUrl = config('services.whatsapp.api_url');
        $whatsappToken = config('services.whatsapp.token');

        if (!$whatsappApiUrl || !$whatsappToken) {
            throw new \Exception('WhatsApp API configuration is missing');
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $whatsappToken,
            'Content-Type' => 'application/json',
        ])->post($whatsappApiUrl, [
            'messaging_product' => 'whatsapp',
            'to' => $this->formatPhoneNumber($phone),
            'type' => 'text',
            'text' => [
                'body' => $message,
            ],
        ]);

        if (!$response->successful()) {
            throw new \Exception('WhatsApp API request failed: ' . $response->body());
        }
    }

    /**
     * Format phone number for WhatsApp API.
     */
    protected function formatPhoneNumber(string $phone): string
    {
        // Remove any non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Add country code if not present (assuming international format)
        if (!str_starts_with($phone, '966')) { // Saudi Arabia country code
            $phone = '966' . ltrim($phone, '0');
        }
        
        return $phone;
    }
}