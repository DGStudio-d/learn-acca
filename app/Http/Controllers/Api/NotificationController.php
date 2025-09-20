<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateNotificationPreferencesRequest;
use App\Http\Resources\NotificationLogResource;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    protected NotificationService $notificationService;

    public function __construct(NotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    /**
     * Get current user's notification history.
     */
    public function getUserHistory(Request $request): JsonResponse
    {
        $user = Auth::user();
        $limit = $request->get('limit', 50);
        
        $history = $this->notificationService->getUserNotificationHistory($user, $limit);
        
        return response()->json([
            'success' => true,
            'data' => [
                'notifications' => NotificationLogResource::collection($history->items()),
                'pagination' => [
                    'current_page' => $history->currentPage(),
                    'last_page' => $history->lastPage(),
                    'per_page' => $history->perPage(),
                    'total' => $history->total(),
                    'has_more_pages' => $history->hasMorePages(),
                ],
            ],
        ]);
    }

    /**
     * Get current user's notification statistics.
     */
    public function getUserStats(): JsonResponse
    {
        $user = Auth::user();
        $stats = $this->notificationService->getUserNotificationStats($user);
        
        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Update user notification preferences.
     */
    public function updatePreferences(UpdateNotificationPreferencesRequest $request): JsonResponse
    {
        $user = Auth::user();
        
        $user->update($request->only(['notify_email', 'notify_whatsapp']));
        
        return response()->json([
            'success' => true,
            'message' => 'Notification preferences updated successfully',
            'data' => [
                'notify_email' => $user->notify_email,
                'notify_whatsapp' => $user->notify_whatsapp,
            ],
        ]);
    }

    /**
     * Get system notification statistics (admin only).
     */
    public function getSystemStats(): JsonResponse
    {
        // Check if user has admin role
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }
        
        $stats = $this->notificationService->getNotificationStats();
        
        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get notification history for a specific user (admin only).
     */
    public function getUserHistoryByAdmin(Request $request, User $user): JsonResponse
    {
        // Check if user has admin role
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }
        
        $limit = $request->get('limit', 50);
        $history = $this->notificationService->getUserNotificationHistory($user, $limit);
        
        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                ],
                'notifications' => NotificationLogResource::collection($history->items()),
                'pagination' => [
                    'current_page' => $history->currentPage(),
                    'last_page' => $history->lastPage(),
                    'per_page' => $history->perPage(),
                    'total' => $history->total(),
                    'has_more_pages' => $history->hasMorePages(),
                ],
            ],
        ]);
    }

    /**
     * Retry failed notifications (admin only).
     */
    public function retryFailedNotifications(Request $request): JsonResponse
    {
        // Check if user has admin role
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }
        
        $request->validate([
            'limit' => 'integer|min:1|max:100',
        ]);
        
        $limit = $request->get('limit', 10);
        $retryCount = $this->notificationService->retryFailedNotifications($limit);
        
        return response()->json([
            'success' => true,
            'message' => "Dispatched retry jobs for {$retryCount} failed notifications",
            'data' => [
                'retry_count' => $retryCount,
            ],
        ]);
    }

    /**
     * Clean up old notification logs (admin only).
     */
    public function cleanupOldLogs(Request $request): JsonResponse
    {
        // Check if user has admin role
        if (!Auth::user()->hasRole('admin')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }
        
        $request->validate([
            'days' => 'integer|min:1|max:365',
        ]);
        
        $days = $request->get('days', 90);
        $deletedCount = $this->notificationService->cleanupOldNotifications($days);
        
        return response()->json([
            'success' => true,
            'message' => "Deleted {$deletedCount} old notification logs",
            'data' => [
                'deleted_count' => $deletedCount,
                'days_kept' => $days,
            ],
        ]);
    }
}