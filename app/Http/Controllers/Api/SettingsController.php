<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SettingsController extends Controller
{
    public function __construct()
    {
        // Middleware is applied in routes, not needed here
    }

    /**
     * Get all guest access settings.
     */
    public function getGuestAccessSettings(): JsonResponse
    {
        try {
            $settingsService = app(SettingsService::class);
            $settings = $settingsService->getGuestAccessSettings();

            return response()->json([
                'success' => true,
                'data' => [
                    'settings' => $settings,
                    'available_features' => $settingsService->getAvailableGuestFeatures(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SETTINGS_RETRIEVAL_FAILED',
                    'message' => 'Failed to retrieve guest access settings',
                    'details' => config('app.debug') ? $e->getMessage() : null,
                ],
            ], 500);
        }
    }

    /**
     * Update guest access settings.
     */
    public function updateGuestAccessSettings(Request $request): JsonResponse
    {
        try {
            $settingsService = app(SettingsService::class);
            $settings = $request->only([
                'allow_guest_languages',
                'allow_guest_teachers',
                'allow_guest_quizzes',
            ]);

            $updatedSettings = $settingsService->updateGuestAccessSettings($settings);

            return response()->json([
                'success' => true,
                'message' => 'Guest access settings updated successfully',
                'data' => [
                    'settings' => $updatedSettings,
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Invalid settings data provided',
                    'details' => $e->errors(),
                ],
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SETTINGS_UPDATE_FAILED',
                    'message' => 'Failed to update guest access settings',
                    'details' => config('app.debug') ? $e->getMessage() : null,
                ],
            ], 500);
        }
    }

    /**
     * Get a specific setting value.
     */
    public function getSetting(string $key): JsonResponse
    {
        try {
            $settingsService = app(SettingsService::class);
            $value = $settingsService->getSetting($key);

            if ($value === null) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'SETTING_NOT_FOUND',
                        'message' => "Setting '{$key}' not found",
                    ],
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'key' => $key,
                    'value' => $value,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SETTING_RETRIEVAL_FAILED',
                    'message' => 'Failed to retrieve setting',
                    'details' => config('app.debug') ? $e->getMessage() : null,
                ],
            ], 500);
        }
    }

    /**
     * Set a specific setting value.
     */
    public function setSetting(Request $request, string $key): JsonResponse
    {
        try {
            $request->validate([
                'value' => 'required',
                'type' => 'sometimes|string|in:string,boolean,integer,float,array,json',
                'description' => 'sometimes|string|max:255',
            ]);

            $settingsService = app(SettingsService::class);
            $setting = $settingsService->setSetting(
                $key,
                $request->input('value'),
                $request->input('type', 'string'),
                $request->input('description')
            );

            return response()->json([
                'success' => true,
                'message' => 'Setting updated successfully',
                'data' => [
                    'setting' => [
                        'key' => $setting->key,
                        'value' => $setting->value,
                        'type' => $setting->type,
                        'description' => $setting->description,
                    ],
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Invalid setting data provided',
                    'details' => $e->errors(),
                ],
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SETTING_UPDATE_FAILED',
                    'message' => 'Failed to update setting',
                    'details' => config('app.debug') ? $e->getMessage() : null,
                ],
            ], 500);
        }
    }

    /**
     * Initialize default settings.
     */
    public function initializeDefaults(): JsonResponse
    {
        try {
            $settingsService = app(SettingsService::class);
            $settingsService->initializeDefaultSettings();

            return response()->json([
                'success' => true,
                'message' => 'Default settings initialized successfully',
                'data' => [
                    'settings' => $settingsService->getGuestAccessSettings(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INITIALIZATION_FAILED',
                    'message' => 'Failed to initialize default settings',
                    'details' => config('app.debug') ? $e->getMessage() : null,
                ],
            ], 500);
        }
    }
}