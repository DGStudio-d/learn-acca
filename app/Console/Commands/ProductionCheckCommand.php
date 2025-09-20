<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class ProductionCheckCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'production:check';

    /**
     * The console command description.
     */
    protected $description = 'Run production readiness checks';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Running production readiness checks...');
        
        $checks = [
            'Environment Configuration' => $this->checkEnvironment(),
            'Database Connection' => $this->checkDatabase(),
            'Redis Connection' => $this->checkRedis(),
            'Storage Configuration' => $this->checkStorage(),
            'Security Configuration' => $this->checkSecurity(),
            'Logging Configuration' => $this->checkLogging(),
            'Queue Configuration' => $this->checkQueue(),
        ];

        $allPassed = true;
        
        foreach ($checks as $checkName => $result) {
            if ($result['status']) {
                $this->info("✓ {$checkName}: {$result['message']}");
            } else {
                $this->error("✗ {$checkName}: {$result['message']}");
                $allPassed = false;
            }
        }

        if ($allPassed) {
            $this->info("\n🎉 All production checks passed!");
            return Command::SUCCESS;
        } else {
            $this->error("\n❌ Some production checks failed. Please review the issues above.");
            return Command::FAILURE;
        }
    }

    protected function checkEnvironment(): array
    {
        $required = ['APP_KEY', 'DB_DATABASE', 'REDIS_HOST'];
        $missing = [];

        foreach ($required as $env) {
            if (!env($env)) {
                $missing[] = $env;
            }
        }

        if (empty($missing)) {
            return ['status' => true, 'message' => 'All required environment variables are set'];
        }

        return ['status' => false, 'message' => 'Missing: ' . implode(', ', $missing)];
    }

    protected function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            return ['status' => true, 'message' => 'Database connection successful'];
        } catch (\Exception $e) {
            return ['status' => false, 'message' => 'Database connection failed: ' . $e->getMessage()];
        }
    }

    protected function checkRedis(): array
    {
        try {
            Redis::ping();
            return ['status' => true, 'message' => 'Redis connection successful'];
        } catch (\Exception $e) {
            return ['status' => false, 'message' => 'Redis connection failed: ' . $e->getMessage()];
        }
    }

    protected function checkStorage(): array
    {
        try {
            $disk = Storage::disk(config('filesystems.default'));
            $testFile = 'production-check-' . time() . '.txt';
            
            $disk->put($testFile, 'test');
            $content = $disk->get($testFile);
            $disk->delete($testFile);
            
            if ($content === 'test') {
                return ['status' => true, 'message' => 'Storage read/write successful'];
            }
            
            return ['status' => false, 'message' => 'Storage test failed'];
        } catch (\Exception $e) {
            return ['status' => false, 'message' => 'Storage test failed: ' . $e->getMessage()];
        }
    }

    protected function checkSecurity(): array
    {
        $issues = [];

        if (app()->environment('production') && config('app.debug')) {
            $issues[] = 'APP_DEBUG should be false in production';
        }

        if (!config('app.key')) {
            $issues[] = 'APP_KEY is not set';
        }

        if (empty($issues)) {
            return ['status' => true, 'message' => 'Security configuration looks good'];
        }

        return ['status' => false, 'message' => implode(', ', $issues)];
    }

    protected function checkLogging(): array
    {
        try {
            $logPath = storage_path('logs');
            
            if (!is_writable($logPath)) {
                return ['status' => false, 'message' => 'Log directory is not writable'];
            }

            return ['status' => true, 'message' => 'Logging configuration is ready'];
        } catch (\Exception $e) {
            return ['status' => false, 'message' => 'Logging check failed: ' . $e->getMessage()];
        }
    }

    protected function checkQueue(): array
    {
        try {
            $connection = config('queue.default');
            $config = config("queue.connections.{$connection}");
            
            if (!$config) {
                return ['status' => false, 'message' => 'Queue configuration not found'];
            }

            return ['status' => true, 'message' => "Queue configured with {$connection} driver"];
        } catch (\Exception $e) {
            return ['status' => false, 'message' => 'Queue check failed: ' . $e->getMessage()];
        }
    }
}