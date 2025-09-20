<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Throwable;

class MonitoringService
{
    /**
     * Log security events
     */
    public function logSecurityEvent(string $event, array $context = []): void
    {
        Log::channel('security')->info($event, array_merge($context, [
            'timestamp' => now()->toISOString(),
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'user_id' => auth()->id(),
        ]));
    }

    /**
     * Log performance metrics
     */
    public function logPerformanceMetric(string $metric, float $value, array $context = []): void
    {
        Log::channel('performance')->info($metric, array_merge($context, [
            'value' => $value,
            'timestamp' => now()->toISOString(),
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true),
        ]));
    }

    /**
     * Log notification events
     */
    public function logNotificationEvent(string $event, array $context = []): void
    {
        Log::channel('notifications')->info($event, array_merge($context, [
            'timestamp' => now()->toISOString(),
        ]));
    }

    /**
     * Log authentication events
     */
    public function logAuthEvent(string $event, ?int $userId = null, array $context = []): void
    {
        $this->logSecurityEvent("auth.{$event}", array_merge($context, [
            'user_id' => $userId,
            'session_id' => session()->getId(),
        ]));
    }

    /**
     * Log API access events
     */
    public function logApiAccess(Request $request, int $statusCode, float $responseTime): void
    {
        Log::channel('performance')->info('api.request', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status_code' => $statusCode,
            'response_time_ms' => round($responseTime * 1000, 2),
            'user_id' => auth()->id(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Log critical errors with context
     */
    public function logCriticalError(Throwable $exception, array $context = []): void
    {
        Log::critical('Critical error occurred', array_merge($context, [
            'exception' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'user_id' => auth()->id(),
            'ip' => request()->ip(),
            'url' => request()->fullUrl(),
            'timestamp' => now()->toISOString(),
        ]));
    }

    /**
     * Log rate limiting events
     */
    public function logRateLimitExceeded(string $key, int $maxAttempts): void
    {
        $this->logSecurityEvent('rate_limit.exceeded', [
            'key' => $key,
            'max_attempts' => $maxAttempts,
        ]);
    }

    /**
     * Log file upload events
     */
    public function logFileUpload(string $filename, int $size, string $type, ?int $userId = null): void
    {
        Log::channel('security')->info('file.uploaded', [
            'filename' => $filename,
            'size_bytes' => $size,
            'mime_type' => $type,
            'user_id' => $userId,
            'ip' => request()->ip(),
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Log database query performance
     */
    public function logSlowQuery(string $sql, float $time, array $bindings = []): void
    {
        if ($time > 1000) { // Log queries taking more than 1 second
            Log::channel('performance')->warning('slow_query', [
                'sql' => $sql,
                'time_ms' => $time,
                'bindings' => $bindings,
                'timestamp' => now()->toISOString(),
            ]);
        }
    }
}