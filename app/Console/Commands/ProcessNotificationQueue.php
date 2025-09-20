<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class ProcessNotificationQueue extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:process {--timeout=60 : The timeout for the queue worker}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Process the notification queue';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $timeout = $this->option('timeout');
        
        $this->info('Starting notification queue worker...');
        
        // Start the queue worker for notifications
        Artisan::call('queue:work', [
            '--queue' => 'notifications',
            '--timeout' => $timeout,
            '--tries' => 3,
            '--backoff' => '30,60,120',
        ]);
        
        return self::SUCCESS;
    }
}