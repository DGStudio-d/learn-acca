<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Prometheus\CollectorRegistry;
use Symfony\Component\HttpFoundation\Response;

class PrometheusMiddleware
{
    private CollectorRegistry $registry;

    public function __construct(CollectorRegistry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);
        
        $response = $next($request);
        
        $duration = microtime(true) - $start;
        
        // Record HTTP request metrics
        $this->recordHttpMetrics($request, $response, $duration);
        
        return $response;
    }

    private function recordHttpMetrics(Request $request, Response $response, float $duration): void
    {
        $method = $request->getMethod();
        $route = $request->route() ? $request->route()->getName() ?? 'unnamed' : 'unknown';
        $status = (string) $response->getStatusCode();
        
        // HTTP requests total counter
        $requestsTotal = $this->registry->getOrRegisterCounter(
            'laravel',
            'http_requests_total',
            'Total number of HTTP requests',
            ['method', 'route', 'status']
        );
        $requestsTotal->inc([$method, $route, $status]);
        
        // HTTP request duration histogram
        $requestDuration = $this->registry->getOrRegisterHistogram(
            'laravel',
            'http_request_duration_seconds',
            'HTTP request duration in seconds',
            ['method', 'route'],
            [0.005, 0.01, 0.025, 0.05, 0.1, 0.25, 0.5, 1, 2.5, 5, 10]
        );
        $requestDuration->observe($duration, [$method, $route]);
        
        // HTTP response size histogram
        $responseSize = $this->registry->getOrRegisterHistogram(
            'laravel',
            'http_response_size_bytes',
            'HTTP response size in bytes',
            ['method', 'route'],
            [100, 1000, 10000, 100000, 1000000]
        );
        
        $size = strlen($response->getContent());
        $responseSize->observe($size, [$method, $route]);
    }
}