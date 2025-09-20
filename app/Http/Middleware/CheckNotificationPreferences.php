<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckNotificationPreferences
{
    /**
     * Handle an incoming request.
     *
     * This middleware can be used to validate notification preferences
     * before allowing certain notification-related actions.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'message' => 'Authentication required',
                ],
            ], 401);
        }

        // Log notification preference access for audit purposes
        Log::info('Notification preferences accessed', [
            'user_id' => $user->id,
            'notify_email' => $user->notify_email,
            'notify_whatsapp' => $user->notify_whatsapp,
            'endpoint' => $request->path(),
            'method' => $request->method(),
        ]);

        return $next($request);
    }
}