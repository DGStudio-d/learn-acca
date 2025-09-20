<?php

namespace Tests\Unit\Services;

use App\Models\Settings;
use App\Services\SettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    private SettingsService $settingsService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->settingsService = new SettingsService();
    }

    public function test_can_get_guest_access_settings()
    {
        // Create test settings
        Settings::setValue('allow_guest_languages', true, 'boolean');
        Settings::setValue('allow_guest_teachers', false, 'boolean');
        Settings::setValue('allow_guest_quizzes', true, 'boolean');

        $settings = $this->settingsService->getGuestAccessSettings();

        $this->assertIsArray($settings);
        $this->assertTrue($settings['allow_guest_languages']);
        $this->assertFalse($settings['allow_guest_teachers']);
        $this->assertTrue($settings['allow_guest_quizzes']);
    }

    public function test_can_check_guest_access_for_specific_feature()
    {
        Settings::setValue('allow_guest_languages', true, 'boolean');
        Settings::setValue('allow_guest_teachers', false, 'boolean');

        $this->assertTrue($this->settingsService->isGuestAccessAllowed('languages'));
        $this->assertFalse($this->settingsService->isGuestAccessAllowed('teachers'));
        $this->assertFalse($this->settingsService->isGuestAccessAllowed('nonexistent'));
    }

    public function test_can_update_guest_access_settings()
    {
        $newSettings = [
            'allow_guest_languages' => true,
            'allow_guest_teachers' => true,
            'allow_guest_quizzes' => false,
        ];

        $result = $this->settingsService->updateGuestAccessSettings($newSettings);

        $this->assertIsArray($result);
        $this->assertTrue($result['allow_guest_languages']);
        $this->assertTrue($result['allow_guest_teachers']);
        $this->assertFalse($result['allow_guest_quizzes']);

        // Verify settings were saved to database
        $this->assertTrue(Settings::getValue('allow_guest_languages'));
        $this->assertTrue(Settings::getValue('allow_guest_teachers'));
        $this->assertFalse(Settings::getValue('allow_guest_quizzes'));
    }

    public function test_update_guest_access_settings_validates_input()
    {
        $invalidSettings = [
            'allow_guest_languages' => 'invalid_boolean',
            'allow_guest_teachers' => 123,
        ];

        $this->expectException(ValidationException::class);
        $this->settingsService->updateGuestAccessSettings($invalidSettings);
    }

    public function test_can_get_and_set_individual_settings()
    {
        $this->settingsService->setSetting('test_key', 'test_value', 'string', 'Test description');

        $value = $this->settingsService->getSetting('test_key');
        $this->assertEquals('test_value', $value);

        $defaultValue = $this->settingsService->getSetting('nonexistent_key', 'default');
        $this->assertEquals('default', $defaultValue);
    }

    public function test_can_initialize_default_settings()
    {
        // Ensure no settings exist
        Settings::query()->delete();

        $this->settingsService->initializeDefaultSettings();

        $this->assertFalse(Settings::getValue('allow_guest_languages'));
        $this->assertFalse(Settings::getValue('allow_guest_teachers'));
        $this->assertFalse(Settings::getValue('allow_guest_quizzes'));
    }

    public function test_initialize_default_settings_does_not_override_existing()
    {
        Settings::setValue('allow_guest_languages', true, 'boolean');

        $this->settingsService->initializeDefaultSettings();

        // Should not override existing setting
        $this->assertTrue(Settings::getValue('allow_guest_languages'));
        // Should create missing settings
        $this->assertFalse(Settings::getValue('allow_guest_teachers'));
        $this->assertFalse(Settings::getValue('allow_guest_quizzes'));
    }

    public function test_cache_is_cleared_when_updating_guest_settings()
    {
        // Set initial cache with different data
        Cache::put('guest_access_settings', ['cached' => true], 3600);
        
        // Verify cache exists with old data
        $this->assertEquals(['cached' => true], Cache::get('guest_access_settings'));

        $this->settingsService->updateGuestAccessSettings([
            'allow_guest_languages' => true,
        ]);

        // Cache should be cleared and repopulated with new data
        $cachedData = Cache::get('guest_access_settings');
        $this->assertNotEquals(['cached' => true], $cachedData);
        $this->assertTrue($cachedData['allow_guest_languages']);
    }

    public function test_can_get_available_guest_features()
    {
        $features = $this->settingsService->getAvailableGuestFeatures();

        $this->assertIsArray($features);
        $this->assertArrayHasKey('languages', $features);
        $this->assertArrayHasKey('teachers', $features);
        $this->assertArrayHasKey('quizzes', $features);
    }
}