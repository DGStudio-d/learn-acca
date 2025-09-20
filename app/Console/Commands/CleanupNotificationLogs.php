<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class CleanupNotificationLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:cleanup {--days=90 : Number of days to keep notification logs}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old notification logs';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService): int
    {
        $days = (int) $this->option('days');
        
        $this->info("Cleaning up notification logs older than {$days} days...");
        
        $deletedCount = $notificationService->cleanupOldNotifications($days);
        
        $this->info("Deleted {$deletedCount} old notification logs.");
        
        return self::SUCCESS;
    }
}