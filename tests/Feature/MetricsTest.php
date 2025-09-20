<?php

namespace Tests\Feature;

use Tests\TestCase;

class MetricsTest extends TestCase
{
    public function test_metrics_endpoint_returns_prometheus_format(): void
    {
        $response = $this->get('/metrics');

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'text/plain; version=0.0.4; charset=utf-8');
        
        // Check for basic Prometheus metrics format
        $content = $response->getContent();
        $this->assertStringContains('# HELP', $content);
        $this->assertStringContains('# TYPE', $content);
    }

    public function test_health_endpoint_returns_json(): void
    {
        $response = $this->get('/health');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'status',
            'timestamp',
            'checks' => [
                'database' => ['status'],
                'redis' => ['status'],
                'storage' => ['status'],
                'queue' => ['status'],
            ],
            'version',
            'environment',
        ]);
    }

    public function test_readiness_endpoint(): void
    {
        $response = $this->get('/health/ready');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'ready',
        ]);
    }

    public function test_liveness_endpoint(): void
    {
        $response = $this->get('/health/live');

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'alive',
        ]);
    }
}