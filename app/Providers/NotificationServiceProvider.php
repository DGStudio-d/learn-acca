<?php

namespace App\Providers;

use App\Notifications\Channels\EmailChannel;
use App\Notifications\Channels\WhatsAppChannel;
use App\Services\NotificationLogger;
use Illuminate\Notifications\ChannelManager;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\ServiceProvider;

class NotificationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register the notification logger as a singleton
        $this->app->singleton(NotificationLogger::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register custom notification channels
        Notification::resolved(function (ChannelManager $service) {
            $service->extend('custom_email', function ($app) {
                return $app->make(EmailChannel::class);
            });

            $service->extend('custom_whatsapp', function ($app) {
                return $app->make(WhatsAppChannel::class);
            });
        });
    }
}