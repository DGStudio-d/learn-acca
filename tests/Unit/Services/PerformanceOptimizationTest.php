<?php

namespace Tests\Unit\Services;

use App\Models\User;
use App\Models\Program;
use App\Models\Quiz;
use App\Services\CacheService;
use App\Services\FileOptimizationService;
use App\Services\PerformanceMonitoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PerformanceOptimizationTest extends TestCase
{
    use RefreshDatabase;

    protected $cacheService;
    protected $fileService;
    protected $performanceService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->cacheService = app(CacheService::class);
        $this->fileService = app(FileOptimizationService::class);
        $this->performanceService = app(PerformanceMonitoringService::class);
    }

    #[Test]
    public function it_caches_user_dashboard_data()
    {
        $user = User::factory()->create();
        
        // First call should hit the database
        $result1 = $this->cacheService->getUserDashboard($user->id);
        
        // Second call should hit the cache
        $result2 = $this->cacheService->getUserDashboard($user->id);
        
        $this->assertEquals($result1->id, $result2->id);
        $this->assertTrue(Cache::has("user_dashboard_{$user->id}"));
    }

    #[Test]
    public function it_caches_program_statistics()
    {
        $program = Program::factory()->create();
        
        $stats = $this->cacheService->getProgramStats($program->id);
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_enrollments', $stats);
        $this->assertTrue(Cache::has("program_stats_{$program->id}"));
    }

    #[Test]
    public function it_caches_system_statistics()
    {
        User::factory()->count(5)->create();
        Program::factory()->count(3)->create();
        
        $stats = $this->cacheService->getSystemStats();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('total_users', $stats);
        $this->assertArrayHasKey('total_programs', $stats);
        $this->assertTrue(Cache::has('system_stats'));
    }

    #[Test]
    public function it_invalidates_cache_when_models_are_updated()
    {
        $user = User::factory()->create();
        
        // Cache the user dashboard
        $this->cacheService->getUserDashboard($user->id);
        $this->assertTrue(Cache::has("user_dashboard_{$user->id}"));
        
        // Update the user (should trigger cache invalidation)
        $user->update(['name' => 'Updated Name']);
        
        // Cache should be cleared
        $this->assertFalse(Cache::has("user_dashboard_{$user->id}"));
    }

    #[Test]
    public function it_optimizes_image_files()
    {
        Storage::fake('public');
        
        $file = UploadedFile::fake()->image('test.jpg', 1200, 800);
        
        $result = $this->fileService->optimizeAndStoreImage($file, 'test-images');
        
        $this->assertArrayHasKey('path', $result);
        $this->assertArrayHasKey('thumbnail_path', $result);
        $this->assertArrayHasKey('size', $result);
        
        Storage::assertExists($result['path']);
        Storage::assertExists($result['thumbnail_path']);
    }

    #[Test]
    public function it_validates_file_types_and_sizes()
    {
        $validFile = UploadedFile::fake()->image('test.jpg', 800, 600);
        $invalidFile = UploadedFile::fake()->create('test.txt', 1000);
        
        $allowedTypes = ['image/jpeg', 'image/png'];
        $maxSize = 5 * 1024 * 1024; // 5MB
        
        $this->assertTrue(
            $this->fileService->validateFile($validFile, $allowedTypes, $maxSize)
        );
        
        $this->expectException(\Exception::class);
        $this->fileService->validateFile($invalidFile, $allowedTypes, $maxSize);
    }

    #[Test]
    public function it_generates_performance_recommendations()
    {
        // Create some test data
        User::factory()->count(10)->create();
        Program::factory()->count(5)->create();
        
        $recommendations = $this->performanceService->getPerformanceRecommendations();
        
        $this->assertIsArray($recommendations);
    }

    #[Test]
    public function it_monitors_cache_performance()
    {
        $metrics = $this->performanceService->getCacheMetrics();
        
        $this->assertIsArray($metrics);
        
        if (config('cache.default') === 'redis') {
            $this->assertArrayHasKey('redis', $metrics);
        }
    }

    #[Test]
    public function optimized_queries_use_proper_eager_loading()
    {
        $user = User::factory()->create();
        $program = Program::factory()->create();
        
        // Test that optimized scopes work
        $userWithRelations = User::withUserRelations()->find($user->id);
        $programWithRelations = Program::withProgramRelations()->find($program->id);
        
        $this->assertInstanceOf(User::class, $userWithRelations);
        $this->assertInstanceOf(Program::class, $programWithRelations);
    }

    #[Test]
    public function it_clears_related_caches_properly()
    {
        $user = User::factory()->create();
        
        // Cache some data
        $this->cacheService->getUserDashboard($user->id);
        $systemStats = $this->cacheService->getSystemStats();
        
        $this->assertTrue(Cache::has("user_dashboard_{$user->id}"));
        $this->assertTrue(Cache::has('system_stats'));
        
        // Clear user-specific cache
        $this->cacheService->clearUserDashboardCache($user->id);
        
        $this->assertFalse(Cache::has("user_dashboard_{$user->id}"));
        
        // Clear system stats
        $this->cacheService->clearSystemStatsCache();
        $this->assertFalse(Cache::has('system_stats'));
    }
}