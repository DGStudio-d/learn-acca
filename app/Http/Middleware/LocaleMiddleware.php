<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class LocaleMiddleware
{
    /**
     * Supported locales
     */
    private const SUPPORTED_LOCALES = ['ar', 'en', 'es'];
    
    /**
     * Default locale
     */
    private const DEFAULT_LOCALE = 'en';

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->detectLocale($request);
        
        // Set the application locale
        App::setLocale($locale);
        
        // Add locale to request for later use
        $request->attributes->set('locale', $locale);
        
        return $next($request);
    }

    /**
     * Detect the appropriate locale from the request
     */
    private function detectLocale(Request $request): string
    {
        // 1. Check for explicit locale parameter in request
        $requestLocale = $request->input('locale') ?? $request->header('Accept-Language-Locale');
        if ($requestLocale && $this->isValidLocale($requestLocale)) {
            return $requestLocale;
        }

        // 2. Check Accept-Language header
        $acceptLanguage = $request->header('Accept-Language');
        if ($acceptLanguage) {
            $preferredLocale = $this->parseAcceptLanguageHeader($acceptLanguage);
            if ($preferredLocale && $this->isValidLocale($preferredLocale)) {
                return $preferredLocale;
            }
        }

        // 3. Check user preference if authenticated
        if ($request->user() && $request->user()->preferred_locale) {
            $userLocale = $request->user()->preferred_locale;
            if ($this->isValidLocale($userLocale)) {
                return $userLocale;
            }
        }

        // 4. Fall back to default locale
        return self::DEFAULT_LOCALE;
    }

    /**
     * Check if the given locale is supported
     */
    private function isValidLocale(string $locale): bool
    {
        return in_array($locale, self::SUPPORTED_LOCALES);
    }

    /**
     * Parse Accept-Language header to get preferred locale
     */
    private function parseAcceptLanguageHeader(string $acceptLanguage): ?string
    {
        // Parse Accept-Language header (e.g., "en-US,en;q=0.9,ar;q=0.8")
        $languages = [];
        
        foreach (explode(',', $acceptLanguage) as $lang) {
            $parts = explode(';', trim($lang));
            $locale = trim($parts[0]);
            $quality = 1.0;
            
            if (isset($parts[1]) && strpos($parts[1], 'q=') === 0) {
                $quality = (float) substr($parts[1], 2);
            }
            
            // Extract language code (e.g., "en" from "en-US")
            $langCode = strtolower(substr($locale, 0, 2));
            $languages[$langCode] = $quality;
        }
        
        // Sort by quality (preference)
        arsort($languages);
        
        // Return first supported language
        foreach (array_keys($languages) as $langCode) {
            if ($this->isValidLocale($langCode)) {
                return $langCode;
            }
        }
        
        return null;
    }

    /**
     * Get supported locales
     */
    public static function getSupportedLocales(): array
    {
        return self::SUPPORTED_LOCALES;
    }

    /**
     * Get default locale
     */
    public static function getDefaultLocale(): string
    {
        return self::DEFAULT_LOCALE;
    }
}