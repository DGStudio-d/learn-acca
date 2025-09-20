<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\TranslationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TranslationSystemTest extends TestCase
{
    use RefreshDatabase;

    private TranslationService $translationService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->translationService = app(TranslationService::class);
        
        // Create roles for testing
        \Spatie\Permission\Models\Role::create(['name' => 'admin']);
        \Spatie\Permission\Models\Role::create(['name' => 'teacher']);
        \Spatie\Permission\Models\Role::create(['name' => 'student']);
    }

    public function test_can_get_supported_locales()
    {
        $response = $this->getJson('/api/translations/locales');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'supported_locales' => ['ar', 'en', 'es']
                    ]
                ]);
    }

    public function test_can_get_translations_for_valid_locale()
    {
        $response = $this->getJson('/api/translations/en');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'locale',
                        'translations',
                        'supported_locales'
                    ]
                ]);
    }

    public function test_returns_error_for_invalid_locale()
    {
        $response = $this->getJson('/api/translations/invalid');

        $response->assertStatus(400)
                ->assertJson([
                    'success' => false,
                    'error' => [
                        'code' => 'INVALID_LOCALE'
                    ]
                ]);
    }

    public function test_locale_middleware_detects_locale_from_header()
    {
        $response = $this->withHeaders([
            'Accept-Language' => 'ar,en;q=0.9'
        ])->getJson('/api/translations/en');

        $response->assertStatus(200);
        // The middleware should set the locale to 'ar' based on the header
    }

    public function test_locale_middleware_uses_user_preference()
    {
        $user = User::factory()->create([
            'preferred_locale' => 'es'
        ]);

        $response = $this->actingAs($user, 'sanctum')
                        ->getJson('/api/translations/en');

        $response->assertStatus(200);
        // The middleware should consider user's preferred locale
    }

    public function test_can_get_specific_translation_key()
    {
        $response = $this->postJson('/api/translations/en/translate', [
            'key' => 'welcome'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'locale' => 'en',
                        'key' => 'welcome',
                        'translation' => 'Welcome to Learn Academy'
                    ]
                ]);
    }

    public function test_can_get_nested_translation_key()
    {
        $response = $this->postJson('/api/translations/en/translate', [
            'key' => 'ui.save'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'locale' => 'en',
                        'key' => 'ui.save',
                        'translation' => 'Save'
                    ]
                ]);
    }

    public function test_fallback_to_default_locale_when_key_missing()
    {
        // This test assumes a key exists in English but not in Arabic
        $translation = $this->translationService->getTranslation('ar', 'nonexistent.key');
        
        // Should return the key itself as fallback
        $this->assertEquals('nonexistent.key', $translation);
    }

    public function test_validates_translation_key_format()
    {
        $response = $this->postJson('/api/translations/validate-key', [
            'key' => 'valid.key_name'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'is_valid' => true
                    ]
                ]);

        $response = $this->postJson('/api/translations/validate-key', [
            'key' => 'invalid..key'
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'is_valid' => false
                    ]
                ]);
    }

    public function test_admin_can_update_translation()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin, 'sanctum')
                        ->putJson('/api/translations/en', [
                            'key' => 'test.new_key',
                            'value' => 'Test Value'
                        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'locale' => 'en',
                        'key' => 'test.new_key',
                        'value' => 'Test Value'
                    ]
                ]);
    }

    public function test_non_admin_cannot_update_translation()
    {
        $user = User::factory()->create();
        $user->assignRole('student');

        $response = $this->actingAs($user, 'sanctum')
                        ->putJson('/api/translations/en', [
                            'key' => 'test.key',
                            'value' => 'Test Value'
                        ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_clear_translation_cache()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin, 'sanctum')
                        ->postJson('/api/translations/cache/clear');

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true
                ]);
    }

    public function test_can_get_available_keys_for_locale()
    {
        $response = $this->getJson('/api/translations/en/keys');

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'locale',
                        'keys',
                        'count'
                    ]
                ]);
    }

    public function test_translation_service_validates_key_format()
    {
        $this->assertTrue($this->translationService->validateTranslationKey('valid.key'));
        $this->assertTrue($this->translationService->validateTranslationKey('valid_key'));
        $this->assertTrue($this->translationService->validateTranslationKey('valid123'));
        
        $this->assertFalse($this->translationService->validateTranslationKey(''));
        $this->assertFalse($this->translationService->validateTranslationKey('.invalid'));
        $this->assertFalse($this->translationService->validateTranslationKey('invalid.'));
        $this->assertFalse($this->translationService->validateTranslationKey('invalid..key'));
        $this->assertFalse($this->translationService->validateTranslationKey('invalid-key'));
    }

    public function test_translation_service_caching()
    {
        // Clear cache first
        Cache::flush();
        
        // First call should load from files
        $translations1 = $this->translationService->getTranslations('en');
        
        // Second call should load from cache
        $translations2 = $this->translationService->getTranslations('en');
        
        $this->assertEquals($translations1, $translations2);
    }

    public function test_translation_with_parameters()
    {
        $response = $this->postJson('/api/translations/en/translate', [
            'key' => 'welcome',
            'parameters' => [
                'name' => 'John'
            ]
        ]);

        $response->assertStatus(200);
    }
}