<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;

class TranslationController extends Controller
{
    public function __construct(
        private TranslationService $translationService
    ) {}

    /**
     * Get all translations for a specific locale
     */
    public function getTranslations(string $locale): JsonResponse
    {
        try {
            $translations = $this->translationService->getTranslations($locale);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'locale' => $locale,
                    'translations' => $translations,
                    'supported_locales' => $this->translationService->getSupportedLocales()
                ]
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_LOCALE',
                    'message' => $e->getMessage(),
                    'supported_locales' => $this->translationService->getSupportedLocales()
                ]
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TRANSLATION_ERROR',
                    'message' => 'Failed to load translations'
                ]
            ], 500);
        }
    }

    /**
     * Get a specific translation key
     */
    public function getTranslation(string $locale, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string',
            'parameters' => 'sometimes|array'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Invalid request parameters',
                    'details' => $validator->errors()
                ]
            ], 422);
        }

        try {
            $key = $request->input('key');
            $parameters = $request->input('parameters', []);
            
            if (!$this->translationService->validateTranslationKey($key)) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_KEY',
                        'message' => 'Invalid translation key format'
                    ]
                ], 400);
            }
            
            $translation = $this->translationService->getTranslation($locale, $key, $parameters);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'locale' => $locale,
                    'key' => $key,
                    'translation' => $translation,
                    'parameters' => $parameters
                ]
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_LOCALE',
                    'message' => $e->getMessage()
                ]
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TRANSLATION_ERROR',
                    'message' => 'Failed to get translation'
                ]
            ], 500);
        }
    }

    /**
     * Update a translation key (Admin only)
     */
    public function updateTranslation(string $locale, Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string',
            'value' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Invalid request parameters',
                    'details' => $validator->errors()
                ]
            ], 422);
        }

        try {
            $key = $request->input('key');
            $value = $request->input('value');
            
            if (!$this->translationService->validateTranslationKey($key)) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_KEY',
                        'message' => 'Invalid translation key format'
                    ]
                ], 400);
            }
            
            $success = $this->translationService->updateTranslation($locale, $key, $value);
            
            if (!$success) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'UPDATE_FAILED',
                        'message' => 'Failed to update translation'
                    ]
                ], 500);
            }
            
            return response()->json([
                'success' => true,
                'data' => [
                    'locale' => $locale,
                    'key' => $key,
                    'value' => $value,
                    'message' => 'Translation updated successfully'
                ]
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_LOCALE',
                    'message' => $e->getMessage()
                ]
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TRANSLATION_ERROR',
                    'message' => 'Failed to update translation'
                ]
            ], 500);
        }
    }

    /**
     * Get available translation keys for a locale
     */
    public function getAvailableKeys(string $locale): JsonResponse
    {
        try {
            $keys = $this->translationService->getAvailableKeys($locale);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'locale' => $locale,
                    'keys' => $keys,
                    'count' => count($keys)
                ]
            ]);
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_LOCALE',
                    'message' => $e->getMessage()
                ]
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'TRANSLATION_ERROR',
                    'message' => 'Failed to get available keys'
                ]
            ], 500);
        }
    }

    /**
     * Clear translation cache (Admin only)
     */
    public function clearCache(Request $request): JsonResponse
    {
        try {
            $locale = $request->input('locale');
            
            if ($locale && !in_array($locale, $this->translationService->getSupportedLocales())) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_LOCALE',
                        'message' => 'Unsupported locale'
                    ]
                ], 400);
            }
            
            $this->translationService->clearTranslationCache($locale);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'message' => $locale 
                        ? "Translation cache cleared for locale: {$locale}"
                        : 'Translation cache cleared for all locales'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CACHE_ERROR',
                    'message' => 'Failed to clear translation cache'
                ]
            ], 500);
        }
    }

    /**
     * Get supported locales
     */
    public function getSupportedLocales(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'supported_locales' => $this->translationService->getSupportedLocales()
            ]
        ]);
    }

    /**
     * Validate translation key format
     */
    public function validateKey(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'key' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'VALIDATION_ERROR',
                    'message' => 'Invalid request parameters',
                    'details' => $validator->errors()
                ]
            ], 422);
        }

        $key = $request->input('key');
        $isValid = $this->translationService->validateTranslationKey($key);
        
        return response()->json([
            'success' => true,
            'data' => [
                'key' => $key,
                'is_valid' => $isValid,
                'message' => $isValid ? 'Key is valid' : 'Key format is invalid'
            ]
        ]);
    }
}