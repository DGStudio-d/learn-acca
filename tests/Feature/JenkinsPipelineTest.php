<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * @group database
 */
class JenkinsPipelineTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that the Jenkins CI/CD pipeline can run basic application tests.
     */
    public function test_jenkins_pipeline_can_run_basic_tests(): void
    {
        // Test that the application boots correctly using health endpoint
        $response = $this->get('/health');

        // Should not return a server error
        $this->assertNotEquals(500, $response->getStatusCode());
    }

    /**
     * Test that database connection works in CI environment.
     */
    public function test_database_connection_works_in_ci(): void
    {
        // Test database connection
        $this->assertNotNull(DB::connection()->getPdo());

        // Test that we can query the database
        $result = DB::select('SELECT 1 as test');
        $this->assertEquals(1, $result[0]->test);
    }

    /**
     * Test that migrations run successfully in CI environment.
     */
    public function test_migrations_run_successfully(): void
    {
        // Test that migrations table exists
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('migrations'));

        // Test that we can run a simple migration check
        $migrations = DB::table('migrations')->count();
        $this->assertGreaterThan(0, $migrations);
    }

    /**
     * Test that seeding works in CI environment.
     */
    public function test_seeding_works_in_ci(): void
    {
        // Test that users table exists
        $this->assertTrue(DB::getSchemaBuilder()->hasTable('users'));

        // Run the testing seeder
        $this->artisan('db:seed', ['--class' => 'TestingSeeder', '--force' => true]);

        // The TestingSeeder should create at least 3 test users
        $userCount = DB::table('users')->count();
        $this->assertGreaterThanOrEqual(3, $userCount);
    }

    /**
     * Test that environment configuration is correct for testing.
     */
    public function test_environment_configuration_is_correct(): void
    {
        // Test that we're in testing environment
        $this->assertEquals('testing', app()->environment());

        // Test that testing configurations are applied
        $this->assertEquals('array', config('cache.default'));
        $this->assertEquals('array', config('session.driver'));
        $this->assertEquals('sync', config('queue.default'));
        $this->assertEquals('array', config('mail.default'));
    }

    /**
     * Test that required directories exist for CI pipeline.
     */
    public function test_required_directories_exist_for_ci(): void
    {
        $requiredDirectories = [
            storage_path('logs'),
            storage_path('framework/cache'),
            storage_path('framework/sessions'),
            storage_path('framework/views'),
        ];

        foreach ($requiredDirectories as $directory) {
            $this->assertDirectoryExists($directory, "Required directory does not exist: {$directory}");
        }
    }

    /**
     * Test that the application can handle basic HTTP requests.
     */
    public function test_application_handles_basic_http_requests(): void
    {
        // Test that the application responds to basic routes
        $routes = [
            '/health',
            '/api/health',
        ];

        foreach ($routes as $route) {
            $response = $this->get($route);

            // Should not return server errors
            $this->assertLessThan(500, $response->getStatusCode(),
                "Route {$route} returned server error: " . $response->getStatusCode());
        }
    }

    /**
     * Test that Prometheus metrics endpoint works.
     */
    public function test_prometheus_metrics_endpoint_works(): void
    {
        $response = $this->get('/metrics');

        // Should return 200 OK
        $response->assertStatus(200);

        // Should return text/plain content type for Prometheus
        $this->assertStringContainsString('text/plain', $response->headers->get('Content-Type'));

        // Should contain some basic metrics format
        $content = $response->getContent();
        $this->assertStringContainsString('# HELP', $content);
        $this->assertStringContainsString('# TYPE', $content);
    }

    /**
     * Test that Prometheus service provider is registered.
     */
    public function test_prometheus_service_provider_is_registered(): void
    {
        // Test that the CollectorRegistry is bound in the container
        $this->assertTrue(app()->bound(\Prometheus\CollectorRegistry::class));

        // Test that we can resolve the CollectorRegistry
        $registry = app(\Prometheus\CollectorRegistry::class);
        $this->assertInstanceOf(\Prometheus\CollectorRegistry::class, $registry);
    }
}
