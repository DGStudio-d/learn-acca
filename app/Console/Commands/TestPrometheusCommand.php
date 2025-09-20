<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Prometheus\CollectorRegistry;

class TestPrometheusCommand extends Command
{
    protected $signature = 'prometheus:test';
    protected $description = 'Test Prometheus metrics collection';

    public function handle(CollectorRegistry $registry): int
    {
        $this->info('Testing Prometheus metrics collection...');

        try {
            // Test counter
            $counter = $registry->getOrRegisterCounter(
                'laravel',
                'test_counter',
                'Test counter for Prometheus'
            );
            $counter->inc();
            $this->info('✓ Counter test passed');

            // Test gauge
            $gauge = $registry->getOrRegisterGauge(
                'laravel',
                'test_gauge',
                'Test gauge for Prometheus'
            );
            $gauge->set(42);
            $this->info('✓ Gauge test passed');

            // Test histogram
            $histogram = $registry->getOrRegisterHistogram(
                'laravel',
                'test_histogram',
                'Test histogram for Prometheus',
                [],
                [0.1, 0.5, 1, 2, 5]
            );
            $histogram->observe(1.5);
            $this->info('✓ Histogram test passed');

            // Test Redis connection
            $samples = $registry->getMetricFamilySamples();
            $this->info('✓ Retrieved ' . count($samples) . ' metric families');

            $this->info('Prometheus metrics collection is working correctly!');
            return 0;

        } catch (\Exception $e) {
            $this->error('Prometheus test failed: ' . $e->getMessage());
            return 1;
        }
    }
}