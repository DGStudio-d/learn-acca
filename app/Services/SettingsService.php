<?php

namespace App\Services;

use App\Models\Settings;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SettingsService
{
    private const CACHE_KEY = 'guest_access_settings';
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * Get all guest access settings with caching.
     */
    public function getGuestAccessSettings(): array
    {
        return Cache::remember(self::CACHE_KEY, self::CACHE_TTL, function () {
            return Settings::getGuestAccessSettings();
        });
    }

    /**
     * Check if guest access is allowed for a specific feature.
     */
    public function isGuestAccessAllowed(string $feature): bool
    {
        $settings = $this->getGuestAccessSettings();
        $key = "allow_guest_{$feature}";
        
        return $settings[$key] ?? false;
    }

    /**
     * Update guest access settings with validation.
     */
    public function updateGuestAccessSettings(array $settings): array
    {
        $this->validateGuestAccessSettings($settings);

        // Update settings in database
        Settings::updateGuestAccessSettings($settings);

        // Clear cache to ensure immediate effect
        Cache::forget(self::CACHE_KEY);

        // Return updated settings
        return $this->getGuestAccessSettings();
    }

    /**
     * Get a specific setting value.
     */
    public function getSetting(string $key, $default = null)
    {
        return Settings::getValue($key, $default);
    }

    /**
     * Set a specific setting value.
     */
    public function setSetting(string $key, $value, string $type = 'string', string $description = null): Settings
    {
        $setting = Settings::setValue($key, $value, $type, $description);
        
        // Clear cache if it's a guest access setting
        if (str_starts_with($key, 'allow_guest_')) {
            Cache::forget(self::CACHE_KEY);
        }

        return $setting;
    }

    /**
     * Validate guest access settings.
     */
    private function validateGuestAccessSettings(array $settings): void
    {
        $validator = Validator::make($settings, [
            'allow_guest_languages' => 'sometimes|boolean',
            'allow_guest_teachers' => 'sometimes|boolean',
            'allow_guest_quizzes' => 'sometimes|boolean',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    /**
     * Get all available guest access features.
     */
    public function getAvailableGuestFeatures(): array
    {
        return [
            'languages' => 'Allow guests to view available languages',
            'teachers' => 'Allow guests to view teacher profiles',
            'quizzes' => 'Allow guests to attempt quizzes anonymously',
        ];
    }

    /**
     * Initialize default guest access settings if they don't exist.
     */
    public function initializeDefaultSettings(): void
    {
        $defaultSettings = [
            'allow_guest_languages' => false,
            'allow_guest_teachers' => false,
            'allow_guest_quizzes' => false,
        ];

        foreach ($defaultSettings as $key => $value) {
            if (Settings::getValue($key) === null) {
                Settings::setValue(
                    $key, 
                    $value, 
                    'boolean', 
                    "Allow guest access to " . str_replace('allow_guest_', '', $key)
                );
            }
        }

        // Clear cache after initialization
        Cache::forget(self::CACHE_KEY);
    }
}