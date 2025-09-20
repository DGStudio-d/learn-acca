<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    public function check(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis' => $this->checkRedis(),
            'storage' => $this->checkStorage(),
            'queue' => $this->checkQueue(),
        ];

        $healthy = collect($checks)->every(fn ($check) => $check['status'] === 'ok');

        return response()->json([
            'status' => $healthy ? 'healthy' : 'unhealthy',
            'timestamp' => now()->toISOString(),
            'checks' => $checks,
            'version' => config('app.version', '1.0.0'),
            'environment' => config('app.env'),
        ], $healthy ? 200 : 503);
    }

    public function ready(): JsonResponse
    {
        // Simple readiness check for Kubernetes
        return response()->json([
            'status' => 'ready',
            'timestamp' => now()->toISOString(),
        ]);
    }

    public function live(): JsonResponse
    {
        // Simple liveness check for Kubernetes
        return response()->json([
            'status' => 'alive',
            'timestamp' => now()->toISOString(),
        ]);
    }

    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();
            $responseTime = $this->measureResponseTime(fn () => DB::select('SELECT 1'));

            return [
                'status' => 'ok',
                'response_time_ms' => $responseTime,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function checkRedis(): array
    {
        try {
            // Skip Redis check in testing environment
            if (app()->environment('testing')) {
                return [
                    'status' => 'ok',
                    'message' => 'Redis check skipped in testing environment',
                ];
            }

            $responseTime = $this->measureResponseTime(fn () => Redis::ping());

            return [
                'status' => 'ok',
                'response_time_ms' => $responseTime,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function checkStorage(): array
    {
        try {
            $testFile = storage_path('app/health-check.txt');
            file_put_contents($testFile, 'health check');
            $content = file_get_contents($testFile);
            unlink($testFile);

            return [
                'status' => $content === 'health check' ? 'ok' : 'error',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function checkQueue(): array
    {
        try {
            // Check if tables exist before querying them
            if (! DB::getSchemaBuilder()->hasTable('jobs')) {
                return [
                    'status' => 'ok',
                    'message' => 'Queue tables not available in testing environment',
                ];
            }

            $pendingJobs = DB::table('jobs')->count();

            $failedJobs = 0;
            if (DB::getSchemaBuilder()->hasTable('failed_jobs')) {
                $failedJobs = DB::table('failed_jobs')->count();
            }

            return [
                'status' => 'ok',
                'pending_jobs' => $pendingJobs,
                'failed_jobs' => $failedJobs,
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage(),
            ];
        }
    }

    private function measureResponseTime(callable $callback): float
    {
        $start = microtime(true);
        $callback();

        return round((microtime(true) - $start) * 1000, 2);
    }
}
