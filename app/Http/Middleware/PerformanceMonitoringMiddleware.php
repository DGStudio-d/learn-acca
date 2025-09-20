<?php

namespace App\Http\Middleware;

use App\Services\PerformanceMonitoringService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PerformanceMonitoringMiddleware
{
    protected $performanceService;

    public function __construct(PerformanceMonitoringService $performanceService)
    {
        $this->performanceService = $performanceService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (!config('performance.monitoring.enabled')) {
            return $next($request);
        }

        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);
        
        // Enable query logging if configured
        if (config('performance.monitoring.log_queries')) {
            DB::enableQueryLog();
            $this->performanceService->enableQueryLogging();
        }

        $response = $next($request);

        // Calculate performance metrics
        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);
        
        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $memoryUsage = $endMemory - $startMemory;
        $queryCount = count(DB::getQueryLog());

        // Log performance metrics for slow requests
        if ($executionTime > config('performance.monitoring.slow_query_threshold')) {
            Log::warning('Slow request detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'execution_time' => round($executionTime, 2) . 'ms',
                'memory_usage' => $this->formatBytes($memoryUsage),
                'query_count' => $queryCount,
                'user_id' => $request->user()?->id,
            ]);
        }

        // Add performance headers to response
        $response->headers->set('X-Execution-Time', round($executionTime, 2));
        $response->headers->set('X-Memory-Usage', $this->formatBytes($memoryUsage));
        $response->headers->set('X-Query-Count', $queryCount);

        return $response;
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
}