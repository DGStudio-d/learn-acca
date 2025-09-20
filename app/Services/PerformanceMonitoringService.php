<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class PerformanceMonitoringService
{
    const SLOW_QUERY_THRESHOLD = 1000; // milliseconds
    const CACHE_KEY_PREFIX = 'performance_metrics_';

    /**
     * Enable query logging for performance monitoring.
     */
    public function enableQueryLogging(): void
    {
        DB::listen(function ($query) {
            if ($query->time > self::SLOW_QUERY_THRESHOLD) {
                Log::warning('Slow query detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time . 'ms',
                    'connection' => $query->connectionName,
                ]);
            }
        });
    }

    /**
     * Get database performance metrics.
     */
    public function getDatabaseMetrics(): array
    {
        return Cache::remember(self::CACHE_KEY_PREFIX . 'db_metrics', 300, function () {
            $metrics = [];

            try {
                // Get table sizes
                $tableSizes = DB::select("
                    SELECT 
                        table_name,
                        ROUND(((data_length + index_length) / 1024 / 1024), 2) AS size_mb,
                        table_rows
                    FROM information_schema.tables 
                    WHERE table_schema = DATABASE()
                    ORDER BY (data_length + index_length) DESC
                ");

                $metrics['table_sizes'] = $tableSizes;

                // Get index usage statistics
                $indexStats = DB::select("
                    SELECT 
                        table_name,
                        index_name,
                        cardinality,
                        non_unique
                    FROM information_schema.statistics 
                    WHERE table_schema = DATABASE()
                    ORDER BY table_name, cardinality DESC
                ");

                $metrics['index_stats'] = $indexStats;

                // Get connection pool status
                $connectionStats = DB::select("SHOW STATUS LIKE 'Threads_%'");
                $metrics['connection_stats'] = $connectionStats;

            } catch (\Exception $e) {
                Log::error('Failed to get database metrics', ['error' => $e->getMessage()]);
                $metrics['error'] = 'Failed to retrieve metrics';
            }

            return $metrics;
        });
    }

    /**
     * Analyze query performance and suggest optimizations.
     */
    public function analyzeQueryPerformance(string $sql, array $bindings = []): array
    {
        $analysis = [];

        try {
            // Explain the query
            $explainResult = DB::select("EXPLAIN " . $sql, $bindings);
            $analysis['explain'] = $explainResult;

            // Check for potential issues
            $issues = [];
            foreach ($explainResult as $row) {
                if ($row->type === 'ALL') {
                    $issues[] = "Full table scan detected on table: {$row->table}";
                }
                
                if ($row->key === null && $row->rows > 1000) {
                    $issues[] = "No index used for table: {$row->table} with {$row->rows} rows";
                }
                
                if (isset($row->Extra) && strpos($row->Extra, 'Using filesort') !== false) {
                    $issues[] = "Filesort detected - consider adding appropriate index";
                }
                
                if (isset($row->Extra) && strpos($row->Extra, 'Using temporary') !== false) {
                    $issues[] = "Temporary table created - query may benefit from optimization";
                }
            }

            $analysis['issues'] = $issues;
            $analysis['suggestions'] = $this->generateOptimizationSuggestions($issues);

        } catch (\Exception $e) {
            $analysis['error'] = 'Failed to analyze query: ' . $e->getMessage();
        }

        return $analysis;
    }

    /**
     * Generate optimization suggestions based on detected issues.
     */
    private function generateOptimizationSuggestions(array $issues): array
    {
        $suggestions = [];

        foreach ($issues as $issue) {
            if (strpos($issue, 'Full table scan') !== false) {
                $suggestions[] = 'Add appropriate WHERE clause conditions and ensure they are indexed';
            }
            
            if (strpos($issue, 'No index used') !== false) {
                $suggestions[] = 'Consider adding composite indexes for frequently queried columns';
            }
            
            if (strpos($issue, 'Filesort') !== false) {
                $suggestions[] = 'Add index that matches ORDER BY clause columns';
            }
            
            if (strpos($issue, 'temporary table') !== false) {
                $suggestions[] = 'Optimize GROUP BY and JOIN operations, consider denormalization';
            }
        }

        return array_unique($suggestions);
    }

    /**
     * Get cache performance metrics.
     */
    public function getCacheMetrics(): array
    {
        return Cache::remember(self::CACHE_KEY_PREFIX . 'cache_metrics', 300, function () {
            $metrics = [];

            try {
                if (config('cache.default') === 'redis') {
                    $redis = Cache::getRedis();
                    $info = $redis->info();
                    
                    $metrics['redis'] = [
                        'used_memory' => $info['used_memory_human'] ?? 'N/A',
                        'connected_clients' => $info['connected_clients'] ?? 'N/A',
                        'total_commands_processed' => $info['total_commands_processed'] ?? 'N/A',
                        'keyspace_hits' => $info['keyspace_hits'] ?? 0,
                        'keyspace_misses' => $info['keyspace_misses'] ?? 0,
                    ];
                    
                    // Calculate hit ratio
                    $hits = (int)($info['keyspace_hits'] ?? 0);
                    $misses = (int)($info['keyspace_misses'] ?? 0);
                    $total = $hits + $misses;
                    
                    $metrics['redis']['hit_ratio'] = $total > 0 ? round(($hits / $total) * 100, 2) : 0;
                }

            } catch (\Exception $e) {
                Log::error('Failed to get cache metrics', ['error' => $e->getMessage()]);
                $metrics['error'] = 'Failed to retrieve cache metrics';
            }

            return $metrics;
        });
    }

    /**
     * Monitor and log application performance metrics.
     */
    public function logPerformanceMetrics(): void
    {
        $metrics = [
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'execution_time' => microtime(true) - LARAVEL_START,
            'database_queries' => count(DB::getQueryLog()),
        ];

        Log::info('Performance metrics', $metrics);
    }

    /**
     * Get recommendations for performance improvements.
     */
    public function getPerformanceRecommendations(): array
    {
        $recommendations = [];
        
        $dbMetrics = $this->getDatabaseMetrics();
        $cacheMetrics = $this->getCacheMetrics();

        // Database recommendations
        if (isset($dbMetrics['table_sizes'])) {
            foreach ($dbMetrics['table_sizes'] as $table) {
                if ($table->size_mb > 100) {
                    $recommendations[] = "Consider partitioning large table: {$table->table_name} ({$table->size_mb}MB)";
                }
                
                if ($table->table_rows > 100000) {
                    $recommendations[] = "Table {$table->table_name} has {$table->table_rows} rows - ensure proper indexing";
                }
            }
        }

        // Cache recommendations
        if (isset($cacheMetrics['redis']['hit_ratio']) && $cacheMetrics['redis']['hit_ratio'] < 80) {
            $recommendations[] = "Cache hit ratio is low ({$cacheMetrics['redis']['hit_ratio']}%) - review caching strategy";
        }

        return $recommendations;
    }
}