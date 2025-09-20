<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class RetryFailedNotifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:retry {--limit=10 : Maximum number of notifications to retry}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Retry failed notifications';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService): int
    {
        $limit = (int) $this->option('limit');
        
        $this->info("Retrying up to {$limit} failed notifications...");
        
        $retryCount = $notificationService->retryFailedNotifications($limit);
        
        $this->info("Dispatched retry jobs for {$retryCount} failed notifications.");
        
        return self::SUCCESS;
    }
}