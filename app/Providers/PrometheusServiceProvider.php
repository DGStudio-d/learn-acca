<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Predis\Client as PredisClient;
use Prometheus\CollectorRegistry;
use Prometheus\Storage\InMemory;
use Prometheus\Storage\Redis;

class PrometheusServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(CollectorRegistry::class, function ($app) {
            // Use in-memory storage for testing environment
            if (app()->environment('testing')) {
                $adapter = new InMemory();
            } else {
                try {
                    // Try to use Redis with Predis client
                    $predisClient = new PredisClient([
                        'scheme' => 'tcp',
                        'host' => config('database.redis.default.host', '127.0.0.1'),
                        'port' => config('database.redis.default.port', 6379),
                        'password' => config('database.redis.default.password'),
                        'database' => config('database.redis.default.database', 0),
                    ]);

                    // Test the connection
                    $predisClient->ping();

                    $adapter = new Redis([
                        'host' => config('database.redis.default.host', '127.0.0.1'),
                        'port' => config('database.redis.default.port', 6379),
                        'password' => config('database.redis.default.password'),
                        'timeout' => 0.1,
                        'read_timeout' => '10',
                        'persistent_connections' => false,
                    ]);
                } catch (\Exception $e) {
                    // Fallback to in-memory storage if Redis is not available
                    \Log::warning('Redis not available for Prometheus, using in-memory storage: ' . $e->getMessage());
                    $adapter = new InMemory();
                }
            }

            return new CollectorRegistry($adapter);
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
