<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class TranslationService
{
    /**
     * Supported locales
     */
    private const SUPPORTED_LOCALES = ['ar', 'en', 'es'];
    
    /**
     * Default locale for fallback
     */
    private const DEFAULT_LOCALE = 'en';
    
    /**
     * Cache duration in minutes
     */
    private const CACHE_DURATION = 60;

    /**
     * Get all translations for a specific locale
     */
    public function getTranslations(string $locale): array
    {
        if (!$this->isValidLocale($locale)) {
            throw new InvalidArgumentException("Unsupported locale: {$locale}");
        }

        $cacheKey = "translations.{$locale}";
        
        return Cache::remember($cacheKey, self::CACHE_DURATION, function () use ($locale) {
            return $this->loadTranslationsFromFiles($locale);
        });
    }

    /**
     * Get a specific translation key with fallback support
     */
    public function getTranslation(string $locale, string $key, array $parameters = []): string
    {
        $translations = $this->getTranslations($locale);
        
        // Try to get translation for requested locale
        $translation = $this->getNestedValue($translations, $key);
        
        // If not found, try with 'messages' prefix (default file)
        if ($translation === null && !str_starts_with($key, 'messages.')) {
            $translation = $this->getNestedValue($translations, "messages.{$key}");
        }
        
        // If not found and locale is not default, try fallback to default locale
        if ($translation === null && $locale !== self::DEFAULT_LOCALE) {
            $fallbackTranslations = $this->getTranslations(self::DEFAULT_LOCALE);
            $translation = $this->getNestedValue($fallbackTranslations, $key);
            
            // Try with messages prefix in fallback
            if ($translation === null && !str_starts_with($key, 'messages.')) {
                $translation = $this->getNestedValue($fallbackTranslations, "messages.{$key}");
            }
            
            // Log missing translation
            Log::warning("Translation key '{$key}' not found for locale '{$locale}', using fallback");
        }
        
        // If still not found, return the key itself as fallback
        if ($translation === null) {
            Log::warning("Translation key '{$key}' not found in any locale");
            return $key;
        }
        
        // Replace parameters if provided
        if (!empty($parameters)) {
            $translation = $this->replaceParameters($translation, $parameters);
        }
        
        return $translation;
    }

    /**
     * Update a translation key dynamically
     */
    public function updateTranslation(string $locale, string $key, string $value): bool
    {
        if (!$this->isValidLocale($locale)) {
            throw new InvalidArgumentException("Unsupported locale: {$locale}");
        }

        try {
            // Load current translations
            $translations = $this->loadTranslationsFromFiles($locale);
            
            // Update the specific key
            $this->setNestedValue($translations, $key, $value);
            
            // Save back to file
            $this->saveTranslationsToFile($locale, $translations);
            
            // Clear cache
            $this->clearTranslationCache($locale);
            
            Log::info("Translation updated", [
                'locale' => $locale,
                'key' => $key,
                'value' => $value
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to update translation", [
                'locale' => $locale,
                'key' => $key,
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    /**
     * Validate translation key format
     */
    public function validateTranslationKey(string $key): bool
    {
        // Key should not be empty
        if (empty($key)) {
            return false;
        }
        
        // Key should only contain alphanumeric characters, dots, and underscores
        if (!preg_match('/^[a-zA-Z0-9._]+$/', $key)) {
            return false;
        }
        
        // Key should not start or end with a dot
        if (str_starts_with($key, '.') || str_ends_with($key, '.')) {
            return false;
        }
        
        // Key should not have consecutive dots
        if (str_contains($key, '..')) {
            return false;
        }
        
        return true;
    }

    /**
     * Get all available translation keys for a locale
     */
    public function getAvailableKeys(string $locale): array
    {
        if (!$this->isValidLocale($locale)) {
            throw new InvalidArgumentException("Unsupported locale: {$locale}");
        }

        $translations = $this->getTranslations($locale);
        return $this->flattenArray($translations);
    }

    /**
     * Clear translation cache for a specific locale or all locales
     */
    public function clearTranslationCache(?string $locale = null): void
    {
        if ($locale) {
            Cache::forget("translations.{$locale}");
        } else {
            foreach (self::SUPPORTED_LOCALES as $supportedLocale) {
                Cache::forget("translations.{$supportedLocale}");
            }
        }
    }

    /**
     * Get supported locales
     */
    public function getSupportedLocales(): array
    {
        return self::SUPPORTED_LOCALES;
    }

    /**
     * Check if locale is valid
     */
    private function isValidLocale(string $locale): bool
    {
        return in_array($locale, self::SUPPORTED_LOCALES);
    }

    /**
     * Load translations from files
     */
    private function loadTranslationsFromFiles(string $locale): array
    {
        $translations = [];
        $langPath = resource_path("lang/{$locale}");
        
        if (!File::exists($langPath)) {
            Log::warning("Translation directory not found: {$langPath}");
            return [];
        }
        
        $files = File::files($langPath);
        
        foreach ($files as $file) {
            if ($file->getExtension() === 'php') {
                $filename = $file->getFilenameWithoutExtension();
                $fileTranslations = include $file->getPathname();
                
                if (is_array($fileTranslations)) {
                    $translations[$filename] = $fileTranslations;
                }
            }
        }
        
        return $translations;
    }

    /**
     * Save translations to file
     */
    private function saveTranslationsToFile(string $locale, array $translations): void
    {
        $langPath = resource_path("lang/{$locale}");
        
        if (!File::exists($langPath)) {
            File::makeDirectory($langPath, 0755, true);
        }
        
        foreach ($translations as $filename => $fileTranslations) {
            $filePath = "{$langPath}/{$filename}.php";
            $content = "<?php\n\nreturn " . var_export($fileTranslations, true) . ";\n";
            File::put($filePath, $content);
        }
    }

    /**
     * Get nested value from array using dot notation
     */
    private function getNestedValue(array $array, string $key): ?string
    {
        $keys = explode('.', $key);
        $value = $array;
        
        foreach ($keys as $nestedKey) {
            if (!is_array($value) || !array_key_exists($nestedKey, $value)) {
                return null;
            }
            $value = $value[$nestedKey];
        }
        
        return is_string($value) ? $value : null;
    }

    /**
     * Set nested value in array using dot notation
     */
    private function setNestedValue(array &$array, string $key, string $value): void
    {
        $keys = explode('.', $key);
        $current = &$array;
        
        foreach ($keys as $nestedKey) {
            if (!isset($current[$nestedKey]) || !is_array($current[$nestedKey])) {
                $current[$nestedKey] = [];
            }
            $current = &$current[$nestedKey];
        }
        
        $current = $value;
    }

    /**
     * Replace parameters in translation string
     */
    private function replaceParameters(string $translation, array $parameters): string
    {
        foreach ($parameters as $key => $value) {
            $translation = str_replace(":{$key}", $value, $translation);
        }
        
        return $translation;
    }

    /**
     * Flatten nested array to dot notation keys
     */
    private function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];
        
        foreach ($array as $key => $value) {
            $newKey = $prefix ? "{$prefix}.{$key}" : $key;
            
            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[] = $newKey;
            }
        }
        
        return $result;
    }
}