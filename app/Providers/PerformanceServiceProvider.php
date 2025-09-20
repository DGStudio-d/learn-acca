<?php

namespace App\Providers;

use App\Services\CacheService;
use App\Services\FileOptimizationService;
use App\Services\PerformanceMonitoringService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;

class PerformanceServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register performance services as singletons
        $this->app->singleton(PerformanceMonitoringService::class);
        $this->app->singleton(FileOptimizationService::class);
        $this->app->singleton(CacheService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Enable query logging in development
        if (config('app.debug') && config('performance.monitoring.enabled')) {
            $performanceService = $this->app->make(PerformanceMonitoringService::class);
            $performanceService->enableQueryLogging();
        }

        // Set up database query optimization
        $this->optimizeDatabaseQueries();
        
        // Configure cache tags if using Redis
        $this->configureCacheTags();
    }

    /**
     * Optimize database queries globally.
     */
    private function optimizeDatabaseQueries(): void
    {
        // Prevent N+1 queries in production
        if (!config('app.debug')) {
            DB::preventLazyLoading();
        }

        // Set default query timeout
        DB::setDefaultTimeout(30);
    }

    /**
     * Configure cache tags for better cache management.
     */
    private function configureCacheTags(): void
    {
        if (config('cache.default') === 'redis') {
            // Cache tags configuration is handled by the CacheService
            // This method can be extended for additional cache optimizations
        }
    }
}
