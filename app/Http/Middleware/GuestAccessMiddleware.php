<?php

namespace App\Http\Middleware;

use App\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GuestAccessMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $feature  The feature to check guest access for (languages, teachers, quizzes)
     */
    public function handle(Request $request, Closure $next, string $feature): Response
    {
        // If user is authenticated, allow access
        if ($request->user()) {
            return $next($request);
        }

        // Check if guest access is allowed for this feature
        $settingsService = app(SettingsService::class);
        if (!$settingsService->isGuestAccessAllowed($feature)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'GUEST_ACCESS_DENIED',
                    'message' => "Guest access to {$feature} is not allowed. Please log in to access this resource.",
                    'feature' => $feature,
                ],
            ], 403);
        }

        return $next($request);
    }
}
