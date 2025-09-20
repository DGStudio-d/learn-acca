<?php

namespace App\Console\Commands;

use App\Services\PerformanceMonitoringService;
use App\Services\FileOptimizationService;
use Illuminate\Console\Command;

class PerformanceAnalysisCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'performance:analyze 
                            {--cleanup : Clean up old files}
                            {--metrics : Show performance metrics}
                            {--recommendations : Show performance recommendations}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Analyze system performance and provide optimization recommendations';

    protected $performanceService;
    protected $fileService;

    public function __construct(PerformanceMonitoringService $performanceService, FileOptimizationService $fileService)
    {
        parent::__construct();
        $this->performanceService = $performanceService;
        $this->fileService = $fileService;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🚀 Performance Analysis Tool');
        $this->newLine();

        if ($this->option('cleanup')) {
            $this->performFileCleanup();
        }

        if ($this->option('metrics')) {
            $this->showPerformanceMetrics();
        }

        if ($this->option('recommendations')) {
            $this->showRecommendations();
        }

        if (!$this->option('cleanup') && !$this->option('metrics') && !$this->option('recommendations')) {
            $this->showFullAnalysis();
        }

        return Command::SUCCESS;
    }

    /**
     * Perform file cleanup.
     */
    private function performFileCleanup(): void
    {
        $this->info('🧹 Cleaning up old files...');
        
        if (!config('performance.files.cleanup.enabled')) {
            $this->warn('File cleanup is disabled in configuration');
            return;
        }

        $daysOld = config('performance.files.cleanup.days_old', 30);
        
        try {
            $deletedImages = $this->fileService->cleanupOldFiles('images', $daysOld);
            $deletedQuizzes = $this->fileService->cleanupOldFiles('quizzes', $daysOld);
            
            $this->info("✅ Cleaned up {$deletedImages} old image files");
            $this->info("✅ Cleaned up {$deletedQuizzes} old quiz files");
            
        } catch (\Exception $e) {
            $this->error("❌ File cleanup failed: " . $e->getMessage());
        }
    }

    /**
     * Show performance metrics.
     */
    private function showPerformanceMetrics(): void
    {
        $this->info('📊 Performance Metrics');
        $this->newLine();

        // Database metrics
        $dbMetrics = $this->performanceService->getDatabaseMetrics();
        
        if (isset($dbMetrics['table_sizes'])) {
            $this->info('📋 Database Table Sizes:');
            $headers = ['Table', 'Size (MB)', 'Rows'];
            $rows = [];
            
            foreach (array_slice($dbMetrics['table_sizes'], 0, 10) as $table) {
                $rows[] = [$table->table_name, $table->size_mb, number_format($table->table_rows)];
            }
            
            $this->table($headers, $rows);
            $this->newLine();
        }

        // Cache metrics
        $cacheMetrics = $this->performanceService->getCacheMetrics();
        
        if (isset($cacheMetrics['redis'])) {
            $this->info('🗄️ Cache Metrics (Redis):');
            $redis = $cacheMetrics['redis'];
            
            $this->line("Memory Usage: {$redis['used_memory']}");
            $this->line("Connected Clients: {$redis['connected_clients']}");
            $this->line("Hit Ratio: {$redis['hit_ratio']}%");
            $this->line("Total Commands: " . number_format($redis['total_commands_processed']));
            $this->newLine();
        }
    }

    /**
     * Show performance recommendations.
     */
    private function showRecommendations(): void
    {
        $this->info('💡 Performance Recommendations');
        $this->newLine();

        $recommendations = $this->performanceService->getPerformanceRecommendations();
        
        if (empty($recommendations)) {
            $this->info('✅ No performance issues detected!');
            return;
        }

        foreach ($recommendations as $index => $recommendation) {
            $this->line(($index + 1) . ". " . $recommendation);
        }
        
        $this->newLine();
    }

    /**
     * Show full performance analysis.
     */
    private function showFullAnalysis(): void
    {
        $this->showPerformanceMetrics();
        $this->showRecommendations();
        
        $this->info('🔧 Available Options:');
        $this->line('  --cleanup          Clean up old files');
        $this->line('  --metrics          Show detailed metrics');
        $this->line('  --recommendations  Show optimization recommendations');
    }
}
