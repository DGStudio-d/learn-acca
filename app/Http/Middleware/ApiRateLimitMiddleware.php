<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class ApiRateLimitMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $type = 'api'): Response
    {
        $config = config("rate-limiting.{$type}");
        
        if (!$config) {
            return $next($request);
        }

        $key = $this->resolveRequestSignature($request, $type);
        $maxAttempts = $config['requests'] ?? 60;
        $decayMinutes = $config['per_minute'] ?? 1;

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);
            
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'RATE_LIMIT_EXCEEDED',
                    'message' => 'Too many requests. Please try again later.',
                    'retry_after' => $seconds
                ]
            ], 429, [
                'Retry-After' => $seconds,
                'X-RateLimit-Limit' => $maxAttempts,
                'X-RateLimit-Remaining' => 0,
            ]);
        }

        RateLimiter::hit($key, $decayMinutes * 60);

        $response = $next($request);

        $remaining = RateLimiter::remaining($key, $maxAttempts);
        
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remaining,
        ]);

        return $response;
    }

    /**
     * Resolve the rate limiting signature for the request.
     */
    protected function resolveRequestSignature(Request $request, string $type): string
    {
        $user = $request->user();
        
        if ($user) {
            return "rate_limit:{$type}:user:{$user->id}";
        }

        return "rate_limit:{$type}:ip:" . $request->ip();
    }
}