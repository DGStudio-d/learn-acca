<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;

class MetricsController extends Controller
{
    private CollectorRegistry $registry;

    public function __construct(CollectorRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function metrics(): Response
    {
        // Add application-specific metrics
        $this->recordApplicationMetrics();

        $renderer = new RenderTextFormat();
        $result = $renderer->render($this->registry->getMetricFamilySamples());

        return response($result, 200, [
            'Content-Type' => RenderTextFormat::MIME_TYPE,
        ]);
    }

    private function recordApplicationMetrics(): void
    {
        // Database connection status
        try {
            \DB::connection()->getPdo();
            $dbStatus = 1;
        } catch (\Exception $e) {
            $dbStatus = 0;
        }

        $dbGauge = $this->registry->getOrRegisterGauge(
            'laravel',
            'database_connection_status',
            'Database connection status (1 = connected, 0 = disconnected)'
        );
        $dbGauge->set($dbStatus);

        // Redis connection status
        $redisStatus = 0;

        try {
            if (app()->environment('testing')) {
                // Skip Redis check in testing
                $redisStatus = 1;
            } else {
                \Redis::ping();
                $redisStatus = 1;
            }
        } catch (\Exception $e) {
            $redisStatus = 0;
        }

        $redisGauge = $this->registry->getOrRegisterGauge(
            'laravel',
            'redis_connection_status',
            'Redis connection status (1 = connected, 0 = disconnected)'
        );
        $redisGauge->set($redisStatus);

        // Queue metrics
        $queueSize = 0;

        try {
            if (\DB::getSchemaBuilder()->hasTable('jobs')) {
                $queueSize = \DB::table('jobs')->count();
            }
        } catch (\Exception $e) {
            // Ignore errors in testing
        }

        $queueGauge = $this->registry->getOrRegisterGauge(
            'laravel',
            'queue_jobs_pending',
            'Number of pending jobs in queue'
        );
        $queueGauge->set($queueSize);

        // Failed jobs
        $failedJobs = 0;

        try {
            if (\DB::getSchemaBuilder()->hasTable('failed_jobs')) {
                $failedJobs = \DB::table('failed_jobs')->count();
            }
        } catch (\Exception $e) {
            // Ignore errors in testing
        }

        $failedJobsGauge = $this->registry->getOrRegisterGauge(
            'laravel',
            'queue_jobs_failed_total',
            'Total number of failed jobs'
        );
        $failedJobsGauge->set($failedJobs);

        // Application version
        $versionGauge = $this->registry->getOrRegisterGauge(
            'laravel',
            'application_info',
            'Application information',
            ['version', 'environment']
        );
        $versionGauge->set(1, [
            config('app.version', '1.0.0'),
            config('app.env', 'production'),
        ]);

        // Memory usage
        $memoryGauge = $this->registry->getOrRegisterGauge(
            'laravel',
            'memory_usage_bytes',
            'Current memory usage in bytes'
        );
        $memoryGauge->set(memory_get_usage(true));

        // Peak memory usage
        $peakMemoryGauge = $this->registry->getOrRegisterGauge(
            'laravel',
            'memory_peak_usage_bytes',
            'Peak memory usage in bytes'
        );
        $peakMemoryGauge->set(memory_get_peak_usage(true));
    }
}
